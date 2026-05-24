<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ehris\EhrisUser;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AdminSubjectController extends Controller
{
    private function normalizeSubjectName(string $name): string
    {
        return strtolower(trim($name));
    }

    private function schoolScope(Request $request): ?int
    {
        return $request->user()?->school_id;
    }

    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->schoolScope($request);

        try {
            $subjects = Subject::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->get();
        } catch (Throwable $exception) {
            Log::error('Admin subject list failed.', [
                'method' => __METHOD__,
                'school_id' => $schoolId,
                'table' => 'tbl_scanup_subjects',
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['data' => [], 'message' => 'Unable to read local subjects. Check laravel.log for Admin subject list failed.']);
        }

        return response()->json(['data' => $subjects]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $schoolId = $this->schoolScope($request);
        $name = trim((string) $request->name);

        if ($name === '') {
            return response()->json(['message' => 'Subject name is required.'], 422);
        }

        if ($schoolId && !School::whereKey($schoolId)->exists()) {
            Log::error('Admin subject create failed because user school_id is invalid.', [
                'method' => __METHOD__,
                'user_id' => $request->user()?->id,
                'school_id' => $schoolId,
            ]);

            return response()->json(['message' => 'Your Admin account is not linked to a valid school. Sync or recreate the school record first.'], 422);
        }

        try {
            $exists = Subject::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereRaw('LOWER(name) = ?', [$this->normalizeSubjectName($name)])
                ->exists();
        } catch (Throwable $exception) {
            Log::error('Admin subject duplicate check failed.', [
                'method' => __METHOD__,
                'school_id' => $schoolId,
                'table' => 'tbl_scanup_subjects',
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to check existing subjects. Check laravel.log for Admin subject duplicate check failed.'], 500);
        }

        if ($exists) {
            return response()->json(['message' => 'Subject already exists.'], 422);
        }

        try {
            $subject = $this->createSubjectRecord($name, $schoolId);
        } catch (Throwable $exception) {
            Log::error('Admin subject create failed.', [
                'method' => __METHOD__,
                'school_id' => $schoolId,
                'table' => 'tbl_scanup_subjects',
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to create subject. Check laravel.log for Admin subject create failed.'], 500);
        }

        return response()->json(['message' => 'Subject created.', 'data' => $subject], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->schoolScope($request);
        $subject = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->find($id);

        if (!$subject) {
            return response()->json(['message' => 'Subject not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $name = trim((string) $request->name);
        try {
            $exists = Subject::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereRaw('LOWER(name) = ?', [$this->normalizeSubjectName($name)])
                ->where('id', '!=', $subject->id)
                ->exists();
        } catch (Throwable $exception) {
            Log::error('Admin subject update duplicate check failed.', [
                'method' => __METHOD__,
                'school_id' => $schoolId,
                'subject_id' => $id,
                'table' => 'tbl_scanup_subjects',
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to check existing subjects. Check laravel.log for Admin subject update duplicate check failed.'], 500);
        }

        if ($exists) {
            return response()->json(['message' => 'Subject already exists.'], 422);
        }

        try {
            $subject->update(['name' => $name]);
        } catch (Throwable $exception) {
            Log::error('Admin subject update failed.', [
                'method' => __METHOD__,
                'school_id' => $schoolId,
                'subject_id' => $id,
                'table' => 'tbl_scanup_subjects',
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to update subject. Check laravel.log for Admin subject update failed.'], 500);
        }

        return response()->json(['message' => 'Subject updated.', 'data' => $subject]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->schoolScope($request);
        $subject = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->find($id);

        if (!$subject) {
            return response()->json(['message' => 'Subject not found.'], 404);
        }

        try {
            $subject->delete();
        } catch (Throwable $exception) {
            Log::error('Admin subject delete failed.', [
                'method' => __METHOD__,
                'school_id' => $schoolId,
                'subject_id' => $id,
                'table' => 'tbl_scanup_subjects',
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to delete subject. Check laravel.log for Admin subject delete failed.'], 500);
        }

        return response()->json(['message' => 'Subject deleted.']);
    }

    public function ehris(Request $request): JsonResponse
    {
        $school = $this->authSchool($request);
        if (!$school || !$school->deped_school_id) {
            return response()->json(['message' => 'School is not linked to a DepEd School ID.'], 422);
        }

        try {
            $subjects = $this->ehrisSubjectRowsForSchool($school);
            $existingNames = Subject::where('school_id', $school->id)
                ->pluck('name')
                ->map(fn ($name) => $this->normalizeSubjectName((string) $name))
                ->all();
            $existingMap = array_fill_keys($existingNames, true);
        } catch (Throwable $exception) {
            Log::error('Admin EHRIS subject fetch failed.', [
                'method' => __METHOD__,
                'school_id' => $school->id,
                'deped_school_id' => $school->deped_school_id,
                'database' => config('database.connections.ehris.database'),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'deped_school_id' => (string) $school->deped_school_id,
                'data' => [],
                'message' => 'Unable to read EHRIS subject tables. Check laravel.log for Admin EHRIS subject fetch failed.',
            ]);
        }

        return response()->json([
            'deped_school_id' => (string) $school->deped_school_id,
            'data' => $subjects->map(fn ($row) => [
                'name' => $row['name'],
                'teacher_count' => $row['teacher_count'],
                'sample_teachers' => $row['sample_teachers'],
                'source' => $row['source'],
                'is_synced' => isset($existingMap[$this->normalizeSubjectName($row['name'])]),
            ])->values(),
        ]);
    }

    public function syncEhris(Request $request): JsonResponse
    {
        $school = $this->authSchool($request);
        if (!$school || !$school->deped_school_id) {
            return response()->json(['message' => 'School is not linked to a DepEd School ID.'], 422);
        }

        $validated = $request->validate([
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['string', 'max:150'],
        ]);

        try {
            $ehrisSubjects = $this->ehrisSubjectRowsForSchool($school);
            $allowedMap = $ehrisSubjects
                ->mapWithKeys(fn ($row) => [$this->normalizeSubjectName($row['name']) => $row['name']]);
        } catch (Throwable $exception) {
            Log::error('Admin EHRIS subject sync source failed.', [
                'method' => __METHOD__,
                'school_id' => $school->id,
                'deped_school_id' => $school->deped_school_id,
                'database' => config('database.connections.ehris.database'),
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to read EHRIS subject tables. Check laravel.log for Admin EHRIS subject sync source failed.'], 422);
        }

        $requested = collect($validated['subjects'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        $targetNames = $requested->isNotEmpty()
            ? $requested
                ->map(fn ($name) => $allowedMap[$this->normalizeSubjectName($name)] ?? null)
                ->filter()
                ->values()
            : $allowedMap->values();

        $created = 0;
        $skipped = 0;

        foreach ($targetNames as $name) {
            $exists = Subject::where('school_id', $school->id)
                ->whereRaw('LOWER(name) = ?', [$this->normalizeSubjectName($name)])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $this->createSubjectRecord($name, $school->id);
            $created++;
        }

        return response()->json([
            'message' => 'EHRIS subjects synced.',
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    private function authSchool(Request $request): ?School
    {
        $schoolId = $this->schoolScope($request);

        return $schoolId ? School::find($schoolId) : null;
    }

    private function ehrisSubjectRowsForSchool(School $school)
    {
        $deped = trim((string) $school->deped_school_id);
        $connection = (new EhrisUser())->getConnectionName() ?: config('database.default');
        $schema = Schema::connection($connection);
        $db = DB::connection($connection);

        $hrids = EhrisUser::active()
            ->where('role', 'Teacher')
            ->where(function ($query) use ($deped) {
                $query->where('department_id', $deped);
                if (ctype_digit($deped)) {
                    $query->orWhere('department_id', (int) $deped);
                }
            })
            ->pluck('hrId')
            ->map(fn ($hrid) => trim((string) $hrid))
            ->filter()
            ->unique()
            ->values();

        $assignedSubjects = $hrids->isEmpty() ||
            ! $schema->hasTable('tbl_emp_official_subject_taught') ||
            ! $schema->hasColumn('tbl_emp_official_subject_taught', 'hrid') ||
            ! $schema->hasColumn('tbl_emp_official_subject_taught', 'subject_name')
            ? collect()
            : $this->assignedSubjectRows($db, $schema, $hrids);

        $subjectLibrary = $schema->hasTable('tbl_subject_library') && $schema->hasColumn('tbl_subject_library', 'name')
            ? $this->subjectLibraryRows($db, $schema)
            : collect();

        return $assignedSubjects
            ->merge($subjectLibrary)
            ->unique(fn ($row) => $this->normalizeSubjectName($row['name']))
            ->sortBy('name')
            ->values();
    }

    private function createSubjectRecord(string $name, ?int $schoolId): Subject
    {
        try {
            return Subject::create([
                'name' => $name,
                'school_id' => $schoolId,
            ]);
        } catch (Throwable $exception) {
            if (strpos($exception->getMessage(), "Field 'id' doesn't have a default value") === false) {
                throw $exception;
            }

            $id = ((int) Subject::max('id')) + 1;
            $now = now();

            Subject::query()->insert([
                'id' => $id,
                'name' => $name,
                'school_id' => $schoolId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return Subject::findOrFail($id);
        }
    }

    private function assignedSubjectRows($db, $schema, $hrids)
    {
        $query = $db->table('tbl_emp_official_subject_taught as taught')
            ->whereIn('taught.hrid', $hrids)
            ->whereRaw("TRIM(COALESCE(taught.subject_name, '')) <> ''");

        $hasUserTable = $schema->hasTable('tbl_user') &&
            $schema->hasColumn('tbl_user', 'hrId') &&
            $schema->hasColumn('tbl_user', 'firstname') &&
            $schema->hasColumn('tbl_user', 'lastname');

        if ($hasUserTable) {
            $query->leftJoin('tbl_user as users', 'taught.hrid', '=', 'users.hrId')
                ->select([
                    'taught.subject_name as name',
                    DB::raw('COUNT(DISTINCT taught.hrid) as teacher_count'),
                    DB::raw("GROUP_CONCAT(DISTINCT TRIM(CONCAT(COALESCE(users.firstname, ''), ' ', COALESCE(users.lastname, ''))) ORDER BY users.lastname SEPARATOR ', ') as sample_teachers"),
                ]);
        } else {
            $query->select([
                'taught.subject_name as name',
                DB::raw('COUNT(DISTINCT taught.hrid) as teacher_count'),
                DB::raw("'' as sample_teachers"),
            ]);
        }

        return $query
            ->groupBy('taught.subject_name')
            ->orderBy('taught.subject_name')
            ->get()
            ->map(fn ($row) => [
                'name' => trim((string) $row->name),
                'teacher_count' => (int) $row->teacher_count,
                'sample_teachers' => (string) $row->sample_teachers,
                'source' => 'teacher_assignment',
            ])
            ->filter(fn ($row) => $row['name'] !== '')
            ->values();
    }

    private function subjectLibraryRows($db, $schema)
    {
        $query = $db->table('tbl_subject_library')
            ->whereRaw("TRIM(COALESCE(name, '')) <> ''");

        if ($schema->hasColumn('tbl_subject_library', 'is_active')) {
            $query->where('is_active', 1);
        }

        return $query
            ->orderBy('name')
            ->get(['name'])
            ->map(fn ($row) => [
                'name' => trim((string) $row->name),
                'teacher_count' => 0,
                'sample_teachers' => '',
                'source' => 'subject_library',
            ])
            ->filter(fn ($row) => $row['name'] !== '')
            ->values();
    }
}
