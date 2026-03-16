<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\SchoolSetting;
use App\Models\SchoolYear;
use App\Models\School;
use App\Services\MailerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Jobs\SendSmsNotification;

class AttendanceController extends Controller
{
    protected MailerService $mailer;

    public function __construct(MailerService $mailer)
    {
        $this->mailer = $mailer;
    }

    public function scanPublic(Request $request): JsonResponse
    {
        try {
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
            $teacher = null;
            $person = null;
            $personType = 'student';

            if (is_numeric($input)) {
                $student = Student::find((int) $input);
            }
            if (!$student) {
                $student = Student::where('student_number', $input)->first();
            }

            if ($student) {
                $person = $student;
            } else {
                // Try finding a teacher
                $teacher = User::where('employee_id', $input)->first();
                if ($teacher) {
                    $person = $teacher;
                    $personType = 'teacher';
                }
            }

            if (!$person) {
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'Person not found.',
                ], 404);
            }

            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();
            
            // Check for duplicate scan in the last 5 seconds to prevent spam
            $recent = Attendance::where($personType === 'student' ? 'student_id' : 'scanned_by', $person->id)
                ->where('scanned_at', '>=', now()->subSeconds(5))
                ->exists();

            if ($recent) {
                return response()->json([
                    'status' => 'duplicate',
                    'message' => 'Duplicate scan. Please wait.',
                ], 422);
            }

            // Determine Session (Morning/Afternoon)
            $currentTime = now();
            $session = $currentTime->hour < 12 ? 'morning' : 'afternoon';

            // For students, check if already recorded today in this session (Duplicate logic)
            if ($personType === 'student') {
                $alreadyScanned = Attendance::where('student_id', $person->id)
                    ->whereDate('scanned_at', now()->toDateString())
                    ->where('session', $session)
                    ->exists();

                if ($alreadyScanned) {
                    return response()->json([
                        'status' => 'already_scanned',
                        'message' => 'Duplicate scan. ' . ucfirst($session) . ' entry already recorded.',
                    ], 200);
                }
            }

            $guardUser = $request->user('sanctum') ?: $this->getDefaultGuardUser();
            $scannedBy = $guardUser ? $guardUser->id : null;

            $schoolId = null;
            if ($guardUser) {
                if ($guardUser instanceof \App\Models\Teacher) {
                    $schoolId = $guardUser->school_id;
                } elseif ($guardUser->school_name) {
                    $school = \App\Models\School::where('name', 'like', '%' . $guardUser->school_name . '%')->first();
                    $schoolId = $school ? $school->id : null;
                }
            }
            
            // Fallback to student's school or first school
            if (!$schoolId) {
                $schoolId = ($personType === 'student' ? $person->school_id : null) ?: School::first()?->id;
            }

            $settings = SchoolSetting::where('school_id', $schoolId)->first();
            $schoolYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();

            $scannedAt = now();
            $status = 'on_time';
            
            if ($settings && !empty($settings->late_threshold)) {
                try {
                    $threshold = now()->setTimeFromTimeString($settings->late_threshold);
                    if ($scannedAt->greaterThan($threshold)) {
                        $status = 'late';
                    }
                } catch (\Exception $e) {
                    Log::warning('Threshold parsing failed: ' . $e->getMessage());
                }
            }

            $attendance = null;
            if ($personType === 'student') {
                $attendance = Attendance::create([
                    'student_id' => $person->id,
                    'scanned_by' => $scannedBy,
                    'scanned_at' => $scannedAt,
                    'status' => $status,
                    'session' => $session,
                    'school_id' => $schoolId,
                    'school_year_id' => ($schoolYear ? $schoolYear->id : null),
                ]);
            } else {
                // For teachers, we just log the event for now or return success
                // We'll return a mock attendance object for the frontend
                $attendance = (object)[
                    'id' => 'teacher-' . $person->id . '-' . time(),
                    'status' => 'on_time',
                    'scanned_at' => $scannedAt
                ];
            }

            // Dispatch SMS Notification for Students (Asynchronous)
            if ($personType === 'student' && ($person->contact_number || $person->emergency_contact)) {
                SendSmsNotification::dispatch($person, $scannedAt->format('h:i A'), ucfirst($session));
            }

            // Disable synchronous email to prevent long delay
            // $this->attemptEmailNotification($student, $attendance, $scannedAt);

            $photoField = $personType === 'student' ? 'photo_path' : 'profile_photo';
            $photoPath = $person->$photoField;
            $cleanPhotoPath = $photoPath ? url('storage/' . ltrim(str_replace(['public/', 'storage/'], '', $photoPath), '/')) : null;

            return response()->json([
                'status' => 'success',
                'message' => $personType === 'teacher' ? 'Teacher verified.' : 'Attendance recorded.',
                'attendance' => [
                    'id' => $attendance->id,
                    'status' => $attendance->status ?? 'on_time',
                    'scanned_at' => $scannedAt->toIso8601String(),
                ],
                'student' => [
                    'id' => $person->id,
                    'student_number' => $personType === 'student' ? $person->student_number : $person->employee_id,
                    'full_name' => $personType === 'student' ? ($person->first_name . ' ' . $person->last_name) : $person->name,
                    'first_name' => $personType === 'student' ? $person->first_name : $person->name,
                    'last_name' => $personType === 'student' ? $person->last_name : '',
                    'grade_section' => $personType === 'student' ? ($person->grade_section ?? '—') : ($person->job_title ?? 'Faculty'),
                    'photo_path' => $cleanPhotoPath,
                    'type' => $personType
                ],
                // Return fresh stats so frontend can update immediately
                'stats' => $this->calculateStats($schoolId)
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Critical error in scanPublic: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error. Please contact administrator.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function attemptEmailNotification($student, $attendance, $scannedAt)
    {
        $parentEmail = $student->guardian_email ?: $student->emergency_contact;
        if (!$parentEmail || !filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $scannedAtFormatted = $scannedAt->timezone(config('app.timezone', 'UTC'));
            $formattedTime = $scannedAtFormatted->format('F j, Y g:i A');

            $guardianName = $student->guardian ?: 'Parent/Guardian';
            $studentName = trim(
                $student->first_name . ' ' .
                (($student->middle_name ?? '') !== '' ? ($student->middle_name . ' ') : '') .
                $student->last_name
            );
            $gradeSection = $student->grade_section ?? 'N/A';

            $schoolName = config('app.name', 'School');
            $subject = "{$schoolName} - Campus Entry Notification";

            $bodyLines = [
                "Good day {$guardianName},",
                '',
                'This is to notify you of a campus entry event.',
                '',
                "Student: {$studentName}",
                "Grade/Section: {$gradeSection}",
                'Student number: ' . $student->student_number,
                "Time: {$formattedTime}",
                "Status: " . ($attendance->status === 'late' ? 'LATE' : 'ON TIME'),
                'Location: Main Gate',
                '',
                'This is an automated message. Do not reply to this email.',
            ];

            $this->mailer->sendEmail($parentEmail, $subject, implode("\n", $bodyLines));
        } catch (\Throwable $e) {
            Log::error('Failed to send attendance email', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getDefaultGuardUser(): ?User
    {
        $guardRole = Role::where('name', 'Guard')->first();
        if (!$guardRole) {
            return null;
        }
        return User::where('role_id', $guardRole->id)->first();
    }

    public function getStats(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $schoolId = null;

        if ($user) {
            if ($user instanceof \App\Models\Teacher) {
                $schoolId = $user->school_id;
            } elseif ($user->school_name) {
                $school = \App\Models\School::where('name', 'like', '%' . $user->school_name . '%')->first();
                $schoolId = $school ? $school->id : null;
            }
        }

        if (!$schoolId) {
            $schoolId = School::first()?->id;
        }

        return response()->json($this->calculateStats($schoolId));
    }

    private function calculateStats($schoolId)
    {
        if (!$schoolId) {
            return [
                'total_today' => 0,
                'present_count' => 0,
                'late_count' => 0,
                'absent_count' => 0,
                'present' => 0,
                'late' => 0,
                'absent' => 0
            ];
        }

        $today = now()->toDateString();
        
        $present = Attendance::where('school_id', $schoolId)
            ->whereDate('scanned_at', $today)
            ->where('session', 'morning')
            ->count();

        $late = Attendance::where('school_id', $schoolId)
            ->whereDate('scanned_at', $today)
            ->where('session', 'morning')
            ->where('status', 'late')
            ->count();

        // Count only students assigned to this school
        $totalStudents = Student::where('school_id', $schoolId)->count();
        $absent = max(0, $totalStudents - $present);

        return [
            'total_today' => $totalStudents,
            'present_count' => $present,
            'late_count' => $late,
            'absent_count' => $absent,
            // Legacy/Frontend consistency
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
        ];
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
                    'first_name' => $s ? $s->first_name : '',
                    'last_name' => $s ? $s->last_name : '',
                    'grade_section' => $s ? ($s->grade_section ?? '—') : '—',
                    'time_in' => $a->scanned_at->toIso8601String(),
                    'status' => $a->status ?? 'on_time',
                    'photo_path' => ($s && $s->photo_path) ? url('storage/' . ltrim(str_replace(['public/', 'storage/'], '', $s->photo_path), '/')) : null,
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
                'photo_path' => $student->photo_path ? url('storage/' . ltrim(str_replace(['public/', 'storage/'], '', $student->photo_path), '/')) : null,
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
