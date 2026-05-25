<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return response()->json([
            'data' => $subjects,
            'areas' => $this->subjectAreaNames(),
        ]);
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

        if (! $this->isAllowedSubjectArea($name)) {
            return response()->json(['message' => 'Subject must come from tbl_subject_area.'], 422);
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
        if (! $this->isAllowedSubjectArea($name)) {
            return response()->json(['message' => 'Subject must come from tbl_subject_area.'], 422);
        }

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

    public function subjectAreas(): JsonResponse
    {
        $areas = $this->subjectAreaNames();

        return response()->json([
            'data' => $areas,
            'areas' => $areas,
        ]);
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

    private function subjectAreaNames()
    {
        $connection = 'ehris';

        try {
            if (! Schema::connection($connection)->hasTable('tbl_subject_area')) {
                Log::warning('tbl_subject_area not found on ehris connection.', [
                    'method' => __METHOD__,
                    'database' => config('database.connections.ehris.database'),
                ]);

                return collect();
            }

            $column = 'subject_area';
            if (! Schema::connection($connection)->hasColumn('tbl_subject_area', $column)) {
                $column = Schema::connection($connection)->hasColumn('tbl_subject_area', 'name') ? 'name' : null;
            }

            if ($column === null) {
                Log::warning('tbl_subject_area has no subject_area or name column on ehris.', [
                    'method' => __METHOD__,
                ]);

                return collect();
            }

            return DB::connection($connection)
                ->table('tbl_subject_area')
                ->whereRaw("TRIM(COALESCE({$column}, '')) <> ''")
                ->orderBy($column)
                ->pluck($column)
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->values();
        } catch (Throwable $exception) {
            Log::error('Admin subject area lookup failed.', [
                'method' => __METHOD__,
                'connection' => $connection,
                'table' => 'tbl_subject_area',
                'error' => $exception->getMessage(),
            ]);

            return collect();
        }
    }

    private function isAllowedSubjectArea(string $name): bool
    {
        $normalized = $this->normalizeSubjectName($name);

        return $this->subjectAreaNames()
            ->contains(fn ($area) => $this->normalizeSubjectName((string) $area) === $normalized);
    }
}
