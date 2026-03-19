<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AdminStudentController — Admin-level CRUD for student records.
 *
 * Unlike StudentController (teacher-scoped), this controller exposes
 * all students to the admin user without ownership filters.
 */
class AdminStudentController extends Controller
{
    /* ====================================================================== */
    /*  Read                                                                   */
    /* ====================================================================== */

    /**
     * Return a paginated list of students, optionally filtered by search term.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Student::query()->orderBy('last_name')->orderBy('first_name');

        $search = $request->input('search');
        if ($search && is_string($search)) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('student_number', 'like', $term)
                  ->orWhere('grade_section', 'like', $term);
            });
        }

        $perPage  = max(5, min(100, (int) $request->input('per_page', 15)));
        $students = $query->paginate($perPage);
        $items    = $students->getCollection()->map(fn (Student $s) => $this->studentToArray($s));

        return response()->json([
            'data'         => $items,
            'current_page' => $students->currentPage(),
            'last_page'    => $students->lastPage(),
            'per_page'     => $students->perPage(),
            'total'        => $students->total(),
        ]);
    }

    /* ====================================================================== */
    /*  Write                                                                  */
    /* ====================================================================== */

    /** Create a new student record. */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name'     => ['required', 'string', 'max:255'],
            'last_name'      => ['required', 'string', 'max:255'],
            'middle_name'    => ['nullable', 'string', 'max:255'],
            'student_number' => ['required', 'string', 'max:64', 'unique:students,student_number'],
            'grade_section'  => ['nullable', 'string', 'max:64'],
            'grade'          => ['nullable', 'string', 'max:32'],
            'section'        => ['nullable', 'string', 'max:32'],
            'guardian'       => ['nullable', 'string', 'max:255'],
            'guardian_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:64'],
            'notification_preference' => ['nullable', 'integer', 'in:0,1,2'],
            'school_id'      => ['nullable', 'exists:schools,id'],
        ], [
            'student_number.unique' => 'LRN already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $gradeSection = $this->resolveGradeSection($request);

        $student = Student::create([
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'middle_name'    => $request->middle_name ?: null,
            'student_number' => $request->student_number,
            'grade_section'  => $gradeSection,
            'grade'          => $request->grade ?: null,
            'section'        => $request->section ?: null,
            'guardian'       => $request->guardian ?: null,
            'guardian_email' => $request->guardian_email ?: null,
            'contact_number' => $request->contact_number ?: null,
            'notification_preference' => (int) ($request->notification_preference ?? 0),
            'school_id'      => $request->user()->school_id,
            'created_by'     => $request->user()->id,
        ]);

        return response()->json(['message' => 'Student created.', 'student' => $this->studentToArray($student)], 201);
    }

    /** Update an existing student record by ID. */
    public function update(Request $request, int $id): JsonResponse
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name'     => ['sometimes', 'required', 'string', 'max:255'],
            'last_name'      => ['sometimes', 'required', 'string', 'max:255'],
            'middle_name'    => ['nullable', 'string', 'max:255'],
            'student_number' => ['sometimes', 'required', 'string', 'max:64', 'unique:students,student_number,' . $id],
            'grade_section'  => ['nullable', 'string', 'max:64'],
            'grade'          => ['nullable', 'string', 'max:32'],
            'section'        => ['nullable', 'string', 'max:32'],
            'guardian'       => ['nullable', 'string', 'max:255'],
            'guardian_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:64'],
            'notification_preference' => ['nullable', 'integer', 'in:0,1,2'],
        ], [
            'student_number.unique' => 'LRN already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'first_name', 'last_name', 'middle_name', 'student_number',
            'grade_section', 'grade', 'section',
            'guardian', 'guardian_email', 'contact_number', 'notification_preference',
        ]);

        if ($request->hasAny(['grade', 'section', 'grade_section'])) {
            $data['grade_section'] = $this->resolveGradeSection($request);
        }

        $student->fill($data)->save();

        return response()->json(['message' => 'Student updated.', 'student' => $this->studentToArray($student)]);
    }

    /** Permanently delete a student record. */
    public function destroy(int $id): JsonResponse
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $student->delete();

        return response()->json(['message' => 'Student deleted.']);
    }

    /* ====================================================================== */
    /*  Export                                                                 */
    /* ====================================================================== */

    /**
     * Stream students as a UTF-8 CSV download.
     *
     * Respects any active search filter so admins can export filtered subsets.
     * Uses chunked processing to prevent memory exhaustion on large datasets.
     */
    public function export(Request $request): StreamedResponse
    {
        $query  = Student::query()->orderBy('last_name')->orderBy('first_name');
        $search = $request->input('search');

        if ($search && is_string($search)) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('student_number', 'like', $term)
                  ->orWhere('grade_section', 'like', $term);
            });
        }

        $response = new StreamedResponse(function () use ($query) {
            if (ob_get_length()) {
                ob_end_clean();
            }

            $handle = fopen('php://output', 'w');
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel

            fputcsv($handle, ['ID', 'LRN', 'First Name', 'Last Name', 'Middle Name', 'Grade & Section', 'Grade', 'Section', 'Guardian', 'Guardian Email', 'Contact Number']);

            $query->chunk(100, function ($students) use ($handle) {
                foreach ($students as $student) {
                    fputcsv($handle, [
                        $student->id,
                        $student->student_number,
                        $student->first_name,
                        $student->last_name,
                        $student->middle_name,
                        $student->grade_section,
                        $student->grade,
                        $student->section,
                        $student->guardian,
                        $student->guardian_email,
                        $student->contact_number,
                    ]);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="students_export.csv"');

        return $response;
    }

    /* ====================================================================== */
    /*  Private helpers                                                        */
    /* ====================================================================== */

    /** Derive grade_section from input, falling back to grade+section concatenation. */
    private function resolveGradeSection(Request $request): ?string
    {
        if ($request->grade_section) {
            return $request->grade_section;
        }

        if ($request->grade && $request->section) {
            return $request->grade . '-' . $request->section;
        }

        return $request->grade ?: null;
    }

    /** Serialize a Student model into the standard API response shape. */
    private function studentToArray(Student $student): array
    {
        return [
            'id'             => $student->id,
            'student_number' => $student->student_number,
            'first_name'     => $student->first_name,
            'last_name'      => $student->last_name,
            'middle_name'    => $student->middle_name,
            'full_name'      => trim($student->first_name . ' ' . $student->last_name),
            'grade_section'  => $student->grade_section ?? '—',
            'grade'          => $student->grade,
            'section'        => $student->section,
            'guardian'       => $student->guardian,
            'guardian_email' => $student->guardian_email,
            'notification_preference' => (int) ($student->notification_preference ?? 0),
            'contact_number' => $student->contact_number,
            'photo_path'     => $student->photo_path,
            'created_at'     => $student->created_at?->toIso8601String(),
        ];
    }
}
