<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSubjectController extends Controller
{
    private function schoolScope(Request $request): ?int
    {
        return $request->user()?->school_id;
    }

    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->schoolScope($request);
        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

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
        $exists = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $request->name))])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Subject already exists.'], 422);
        }

        $subject = Subject::create([
            'name' => trim((string) $request->name),
            'school_id' => $schoolId,
        ]);

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
        $exists = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('id', '!=', $subject->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Subject already exists.'], 422);
        }

        $subject->update(['name' => $name]);

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

        $subject->delete();

        return response()->json(['message' => 'Subject deleted.']);
    }
}

