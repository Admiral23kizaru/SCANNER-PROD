<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\MailerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    protected MailerService $mailer;

    public function __construct(MailerService $mailer)
    {
        $this->mailer = $mailer;
    }

    public function scanPublic(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'string'],
        ], [
            'student_id.required' => 'Invalid QR code.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Invalid QR code.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $input = trim((string) $request->student_id);
        $student = null;

        if (is_numeric($input)) {
            $student = Student::find((int) $input);
        }
        if (!$student) {
            $student = Student::where('student_number', $input)->first();
        }

        if (!$student) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Student not found.',
            ], 404);
        }

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $alreadyToday = Attendance::where('student_id', $student->id)
            ->whereBetween('scanned_at', [$todayStart, $todayEnd])
            ->exists();

        if ($alreadyToday) {
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Attendance already recorded today.',
            ], 422);
        }

        $guardUser = $this->getDefaultGuardUser();
        if (!$guardUser) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Guard terminal not configured.',
            ], 500);
        }

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'scanned_by' => $guardUser->id,
            'scanned_at' => now(),
        ]);

        // Attempt to notify parent/guardian via email (non-blocking for API response)
        // Prefer explicit parent_email; fall back to emergency_contact for older data.
        $parentEmail = $student->parent_email ?: $student->emergency_contact;
        if ($parentEmail && filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                $scannedAt = $attendance->scanned_at->timezone(config('app.timezone'));
                $formattedTime = $scannedAt->format('M d, Y h:i A');

                $guardianName = $student->guardian ?: 'Parent/Guardian';
                $studentName = trim($student->first_name . ' ' . $student->last_name);
                $gradeSection = $student->grade_section ?? 'N/A';

                $subject = 'Attendance notification for ' . $studentName;

                $bodyLines = [
                    'Good day ' . $guardianName . ',',
                    '',
                    $studentName . ' has been recorded as present at the school gate.',
                    '',
                    'Details:',
                    ' - Time in: ' . $formattedTime,
                    ' - Grade/Section: ' . $gradeSection,
                    ' - Student number: ' . $student->student_number,
                    '',
                    'This is an automated message generated when the QR code was scanned at the school entrance.',
                ];

                $this->mailer->sendEmail($parentEmail, $subject, implode("\n", $bodyLines));
            } catch (\Throwable $e) {
                Log::error('Failed to send attendance email', [
                    'student_id' => $student->id,
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance recorded.',
            'attendance' => [
                'id' => $attendance->id,
                'scanned_at' => $attendance->scanned_at->toIso8601String(),
            ],
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'full_name' => $student->first_name . ' ' . $student->last_name,
                'grade_section' => $student->grade_section ?? '—',
            ],
        ], 201);
    }

    private function getDefaultGuardUser(): ?User
    {
        $guardRole = Role::where('name', 'Guard')->first();
        if (!$guardRole) {
            return null;
        }
        return User::where('role_id', $guardRole->id)->first();
    }

    public function publicRecent(): JsonResponse
    {
        $items = Attendance::with('student')
            ->whereDate('scanned_at', now()->toDateString())
            ->orderByDesc('scanned_at')
            ->limit(100)
            ->get()
            ->map(function (Attendance $a) {
                $s = $a->student;
                return [
                    'id' => $a->id,
                    'full_name' => $s ? $s->first_name . ' ' . $s->last_name : '—',
                    'grade_section' => $s ? ($s->grade_section ?? '—') : '—',
                    'time_in' => $a->scanned_at->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }

    public function scan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'string'],
        ], [
            'student_id.required' => 'Invalid QR code.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid QR code.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $input = trim($request->student_id);
        $student = null;

        if (is_numeric($input)) {
            $student = Student::find((int) $input);
        }

        if (!$student) {
            $student = Student::where('student_number', $input)->first();
        }

        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }

        $recent = Attendance::where('student_id', $student->id)
            ->where('scanned_by', $request->user()->id)
            ->where('scanned_at', '>=', now()->subSeconds(2))
            ->exists();

        if ($recent) {
            return response()->json([
                'message' => 'Duplicate scan. Please wait before scanning again.',
            ], 422);
        }

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'scanned_by' => $request->user()->id,
            'scanned_at' => now(),
        ]);

        $student->load('teacher');

        return response()->json([
            'message' => 'Attendance recorded.',
            'attendance' => [
                'id' => $attendance->id,
                'scanned_at' => $attendance->scanned_at->toIso8601String(),
            ],
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'full_name' => $student->first_name . ' ' . $student->last_name,
                'grade_section' => $student->grade_section ?? '—',
            ],
        ], 201);
    }

    public function recent(Request $request): JsonResponse
    {
        $items = Attendance::with('student')
            ->where('scanned_by', $request->user()->id)
            ->orderByDesc('scanned_at')
            ->limit(100)
            ->get()
            ->map(function (Attendance $a) {
                $s = $a->student;
                return [
                    'id' => $a->id,
                    'full_name' => $s ? $s->first_name . ' ' . $s->last_name : '—',
                    'grade_section' => $s ? ($s->grade_section ?? '—') : '—',
                    'time_in' => $a->scanned_at->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }
}
