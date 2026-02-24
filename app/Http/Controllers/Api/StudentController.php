<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Student::query();

        if ($user->role->name === 'Teacher') {
            $query->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)->orWhere('created_by', $user->id);
            });
        }

        $search = $request->input('search');
        if ($search && is_string($search)) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('middle_name', 'like', $term)
                    ->orWhere('student_number', 'like', $term)
                    ->orWhere('grade_section', 'like', $term)
                    ->orWhere('grade', 'like', $term)
                    ->orWhere('section', 'like', $term);
            });
        }

        $perPage = max(5, min(100, (int) $request->input('per_page', 15)));
        $students = $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage);

        $items = $students->getCollection()->map(function (Student $s) {
            $fullName = trim($s->first_name . ' ' . ($s->middle_name ?? '') . ' ' . $s->last_name);
            return [
                'id' => $s->id,
                'student_number' => $s->student_number,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'middle_name' => $s->middle_name,
                'full_name' => $fullName ?: $s->first_name . ' ' . $s->last_name,
                'grade_section' => $s->grade_section ?? '—',
                'grade' => $s->grade,
                'section' => $s->section,
                'guardian' => $s->guardian,
                'contact_number' => $s->contact_number,
                'created_at' => $s->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'current_page' => $students->currentPage(),
            'last_page' => $students->lastPage(),
            'per_page' => $students->perPage(),
            'total' => $students->total(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'student_number' => ['required', 'string', 'max:64', 'unique:students,student_number'],
            'grade_section' => ['nullable', 'string', 'max:64'],
            'grade' => ['nullable', 'string', 'max:32'],
            'section' => ['nullable', 'string', 'max:32'],
            'guardian' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:64'],
            'photo' => ['nullable', 'file', 'mimes:png', 'max:5120'],
        ], [
            'student_number.unique' => 'LRN already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $gradeSection = $request->grade_section ?: ($request->grade && $request->section
            ? $request->grade . '-' . $request->section
            : ($request->grade ?? null));

        $student = Student::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name ?: null,
            'student_number' => $request->student_number,
            'grade_section' => $gradeSection,
            'grade' => $request->grade ?: null,
            'section' => $request->section ?: null,
            'guardian' => $request->guardian ?: null,
            'contact_number' => $request->contact_number ?: null,
            'emergency_contact' => $request->contact_number ?: null,
            'teacher_id' => $user->role->name === 'Teacher' ? $user->id : null,
            'created_by' => $user->id,
        ]);

        if ($request->hasFile('photo')) {
            $this->saveStudentPhoto($request->file('photo'), $student->student_number);
        }

        $fullName = trim($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . $student->last_name);
        return response()->json([
            'message' => 'Student created.',
            'student' => $this->studentToArray($student, $fullName),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        if ($user->role->name === 'Teacher') {
            $allowed = ($student->teacher_id === $user->id || $student->created_by === $user->id);
            if (!$allowed) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'student_number' => ['sometimes', 'required', 'string', 'max:64', 'unique:students,student_number,' . $id],
            'grade_section' => ['nullable', 'string', 'max:64'],
            'grade' => ['nullable', 'string', 'max:32'],
            'section' => ['nullable', 'string', 'max:32'],
            'guardian' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:64'],
            'photo' => ['nullable', 'file', 'mimes:png', 'max:5120'],
        ], [
            'student_number.unique' => 'LRN already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'first_name', 'last_name', 'middle_name', 'student_number',
            'grade_section', 'grade', 'section', 'guardian', 'contact_number',
        ]);
        if ($request->has('grade_section') && $request->grade_section === '') {
            $data['grade_section'] = null;
        }
        if ($request->has('contact_number')) {
            $data['emergency_contact'] = $request->contact_number ?: null;
        }
        $gradeSection = $request->grade_section ?: ($request->grade && $request->section
            ? $request->grade . '-' . $request->section
            : ($request->grade ?? null));
        if ($request->has('grade') || $request->has('section')) {
            $data['grade_section'] = $gradeSection;
        }
        $student->fill($data);
        $student->save();

        if ($request->hasFile('photo')) {
            $this->saveStudentPhoto($request->file('photo'), $student->student_number);
        }

        $fullName = trim($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . $student->last_name);
        return response()->json([
            'message' => 'Student updated.',
            'student' => $this->studentToArray($student, $fullName),
        ]);
    }

    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }
        if ($user->role->name === 'Teacher') {
            $allowed = ($student->teacher_id === $user->id || $student->created_by === $user->id);
            if (!$allowed) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }
        $request->validate(['photo' => ['required', 'file', 'mimes:png', 'max:5120']]);
        $this->saveStudentPhoto($request->file('photo'), $student->student_number);
        return response()->json(['message' => 'Photo updated.']);
    }

    private function saveStudentPhoto(\Illuminate\Http\UploadedFile $file, string $studentNumber): void
    {
        $dir = public_path('school');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        // Always save as .png so IdCardController can find and render it correctly
        $path = $dir . '/' . $studentNumber . '.png';
        // Remove old jpg/png versions first
        foreach (['.jpg', '.jpeg', '.png'] as $ext) {
            $old = $dir . '/' . $studentNumber . $ext;
            if (file_exists($old)) {
                @unlink($old);
            }
        }
        $file->move($dir, $studentNumber . '.png');
    }

    private function studentToArray(Student $student, ?string $fullName = null): array
    {
        $fullName = $fullName ?: trim($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . $student->last_name);
        return [
            'id' => $student->id,
            'student_number' => $student->student_number,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'middle_name' => $student->middle_name,
            'full_name' => $fullName ?: $student->first_name . ' ' . $student->last_name,
            'grade_section' => $student->grade_section ?? '—',
            'grade' => $student->grade,
            'section' => $student->section,
            'guardian' => $student->guardian,
            'contact_number' => $student->contact_number,
            'created_at' => $student->created_at?->toIso8601String(),
        ];
    }

    public function verify(string $student_number)
    {
        $student = Student::where('student_number', $student_number)->firstOrFail();

        if (request()->wantsJson()) {
            return response()->json([
                'student' => $this->studentToArray($student),
            ]);
        }

        return view('verify_student', [
            'student' => $student,
        ]);
    }
}
