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
use App\Services\SchoolResolver;
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
    public function __construct(
        protected readonly SchoolResolver $schools,
        protected readonly ?MailerService $mailer = null
    )
    {
    }

    /* ====================================================================== */
    /*  Public scanner                                                         */
    /* ====================================================================== */

    /**
     * PURPOSE: Record a guard-terminal scan and write attendance for the correct school context.
     * WHY: deped_id is authoritative for which school is scanned; the Bearer token must match that school when present.
     * LIMITATION: school_name from the client is ignored for resolution (display-only elsewhere).
     *
     * @param \Illuminate\Http\Request $request
     *        - student_number: The ID (LRN) of the student from the QR scanner.
     *        - session: (morning, lunch_out, etc.).
     * @return \Illuminate\Http\JsonResponse
     */
    public function scanPublic(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id'  => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9\-]+$/'],
                'deped_id'    => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]+$/'],
                'school_name' => ['nullable', 'string', 'max:255'],
                'session'     => ['required', 'string', 'max:64'],
            ], [
                'student_id.required' => 'Invalid QR code.',
                'deped_id.required'   => 'School missing from terminal config.',
                'session.required'    => 'Session determines the log period.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'invalid',
                    'message' => 'Invalid QR code or School config.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $school = $this->resolveScannerSchoolContext($request, true);
            if (!$school) {
                return response()->json([
                    'message' => 'Invalid school context or DepEd ID does not match this terminal session.',
                ], 403);
            }

            $schoolId = $school->id;
            $input    = trim((string) $request->student_id);
            $person   = null;
            $personType = 'student';

            // Resolve person from QR input scoped to the terminal's school
            $student = Student::where('school_id', $schoolId)
                ->where(function ($query) use ($input) {
                    $query->where('student_number', $input)
                          ->orWhere('id', $input);
                })
                ->first();

            if ($student) {
                $person = $student;
            } else {
                $teacher = User::where('school_id', $schoolId)
                    ->where('employee_id', $input)
                    ->first();
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
            // schoolId is already fetched from request->input('school_id')
            $settings   = SchoolSetting::where('school_id', $schoolId)->first();
            $schoolYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->first();
            $scannedAt  = now();
            $status     = $this->resolveStatus($settings, $scannedAt);

            // Record attendance only for students
            $notificationMethodUsed = 'None';
            $notificationPref       = 0; // default: No SMS
            $formattedNumber        = null;
            $scanTime               = null;
            $scanSession            = null;

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

                $notificationPref = (int) ($person->notification_preference ?? 0);
                $scanTime    = $scannedAt->format('h:i A');
                $scanSession = ucfirst((string) $session);

                // Resolve and normalise the guardian's phone number for SMS
                $guardianContact = $person->contact_number ?: $person->emergency_contact;
                if (!empty($guardianContact)) {
                    $digits = preg_replace('/\D/', '', (string) $guardianContact);
                    $formattedNumber = preg_replace('/^0/', '63', $digits);
                    if (str_starts_with($formattedNumber, '9') && strlen($formattedNumber) === 10) {
                        $formattedNumber = '63' . $formattedNumber;
                    }
                }

                // Determine what notifications will be dispatched
                $willSendEmail = !empty($person->guardian_email);
                $willSendSms   = false;

                if ($notificationPref === 2) {
                    // VIP: SMS on every scan
                    $willSendSms = !empty($formattedNumber);
                } elseif ($notificationPref === 1) {
                    // Regular: SMS once per day (tracked via last_sms_sent_date in DB)
                    $todayStr = $scannedAt->toDateString();
                    $lastSent = $person->last_sms_sent_date
                        ? (is_string($person->last_sms_sent_date) ? $person->last_sms_sent_date : $person->last_sms_sent_date->format('Y-m-d'))
                        : null;
                    $willSendSms = !empty($formattedNumber) && ($lastSent !== $todayStr);

                    // Mark the date now so concurrent scans don't double-send
                    if ($willSendSms) {
                        $person->last_sms_sent_date = $todayStr;
                        $person->save();
                        Log::info("Regular SMS: marked last_sms_sent_date={$todayStr} for student {$person->id}");
                    } else {
                        Log::info("Regular SMS: already sent today for student {$person->id}");
                    }
                }

                // Build notification method label for the scan response
                $methods = [];
                if ($willSendEmail) $methods[] = 'Email';
                if ($willSendSms)   $methods[] = 'SMS';
                $notificationMethodUsed = empty($methods) ? 'None' : implode('+', $methods);

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
                'notification_method' => $notificationMethodUsed,
                'attendance' => [
                    'id'         => $attendance->id,
                    'status'     => $attendance->status ?? 'on_time',
                    'scanned_at' => $scannedAt->toIso8601String(),
                ],
                'student' => [
                    'id'             => $person->id,
                    'student_number' => $personType === 'student' ? $person->student_number : $person->employee_id,
                    'full_name'      => $personType === 'student' ? ($person->first_name . ' ' . $person->last_name) : $person->name,
                    'first_name'     => $personType === 'student' ? $person->first_name : $person->name,
                    'last_name'      => $personType === 'student' ? $person->last_name : '',
                    'grade_section'  => $personType === 'student' ? ($person->grade_section ?? '—') : ($person->job_title ?? 'Faculty'),
                    'photo_path'     => $photoPath,
                    'type'           => $personType,
                ],
                'stats' => $this->calculateStats($schoolId),
            ], 201);

            // Dispatch notifications AFTER response is sent (scanner speed first).
            // Email: ALWAYS on every scan for all preferences, if guardian_email is set.
            // SMS:   Depends on notification_preference (0=none, 1=once/day, 2=every scan).
            if ($personType === 'student' && $scanTime !== null) {
                $studentSnap    = $person;
                $timeSnap       = $scanTime;
                $sessionSnap    = $scanSession;
                $numberSnap     = $formattedNumber;
                $prefSnap       = $notificationPref;
                $willSendSmsSnap   = $willSendSms  ?? false;
                $willSendEmailSnap = $willSendEmail ?? false;

                app()->terminating(function () use (
                    $studentSnap, $timeSnap, $sessionSnap, $numberSnap,
                    $prefSnap, $willSendSmsSnap, $willSendEmailSnap
                ) {
                    try {
                        // Email: unlimited, all preferences
                        if ($willSendEmailSnap) {
                            SendEmailNotification::dispatch($studentSnap, $timeSnap, $sessionSnap)->afterResponse();
                        }

                        // SMS: only when preference allows and gate conditions pass
                        if ($willSendSmsSnap) {
                            SendSmsNotification::dispatch($studentSnap, $timeSnap, $sessionSnap, $numberSnap, $prefSnap)
                                ->afterResponse();
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Attendance notification dispatch failed: ' . $e->getMessage());
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

    /**
     * PURPOSE: Return today's recent attendance feed for the guard terminal.
     * WHY: When deped_id is supplied it defines the school; authenticated tokens must match that school.
     * LIMITATION: Unauthenticated callers must pass a valid deped_id query param.
     */
    public function publicRecent(Request $request): JsonResponse
    {
        $school = $this->resolveScannerSchoolContext($request, false);

        if (!$school) {
            return response()->json([
                'message' => 'School context could not be determined.'
            ], 403);
        }

        $items = Attendance::with('student')
            ->whereDate('scanned_at', today())
            ->where('school_id', $school->id)
            ->latest('scanned_at')
            ->limit(100)
            ->get()
            ->map(fn (Attendance $a) => $this->formatAttendanceRow($a));

        return response()->json(['data' => $items]);
    }

    /**
     * PURPOSE: Return guard-terminal attendance stats for the caller's resolved school context.
     * WHY: Same deped_id + token binding rules as publicRecent for consistent isolation.
     * LIMITATION: Requires valid deped_id or an authenticated user with school_id.
     */
    public function publicStats(Request $request): JsonResponse
    {
        $school = $this->resolveScannerSchoolContext($request, false);

        if (!$school) {
            return response()->json([
                'message' => 'School context could not be determined.'
            ], 403);
        }

        $query = Attendance::whereDate('scanned_at', today())
            ->where('school_id', $school->id);

        $stats = (clone $query)->selectRaw("
            COUNT(*) as total_today,
            SUM(status = 'on_time') as on_time_count,
            SUM(status = 'late') as late_count
        ")->first();

        $enrolled = Student::where('school_id', $school->id)->count();
        $markedToday = Attendance::whereDate('scanned_at', today())
            ->where('school_id', $school->id)
            ->whereNotNull('student_id')
            ->distinct()
            ->count('student_id');
        $absent = max(0, $enrolled - $markedToday);

        return response()->json([
            'total_today'   => (int) ($stats->total_today ?? 0),
            'present_count' => $markedToday,
            'on_time_count' => (int) ($stats->on_time_count ?? 0),
            'late_count'    => (int) ($stats->late_count ?? 0),
            'absent_count'  => $absent,
        ]);
    }

    /**
     * Public landing-page summary.
     *
     * This intentionally returns only aggregate, non-personal counts. Scanner
     * and guard terminal requests must still use publicStats() with a school
     * context so school-level isolation is preserved.
     */
    public function divisionPublicStats(): JsonResponse
    {
        $studentsEnrolled = Student::whereNull('deleted_at')->count();
        $scansToday = Attendance::whereDate('scanned_at', today())->count();

        return response()->json([
            'students_enrolled' => (int) $studentsEnrolled,
            'scans_today'       => (int) $scansToday,
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

        $input = trim((string) ($request->student_number ?? $request->student_id));
        $schoolId = $request->user()?->school_id;
        if (!$schoolId) {
            return response()->json([
                'error' => 'Teacher is not assigned to a school.',
            ], 403);
        }

        $student = Student::where('school_id', $schoolId)
            ->where(function ($q) use ($input) {
                $q->where('student_number', $input)->orWhere('id', $input);
            })
            ->first();

        if (!$student) {
            return response()->json([
                'error' => 'Student not found in your school.'
            ], 404);
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
        $schoolId   = $teacher?->school_id;
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
        $notificationPref = (int) ($student->notification_preference ?? 0);
        $timeStr          = $scannedAt->format('h:i A');
        $sessionLabel     = ucfirst((string) $session);
        $formattedNumber  = null;

        // Resolve and normalise the guardian's phone number
        $guardianContact = $student->contact_number ?: $student->emergency_contact;
        if (!empty($guardianContact)) {
            $digits = preg_replace('/\D/', '', (string) $guardianContact);
            $formattedNumber = preg_replace('/^0/', '63', $digits);
            if (str_starts_with($formattedNumber, '9') && strlen($formattedNumber) === 10) {
                $formattedNumber = '63' . $formattedNumber;
            }
        }

        // Determine dispatch conditions
        $willSendEmail = !empty($student->guardian_email);
        $willSendSms   = false;

        if ($notificationPref === 2) {
            $willSendSms = !empty($formattedNumber);
        } elseif ($notificationPref === 1) {
            $todayStr = $scannedAt->toDateString();
            $lastSent = $student->last_sms_sent_date
                ? (is_string($student->last_sms_sent_date) ? $student->last_sms_sent_date : $student->last_sms_sent_date->format('Y-m-d'))
                : null;
            $willSendSms = !empty($formattedNumber) && ($lastSent !== $todayStr);

            if ($willSendSms) {
                $student->update(['last_sms_sent_date' => $todayStr]);
            }
        }

        app()->terminating(function () use ($student, $timeStr, $sessionLabel, $formattedNumber, $notificationPref, $willSendEmail, $willSendSms) {
            try {
                if ($willSendEmail) {
                    SendEmailNotification::dispatch($student, $timeStr, $sessionLabel)->afterResponse();
                }
                if ($willSendSms) {
                    SendSmsNotification::dispatch($student, $timeStr, $sessionLabel, $formattedNumber, $notificationPref)->afterResponse();
                }
            } catch (\Throwable $e) {
                Log::warning('Teacher scan notification dispatch failed: ' . $e->getMessage());
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

    /**
     * PURPOSE: Return live attendance stats for the authenticated user's school context.
     * FIX: Returns 403 when school context cannot be resolved instead of silently using fallback school.
     * LIMITATION: Requires valid school association on authenticated user record.
     */
    public function getStats(Request $request): JsonResponse
    {
        $user     = $request->user('sanctum');
        $schoolId = $this->resolveSchoolId($user, null, 'student');
        if (!$schoolId) {
            return response()->json([
                'message' => 'School context could not be determined.'
            ], 403);
        }

        return response()->json($this->calculateStats($schoolId));
    }

    /* ====================================================================== */
    /*  Private helpers                                                        */
    /* ====================================================================== */

    /**
     * PURPOSE: Resolve school_id from authenticated context without cross-school fallback.
     * FIX: Removed School::first() fallback to prevent random-school leakage when context is missing.
     * LIMITATION: Returns null when school context cannot be determined; caller must handle with 403.
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

        return null;
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

    /* ====================================================================== */
    /*  Teacher Attendance Monitor                                             */
    /* ====================================================================== */

    /**
     * Action: Implementing Teacher-specific Attendance Monitoring with Split-View UI.
     *
     * Target Role: Teacher
     * Source: Authenticated Teacher session.
     * Destination: AttendanceMonitor.vue (frontend split-view).
     * Function: Fetches all students belonging to the teacher, checks today's
     *           attendance records, and sorts them into presentStudents and
     *           absentStudents arrays.
     *
     * Note: UI should prioritize high-contrast badges for quick scanning
     *       of classroom attendance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeacherStudentStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $schoolId = $user?->school_id;

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            // Fetch students in the caller's school. Teachers are narrowed to
            // their own class/records; admins can monitor the whole school.
            $query = Student::query()
                ->when($schoolId, function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
            if ($user->role?->name === 'Teacher') {
                if ($user->grade_level && $user->section) {
                    $query->where('grade', $user->grade_level)
                          ->where('section', $user->section);
                } else {
                    $query->where(function ($q) use ($user) {
                        $q->where('teacher_id', $user->id)->orWhere('created_by', $user->id);
                    });
                }
            }
            
            $students = $query->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            $today = now()->toDateString();

            // Get all of today's morning attendance records for these student IDs
            $studentIds = $students->pluck('id')->toArray();

            $todayAttendance = Attendance::whereIn('student_id', $studentIds)
                ->when($schoolId, function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                })
                ->whereDate('scanned_at', $today)
                ->where('session', 'morning')
                ->get()
                ->keyBy('student_id');

            $presentStudents = [];
            $absentStudents  = [];

            foreach ($students as $student) {
                $row = [
                    'id'             => $student->id,
                    'student_number' => $student->student_number,
                    'first_name'     => $student->first_name,
                    'last_name'      => $student->last_name,
                    'full_name'      => $student->first_name . ' ' . $student->last_name,
                    'grade'          => $student->grade,
                    'section'        => $student->section,
                    'grade_section'  => trim(($student->grade ?? '') . ' - ' . ($student->section ?? ''), ' -'),
                    'photo_path'     => $student->photo_path
                        ? url('storage/' . ltrim(str_replace(['public/', 'storage/'], '', $student->photo_path), '/'))
                        : null,
                ];

                if ($todayAttendance->has($student->id)) {
                    $att = $todayAttendance->get($student->id);
                    $row['time_in'] = $att->scanned_at->format('h:i A');
                    $row['status']  = $att->status ?? 'on_time';
                    $presentStudents[] = $row;
                } else {
                    $absentStudents[] = $row;
                }
            }

            return response()->json([
                'status'          => 'success',
                'presentStudents' => $presentStudents,
                'absentStudents'  => $absentStudents,
                'presentCount'    => count($presentStudents),
                'absentCount'     => count($absentStudents),
                'totalStudents'   => count($students),
                'date'            => $today,
            ]);
        } catch (\Throwable $e) {
            Log::error('getTeacherStudentStatus failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load attendance status.',
            ], 500);
        }
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

        $today = now()->toDateString();

        $attendanceToday = Attendance::where('school_id', $schoolId)
            ->whereDate('scanned_at', $today);

        $totalToday = (clone $attendanceToday)->count();
        $onTime = (clone $attendanceToday)->where('status', 'on_time')->count();
        $late = (clone $attendanceToday)->where('status', 'late')->count();

        $enrolled = Student::where('school_id', $schoolId)->count();
        $markedToday = (clone $attendanceToday)
            ->whereNotNull('student_id')
            ->distinct()
            ->count('student_id');
        $absent = max(0, $enrolled - $markedToday);

        return [
            'total_today'   => $totalToday,
            'present_count' => $markedToday,
            'on_time_count' => $onTime,
            'late_count'    => $late,
            'absent_count'  => $absent,
            'present'       => $markedToday,
            'on_time'       => $onTime,
            'late'          => $late,
            'absent'        => $absent,
        ];
    }

    /**
     * resolveScannerSchoolContext
     * PURPOSE: Resolve the ScanUp school from deped_id and align it with the Sanctum user’s school when a token is present.
     * WHY: Prevents a valid token for School A from scanning or reading stats while claiming deped_id for School B.
     *
     * @param Request $request Incoming HTTP request (body or query may carry deped_id).
     * @param bool $requireDeped When true, missing deped_id yields null (public scan POST).
     * @return School|null Resolved school or null when context is invalid.
     */
    private function resolveScannerSchoolContext(Request $request, bool $requireDeped = false): ?School
    {
        $depedRaw = $request->input('deped_id') ?? $request->query('deped_id');
        $depedId = is_string($depedRaw)
            ? trim($depedRaw)
            : (is_numeric($depedRaw) ? trim((string) $depedRaw) : '');

        if ($requireDeped && $depedId === '') {
            return null;
        }

        $tokenUser = $request->user('sanctum');

        if ($depedId !== '') {
            if (strlen($depedId) > 50 || ! preg_match('/^[A-Za-z0-9\-]+$/', $depedId)) {
                return null;
            }

            $school = $this->schools->resolveForScanUpWrite($depedId);
            if (! $school) {
                return null;
            }

            if ($tokenUser && $tokenUser->school_id && (int) $tokenUser->school_id !== (int) $school->id) {
                return null;
            }

            return $school;
        }

        if ($tokenUser && $tokenUser->school_id) {
            return School::find($tokenUser->school_id);
        }

        return null;
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
