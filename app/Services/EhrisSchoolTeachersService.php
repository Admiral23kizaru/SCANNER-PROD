<?php

namespace App\Services;

use App\Models\Ehris\EhrisUser;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Teachers for section assignment: same roster as Manage Teachers (local school scope),
 * validated against EHRIS tbl_user for the school's DepEd ID when linked.
 */
class EhrisSchoolTeachersService
{
    /**
     * @return array{message?: ?string, deped_school_id: ?string, data: Collection, total: int}
     */
    public function assignmentListForSchool(School $school): array
    {
        $deped = trim((string) ($school->deped_school_id ?? ''));

        $ehrisEmployeeIds = $this->ehrisEmployeeIdsForSchool($school, $deped);

        $localTeachers = $this->schoolScopedLocalTeacherQuery($school)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $data = $localTeachers
            ->map(function (Teacher $teacher) use ($school, $deped, $ehrisEmployeeIds) {
                if (! $this->localTeacherBelongsToEhrisSchool($teacher, $deped, $ehrisEmployeeIds)) {
                    return null;
                }

                return $this->mapLocalTeacherToAssignment($teacher, $school);
            })
            ->filter()
            ->values();

        return [
            'deped_school_id' => $deped !== '' ? $deped : null,
            'data' => $data,
            'total' => $data->count(),
            'message' => $deped === ''
                ? 'Showing teachers registered to this school. Link a DepEd School ID to align with EHRIS.'
                : null,
        ];
    }

    /**
     * Active EHRIS teacher employee IDs for this DepEd school (read-only tbl_user).
     */
    private function ehrisEmployeeIdsForSchool(School $school, string $deped): Collection
    {
        if ($deped === '') {
            return collect();
        }

        try {
            return $this->ehrisTeacherQueryForSchool($school)
                ->get()
                ->map(fn (EhrisUser $row) => $this->resolveEhrisEmployeeId($row))
                ->filter()
                ->unique()
                ->values();
        } catch (\Throwable $e) {
            report($e);

            return collect();
        }
    }

    /**
     * Local teachers table scoped to the admin school (same rules as Manage Teachers).
     */
    private function schoolScopedLocalTeacherQuery(School $school)
    {
        $query = Teacher::query();
        $table = (new Teacher())->getTable();

        if (Schema::hasColumn($table, 'school_id')) {
            return $query->where('school_id', $school->id);
        }

        if (Schema::hasColumn($table, 'school_name')) {
            return $query->where('school_name', $school->name);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * When DepEd ID is set, only keep local rows whose employee_id exists in EHRIS for that school.
     * Teachers without employee_id stay if they are already in the local school roster.
     */
    private function localTeacherBelongsToEhrisSchool(
        Teacher $teacher,
        string $deped,
        Collection $ehrisEmployeeIds
    ): bool {
        if ($deped === '' || $ehrisEmployeeIds->isEmpty()) {
            return true;
        }

        $employeeId = trim((string) ($teacher->employee_id ?? ''));
        if ($employeeId === '') {
            return true;
        }

        return $ehrisEmployeeIds->contains($employeeId);
    }

    private function ehrisTeacherQueryForSchool(School $school)
    {
        $deped = trim((string) ($school->deped_school_id ?? ''));

        $query = EhrisUser::active()
            ->where('role', 'Teacher');

        if ($deped !== '') {
            $query->where(function ($inner) use ($deped) {
                $inner->where('department_id', $deped);
                if (ctype_digit($deped)) {
                    $inner->orWhere('department_id', (int) $deped);
                }
            });
        }

        return $query;
    }

    private function mapLocalTeacherToAssignment(Teacher $teacher, School $school): array
    {
        $email = strtolower(trim((string) ($teacher->email ?? '')));

        $user = null;
        if ($email !== '') {
            $user = User::query()
                ->where('email', $email)
                ->where('school_id', $school->id)
                ->first();
        }

        $name = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? ''));
        if ($name === '') {
            $name = trim((string) ($teacher->email ?? '')) ?: 'Teacher';
        }

        return [
            'id' => $user?->id,
            'name' => $name,
            'employee_id' => $teacher->employee_id,
            'grade_level' => $user?->grade_level,
            'section' => $user?->section,
            'assignable' => $user !== null,
        ];
    }

    private function resolveEhrisEmployeeId(EhrisUser $ehrisUser): string
    {
        return trim((string) ($ehrisUser->hrId ?: $ehrisUser->userId));
    }
}
