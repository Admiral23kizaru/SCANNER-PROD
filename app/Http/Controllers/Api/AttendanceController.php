<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsNotification;
use App\Jobs\SendEmailNotification;
use App\Models\Attendance;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Services\MailerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * AttendanceController — manages QR scan events and attendance statistics.
 *
 * Handles both the public Guard-facing scanner and the teacher-facing internal scanner.
 * Email notifications are disabled in favour of async SMS dispatching via SendSmsNotification job.
 */
class AttendanceController extends Controller
{
    public function __construct(protected readonly ?MailerService $mailer = null)
    {
    }

    /* ====================================================================== */
    /*  Public scanner                                                         */
    /* ====================================================================== */

    /**
     * Target Role: Attendance Guard / Scanner UI.
     * Source: QR Scan Event.
     * Destination: Attendance Table & Notification API.
     * Function: High-speed attendance logging with background notification routing.
     *
     * Action: Bypassing duplicate SMS check for testing; Formatting number to 63 prefix.
     * Source: AttendanceController@scan
     *
     * Note: Priority is Scanner Speed. SMS delivery is secondary to logging.
     *
     * @param \Illuminate\Http\Request $request
     *        - student_number: The ID (LRN) of the student from the QR scanner.
     *        - session: (morning, lunch_out, etc.). 
     * @return \Illuminate\Http\JsonResponse
     */
    public function scan(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_number' => ['required', 'string'],
                'session'        => ['required', 'string'],
            ], [
                'student_number.required' => 'Invalid QR code.',
                'session.required'        => 'Session determines the log period.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'invalid',
                    'message' => 'Invalid QR code.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $input  = trim((string) $request->student_number);
            $person = null;
            $personType = 'student';

            // Resolve person from QR input: always prioritize student_number (LRN).
            $student = Student::where('student_number', $input)
                ->orWhere('id', $input)
                ->first();

            if ($student) {
                $person = $student;
            } else {
                $teacher = User::where('employee_id', $input)->first();
                if ($teacher) {
                    $person     = $teacher;
                    $personType = 'teacher';
                }
            }

            if (!$person) {
                return response()->json(['status' => 'invalid', 'message' => 'Person not found.'], 404);
            }

            // Anti-spam: reject duplicate scans within 5 seconds
            $recentScan = Attendance::where(
                $personType === 'student' ? 'student_id' : 'scanned_by',
                $person->id
            )->where('scanned_at', '>=', now()->subSeconds(5))->exists();

            if ($recentScan) {
                return response()->json(['status' => 'duplicate', 'message' => 'Duplicate scan. Please wait.'], 422);
            }

            // Determine session from the request payload (avoid Request::session() name collision)
            $session = (string) $request->input('session');

            // Prevent double-entry for same session per student
            if ($personType === 'student') {
                $alreadyScanned = Attendance::where('student_id', $person->id)
                    ->whereDate('scanned_at', now()->toDateString())
                    ->where('session', $session)
                    ->exists();

                if ($alreadyScanned) {
                    return response()->json([
                        'status'  => 'already_scanned',
                        'message' => 'Duplicate scan. ' . ucfirst($session) . ' entry already recorded.',
                    ], 200);
                }
            }

            $guardUser  = $request->user('sanctum') ?? $this->getDefaultGuardUser();
            $scannedBy  = $guardUser?->id;
            $schoolId   = $this->resolveSchoolId($guardUser, $person, $personType);
            $settings   = SchoolSetting::where('school_id', $schoolId)->first();
            $schoolYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
            $scannedAt  = now();
            $status     = $this->resolveStatus($settings, $scannedAt);

            // Record attendance only for students
            $notificationMethodUsed = 'None';
            $notificationPreference = null;
            $notificationPayload = null;
            $formattedNumber = null;
            if ($personType === 'student') {
                $attendance = Attendance::create([
                    'student_id'     => $person->id,
                    'scanned_by'     => $scannedBy,
                    'scanned_at'     => $scannedAt,
                    'status'         => $status,
                    'session'        => $session,
                    'school_id'      => $schoolId,
                    'school_year_id' => $schoolYear?->id,
                ]);

                // Prepare notification data, but do NOT block the scanner response.
                $notificationPreference = $person->notification_preference ?? 'email';
                $notificationPayload = [
                    'time'    => $scannedAt->format('h:i A'),
                    'session' => ucfirst((string) $session),
                ];
                if ($notificationPreference === 'sms') {
                    /**
                     * Source: Student DB; Destination: Semaphore API; Action: Converting local 09 format to international 63 format for carrier compatibility.
                     */
                    $guardianContact = $person->contact_number ?: $person->emergency_contact;
                    if (!empty($guardianContact)) {
                        $digits = preg_replace('/\D/', '', (string) $guardianContact);
                        // If it starts with "0", replace leading "0" with "63" (e.g., 0946... -> 63946...).
                        $formattedNumber = preg_replace('/^0/', '63', $digits);
                        // If it already starts with "63", keep as-is (preg_replace won't change it).
                    }
                    $notificationMethodUsed = !empty($formattedNumber) ? 'SMS_QUEUED' : 'None';
                } else {
                    $notificationMethodUsed = ($person->guardian_email) ? 'EMAIL_QUEUED' : 'None';
                }
            } else {
                // Teachers are verified — mock attendance object for frontend compatibility
                $attendance = (object) [
                    'id'         => 'teacher-' . $person->id . '-' . time(),
                    'status'     => 'on_time',
                    'scanned_at' => $scannedAt,
                ];
            }

            $photoPath  = $this->resolvePhotoUrl($person, $personType);

            // Return immediately once the attendance row is confirmed saved.
            $response = response()->json([
                'status'     => 'success',
                'message'    => $personType === 'teacher' ? 'Teacher verified.' : 'Attendance recorded.',
                'notification_method' => $notificationMethodUsed ?? 'None',
                'attendance' => [
                    'id'         => $attendance->id,
                    'status'     => $attendance->status ?? 'on_time',
                    'scanned_at' => $scannedAt->toIso8601String(),
                ],
                'student' => [
                    'id'            => $person->id,
                    'student_number' => $personType === 'student' ? $person->student_number : $person->employee_id,
                    'full_name'     => $personType === 'student' ? ($person->first_name . ' ' . $person->last_name) : $person->name,
                    'first_name'    => $personType === 'student' ? $person->first_name : $person->name,
                    'last_name'     => $personType === 'student' ? $person->last_name : '',
                    'grade_section' => $personType === 'student' ? ($person->grade_section ?? '—') : ($person->job_title ?? 'Faculty'),
                    'photo_path'    => $photoPath,
                    'type'          => $personType,
                ],
                'stats' => $this->calculateStats($schoolId),
            ], 201);

            // Dispatch notifications after the response is sent (scanner speed first).
            if ($personType === 'student' && $notificationPreference && is_array($notificationPayload)) {
                $student = $person; // clarity: $person is Student in this branch
                app()->terminating(function () use ($student, $notificationPreference, $notificationPayload, $formattedNumber) {
                    try {
                        if ($notificationPreference === 'sms') {
                            // Validation: do not attempt SMS when no number is available.
                            if (!empty($formattedNumber)) {
                                SendSmsNotification::dispatch($student, $notificationPayload['time'], $notificationPayload['session'], $formattedNumber)
                                    ->afterResponse();
                            }
                            return;
                        }
                        if ($student->guardian_email) {
                            SendEmailNotification::dispatch($student, $notificationPayload['time'], $notificationPayload['session'])->afterResponse();
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Attendance notification dispatchAfterResponse failed: ' . $e->getMessage());
                    }
                });
            }

            return $response;

        } catch (\Throwable $e) {
            Log::error('Critical error in scanPublic: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal Server Error. Please contact administrator.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /** Return the 100 most recent attendance records for today (public facing terminal). */
    public function publicRecent(): JsonResponse
    {
        $items = Attendance::with('student')
            ->whereDate('scanned_at', now()->toDateString())
            ->orderByDesc('scanned_at')
            ->limit(100)
            ->get()
            ->map(fn (Attendance $a) => $this->formatAttendanceRow($a));

        return response()->json(['data' => $items]);
    }

    /** Return today's attendance stats for the public Guard Terminal (no auth required). */
    public function publicStats(): JsonResponse
    {
        $today   = now()->toDateString();
        $present = Attendance::whereDate('scanned_at', $today)->where('session', 'morning')->count();
        $late    = Attendance::whereDate('scanned_at', $today)->where('session', 'morning')->where('status', 'late')->count();
        $total   = Student::count();
        $absent  = max(0, $total - $present);

        return response()->json([
            'total_today'   => $total,
            'present_count' => $present,
            'late_count'    => $late,
            'absent_count'  => $absent,
        ]);
    }

    /* ====================================================================== */
    /*  Teacher-side scanner (authenticated)                                  */
    /* ====================================================================== */

    /**
     * Record attendance from the Teacher Dashboard QR scanner (authenticated route).
     *
     * -----------------------------------------------------------------------
     * -----------------------------------------------------------------------
     * Target Role  : Attendance Guard / Parent.
     * Source       : QR Scanner UI
     * Function     : Entry point for student attendance logging and notification triggering.
     * Destination  : AttendanceController@scan
     * -----------------------------------------------------------------------
     *
     * Differences from scanPublic():
     *   - Requires a valid Sanctum token (Teacher role).
     *   - Uses a 2-second rapid-fire cooldown instead of 5 seconds.
     *   - No session-guard (allows AM + PM entries for the same student).
     *   - Still dispatches async SMS/Email based on notification_preference.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function teacherScan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'string'],
        ], [
            'student_id.required' => 'Invalid QR code.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid QR code.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $input   = trim($request->student_number ?? $request->student_id);
        $student = Student::where('student_number', $input)
            ->orWhere('id', $input)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        // Prevent rapid double-scan by the same teacher within 2 seconds
        $recent = Attendance::where('student_id', $student->id)
            ->where('scanned_by', $request->user()->id)
            ->where('scanned_at', '>=', now()->subSeconds(2))
            ->exists();

        if ($recent) {
            return response()->json(['message' => 'Duplicate scan. Please wait before scanning again.'], 422);
        }

        // Resolve school & year context (same helpers used by scanPublic)
        $teacher    = $request->user();
        $scannedAt  = now();
        $session    = $scannedAt->hour < 12 ? 'morning' : 'afternoon';
        $schoolId   = $this->resolveSchoolId($teacher, $student, 'student');
        $settings   = SchoolSetting::where('school_id', $schoolId)->first();
        $schoolYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
        $status     = $this->resolveStatus($settings, $scannedAt);

        $attendance = Attendance::create([
            'student_id'     => $student->id,
            'scanned_by'     => $teacher->id,
            'scanned_at'     => $scannedAt,
            'status'         => $status,
            'session'        => $session,
            'school_id'      => $schoolId,
            'school_year_id' => $schoolYear?->id,
        ]);

        // Queue notifications after response (teacher scan should remain fast).
        $pref = $student->notification_preference ?? 'email';
        $timeStr = $scannedAt->format('h:i A');
        $sessionLabel = ucfirst($session);
        $formattedNumber = null;
        if ($pref === 'sms') {
            /**
             * Source: Student DB; Destination: Semaphore API; Action: Converting local 09 format to international 63 format for carrier compatibility.
             */
            $guardianContact = $student->contact_number ?: $student->emergency_contact;
            if (!empty($guardianContact)) {
                $digits = preg_replace('/\D/', '', (string) $guardianContact);
                $formattedNumber = preg_replace('/^0/', '63', $digits);
            }
        }
        app()->terminating(function () use ($student, $pref, $timeStr, $sessionLabel, $formattedNumber) {
            try {
                if ($pref === 'sms') {
                    if (!empty($formattedNumber)) {
                        SendSmsNotification::dispatch($student, $timeStr, $sessionLabel, $formattedNumber)->afterResponse();
                    }
                    return;
                }
                if ($student->guardian_email) {
                    SendEmailNotification::dispatch($student, $timeStr, $sessionLabel)->afterResponse();
                }
            } catch (\Throwable $e) {
                Log::warning('Teacher scan notification dispatchAfterResponse failed: ' . $e->getMessage());
            }
        });

        $student->load('teacher');

        return response()->json([
            'message'    => 'Attendance recorded.',
            'attendance' => [
                'id'         => $attendance->id,
                'status'     => $attendance->status,
                'scanned_at' => $attendance->scanned_at->toIso8601String(),
            ],
            'student' => [
                'id'            => $student->id,
                'student_number' => $student->student_number,
                'full_name'     => $student->first_name . ' ' . $student->last_name,
                'grade_section' => $student->grade_section ?? '—',
                'photo_path'    => $this->resolvePhotoUrl($student, 'student'),
            ],
        ], 201);
    }


    /** Return attendance records scanned by the current teacher. */
    public function recent(Request $request): JsonResponse
    {
        $items = Attendance::with('student')
            ->where('scanned_by', $request->user()->id)
            ->orderByDesc('scanned_at')
            ->limit(100)
            ->get()
            ->map(fn (Attendance $a) => [
                'id'            => $a->id,
                'full_name'     => $a->student ? ($a->student->first_name . ' ' . $a->student->last_name) : '—',
                'grade_section' => $a->student?->grade_section ?? '—',
                'time_in'       => $a->scanned_at->toIso8601String(),
            ]);

        return response()->json(['data' => $items]);
    }

    /* ====================================================================== */
    /*  Stats                                                                  */
    /* ====================================================================== */

    /** Return live attendance stats scoped to the requesting user's school. */
    public function getStats(Request $request): JsonResponse
    {
        $user     = $request->user('sanctum');
        $schoolId = $this->resolveSchoolId($user, null, 'student');

        return response()->json($this->calculateStats($schoolId));
    }

    /* ====================================================================== */
    /*  Private helpers                                                        */
    /* ====================================================================== */

    /**
     * Resolve school_id from the guard user, then from the student, then fallback.
     */
    private function resolveSchoolId(?User $guardUser, mixed $person, string $personType): ?int
    {
        if ($guardUser) {
            if ($guardUser instanceof \App\Models\Teacher && $guardUser->school_id) {
                return $guardUser->school_id;
            }
            if ($guardUser->school_name) {
                $school = School::where('name', 'like', '%' . $guardUser->school_name . '%')->first();
                if ($school) {
                    return $school->id;
                }
            }
        }

        if ($personType === 'student' && $person?->school_id) {
            return $person->school_id;
        }

        return School::first()?->id;
    }

    /**
     * Determine attendance status (on_time / late) based on school settings.
     */
    private function resolveStatus(?SchoolSetting $settings, \Carbon\Carbon $scannedAt): string
    {
        if ($settings && !empty($settings->late_threshold)) {
            try {
                $threshold = now()->setTimeFromTimeString($settings->late_threshold);
                if ($scannedAt->greaterThan($threshold)) {
                    return 'late';
                }
            } catch (\Exception $e) {
                Log::warning('Late threshold parsing failed: ' . $e->getMessage());
            }
        }

        return 'on_time';
    }

    /**
     * Build a normalized public URL for the person's photo.
     */
    private function resolvePhotoUrl(mixed $person, string $personType): ?string
    {
        $field     = $personType === 'student' ? 'photo_path' : 'profile_photo';
        $photoPath = $person->$field ?? null;

        return $photoPath
            ? url('storage/' . ltrim(str_replace(['public/', 'storage/'], '', $photoPath), '/'))
            : null;
    }

    /** Compute today's present / late / absent counts for a given school. */
    private function calculateStats(?int $schoolId): array
    {
        if (!$schoolId) {
            return [
                'total_today'   => 0,
                'present_count' => 0,
                'late_count'    => 0,
                'absent_count'  => 0,
                'present'       => 0,
                'late'          => 0,
                'absent'        => 0,
            ];
        }

        $today   = now()->toDateString();
        $present = Attendance::where('school_id', $schoolId)->whereDate('scanned_at', $today)->where('session', 'morning')->count();
        $late    = Attendance::where('school_id', $schoolId)->whereDate('scanned_at', $today)->where('session', 'morning')->where('status', 'late')->count();
        $total   = Student::where('school_id', $schoolId)->count();
        $absent  = max(0, $total - $present);

        return [
            'total_today'   => $total,
            'present_count' => $present,
            'late_count'    => $late,
            'absent_count'  => $absent,
            'present'       => $present,
            'late'          => $late,
            'absent'        => $absent,
        ];
    }

    /** Find the fallback guard account when no authenticated user is available. */
    private function getDefaultGuardUser(): ?User
    {
        $guardRole = Role::where('name', 'Guard')->first();

        return $guardRole ? User::where('role_id', $guardRole->id)->first() : null;
    }

    /** Format a single Attendance record for the recent-scan feed. */
    private function formatAttendanceRow(Attendance $a): array
    {
        $s = $a->student;

        return [
            'id'            => $a->id,
            'full_name'     => $s ? ($s->first_name . ' ' . $s->last_name) : '—',
            'first_name'    => $s?->first_name ?? '',
            'last_name'     => $s?->last_name ?? '',
            'grade_section' => $s?->grade_section ?? '—',
            'time_in'       => $a->scanned_at->toIso8601String(),
            'status'        => $a->status ?? 'on_time',
            'photo_path'    => ($s?->photo_path)
                ? url('storage/' . ltrim(str_replace(['public/', 'storage/'], '', $s->photo_path), '/'))
                : null,
        ];
    }
}
