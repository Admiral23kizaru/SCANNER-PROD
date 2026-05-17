<?php

namespace App\Services;

use App\Models\Ehris\EhrisDepartment;
use App\Models\Ehris\EhrisReportingManager;
use App\Models\Ehris\EhrisUser;
use App\Models\Role;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * EhrisAuthService
 *
 * PURPOSE: All EHRIS authentication logic lives here.
 * Verifies teacher/reporting manager credentials
 * against EHRIS local database (read-only).
 * After verification, provisions the ScanUp user when allowed.
 *
 * FLOW:
 * 1. Find user in EHRIS tbl_user by email
 * 2. Check active = 1
 * 3. Verify password with Hash::check
 * 4. Check tbl_reporting_manager for RM status
 * 5. Find ScanUp school via department_id
 * 6. Require Teacher accounts to already exist in tbl_scanup_teachers
 * 7. Create or update ScanUp user (with trashed)
 * 8. Return ScanUp User for token issuance
 *
 * ZERO WRITES TO EHRIS — read-only always.
 */
class EhrisAuthService
{
    /**
     * findEhrisUser
     * PURPOSE: Find active EHRIS user by email.
     * WHY: Centralizes tbl_user lookup with active scope.
     *
     * @param string $email Login email from the request.
     * @return EhrisUser|null Null when missing or inactive.
     */
    public function findEhrisUser(string $email): ?EhrisUser
    {
        return EhrisUser::active()
            ->where('email', $email)
            ->first();
    }

    /**
     * verifyPassword
     * PURPOSE: Check submitted password against
     * bcrypt hash in EHRIS tbl_user.password.
     * WHY: Both systems use Laravel bcrypt — compatible.
     *
     * @param string $plainPassword Raw password from login.
     * @param string $hashedPassword Stored bcrypt hash from EHRIS.
     * @return bool True when the password matches.
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return Hash::check($plainPassword, $hashedPassword);
    }

    /**
     * isReportingManager
     * PURPOSE: Check if EHRIS user is a Reporting
     * Manager by looking up tbl_reporting_manager
     * where manager_name = tbl_user.userId.
     * WHY: RM status drives ScanUp role mapping.
     *
     * @param int $ehrisUserId tbl_user.userId primary key.
     * @return bool True when this user is listed as an RM.
     */
    public function isReportingManager(int $ehrisUserId): bool
    {
        return EhrisReportingManager::where('manager_name', $ehrisUserId)->exists();
    }

    /**
     * getScanUpRoleName
     * PURPOSE: Maps EHRIS user to ScanUp role name.
     * WHY: RM check uses tbl_reporting_manager; others default to Teacher.
     *
     * NOTE: Adviser and Subject Teacher are NOT
     * assigned from EHRIS. They are assigned inside
     * ScanUp by Reporting Manager (Phase 2 feature).
     *
     * @param EhrisUser $ehrisUser Authenticated EHRIS row.
     * @return string ScanUp role display name.
     */
    public function getScanUpRoleName(EhrisUser $ehrisUser): string
    {
        if ($this->isReportingManager((int) $ehrisUser->userId)) {
            return 'Reporting Manager';
        }

        return 'Teacher';
    }

    /**
     * isEhrisUser
     * PURPOSE: Decide if login should go through
     * EHRIS or use local ScanUp auth.
     * WHY: Admin and Guard stay on local credentials; teachers sync from EHRIS.
     *
     * LOGIC:
     * - Found in ScanUp with role Admin → local
     * - Found in ScanUp with role Guard → local
     * - Soft-deleted user → EHRIS (restore on login)
     * - Not found in ScanUp → try EHRIS
     * - Found with Teacher/RM/other role → EHRIS
     *
     * WHY withTrashed():
     * ScanUp users has deleted_at (SoftDeletes active).
     * Previously deleted teacher should still route
     * to EHRIS — they will be restored on success.
     *
     * @param string $email Login email from the request.
     * @return bool True routes to EHRIS path; false uses local ScanUp auth.
     */
    public function isEhrisUser(string $email): bool
    {
        $user = User::withTrashed()
            ->where('email', $email)
            ->with('role')
            ->first();

        if (!$user) {
            return true;
        }

        if ($user->trashed()) {
            return true;
        }

        $roleName = $user->role?->name ?? '';

        if (in_array($roleName, ['Admin', 'Guard'], true)) {
            return false;
        }

        return true;
    }

    /**
     * provisionUser
     * PURPOSE: Creates or updates ScanUp user after successful EHRIS verification.
     * WHY: Reporting Managers can bootstrap their school, but Teacher accounts
     * must already be synced through Fetch EHRIS before they can login.
     *
     * SOFT DELETE HANDLING:
     * Uses withTrashed() + restore() to avoid
     * duplicate key on unique email constraint.
     *
     * PASSWORD:
     * Stores random unusable hash — real auth
     * is EHRIS. ScanUp never uses this hash.
     *
     * SCHOOL MAPPING:
     * EHRIS tbl_user.department_id (int)
     *   = ScanUp schools.deped_school_id (varchar)
     * Cast to string for safe comparison.
     *
     * @param EhrisUser $ehrisUser Verified EHRIS account row.
     * @param string $roleName ScanUp roles.name value to assign.
     * @return User Provisioned ScanUp user model.
     *
     * @throws \RuntimeException When school or role is missing in ScanUp.
     */
    public function provisionUser(EhrisUser $ehrisUser, string $roleName): User
    {
        $school = $this->resolveScanUpSchool($ehrisUser);

        if (!$school) {
            throw new \RuntimeException(
                'Your school (DepEd ID: ' .
                $ehrisUser->department_id .
                ') is not registered in ScanUp. ' .
                'Contact your system administrator.'
            );
        }

        $role = Role::where('name', $roleName)->first();

        // Backward compatibility: older databases may not have the RM role row yet.
        if (!$role && $roleName === 'Reporting Manager') {
            $role = Role::firstOrCreate(['name' => 'Reporting Manager']);
        }

        if (!$role) {
            throw new \RuntimeException(
                "Role '{$roleName}' does not exist " .
                'in ScanUp. Run migrations first.'
            );
        }

        if ($roleName === 'Teacher') {
            $this->ensureTeacherWasSynced($ehrisUser, $school);
        }

        $existing = User::withTrashed()
            ->where('email', $ehrisUser->email)
            ->first();

        if ($existing && $existing->trashed()) {
            $existing->restore();
        }

        $user = User::withTrashed()->updateOrCreate(
            ['email' => $ehrisUser->email],
            [
                'name' => $ehrisUser->full_name,
                'role_id' => $role->id,
                'school_id' => $school->id,
                'status' => 'active',
                'employee_id' => (string) ($ehrisUser->hrId ?: $ehrisUser->userId),
                'job_title' => $ehrisUser->job_title ?? null,
                'password' => Str::random(64),
            ]
        );

        if ($user->deleted_at) {
            $user->restore();
        }

        return $user->fresh();
    }

    /**
     * ensureTeacherWasSynced
     * PURPOSE: Block first-login auto-provisioning for EHRIS teachers.
     * WHY: Teachers should only login after the Principal/Reporting Manager
     * uses Fetch EHRIS / Sync All, which creates their tbl_scanup_teachers row.
     *
     * @param EhrisUser $ehrisUser Verified EHRIS Teacher row.
     * @param School $school ScanUp school resolved from department_id.
     *
     * @throws \RuntimeException When the teacher is active in EHRIS but not yet synced.
     */
    private function ensureTeacherWasSynced(EhrisUser $ehrisUser, School $school): void
    {
        $employeeId = trim((string) ($ehrisUser->hrId ?: $ehrisUser->userId));
        $email = strtolower(trim((string) ($ehrisUser->email ?? '')));

        if ($employeeId === '' && $email === '') {
            throw new \RuntimeException(
                'Your EHRIS teacher account is missing both employee ID and email.'
            );
        }

        $teacher = Teacher::where('school_id', $school->id)
            ->where(function ($query) use ($employeeId, $email) {
                if ($employeeId !== '') {
                    $query->where('employee_id', $employeeId);
                }

                if ($email !== '') {
                    if ($employeeId !== '') {
                        $query->orWhere('email', $email);
                    } else {
                        $query->where('email', $email);
                    }
                }
            })
            ->first();

        if (!$teacher) {
            throw new \RuntimeException(
                'Your EHRIS teacher account is active, but it has not been ' .
                'synced in ScanUp yet. Ask your Principal or Reporting Manager ' .
                'to use Fetch EHRIS / Sync All first.'
            );
        }

        if (($teacher->status ?? 'active') !== 'active') {
            throw new \RuntimeException(
                'Your teacher account is inactive in ScanUp. Contact your ' .
                'Principal or Reporting Manager.'
            );
        }
    }

    /**
     * resolveScanUpSchool
     * PURPOSE: Resolve the ScanUp school row for an EHRIS user’s department_id.
     * WHY: When no manual ScanUp row exists, EHRIS tbl_depart (read-only) supplies the
     * official school name so first login can auto-create schools.deped_school_id safely.
     *
     * @param EhrisUser $ehrisUser Verified EHRIS account with department_id set.
     * @return School|null Null when neither ScanUp nor tbl_depart has this DepEd ID.
     */
    private function resolveScanUpSchool(EhrisUser $ehrisUser): ?School
    {
        $depedKey = (string) $ehrisUser->department_id;

        $school = School::where('deped_school_id', $depedKey)->first();

        if ($school) {
            return $school;
        }

        $ehrisDept = EhrisDepartment::where(
            'department_id',
            $ehrisUser->department_id
        )->first();

        if (!$ehrisDept) {
            return null;
        }

        $label = trim((string) ($ehrisDept->department_name ?? ''));
        if ($label === '') {
            $label = 'School ' . $depedKey;
        }

        $unlinked = School::whereNull('deped_school_id')
            ->where('name', $label)
            ->first();

        if ($unlinked) {
            $unlinked->deped_school_id = $depedKey;
            $unlinked->save();

            return $unlinked->fresh();
        }

        return School::firstOrCreate(
            ['deped_school_id' => $depedKey],
            ['name' => $label]
        );
    }
}
