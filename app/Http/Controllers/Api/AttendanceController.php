<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsNotification;
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
    public function __construct(protected readonly MailerService $mailer)
    {
    }

    /* ====================================================================== */
    /*  Public scanner                                                         */
    /* ====================================================================== */

    /**
     * Process a QR scan submitted by the Guard scanner terminal.
     *
     * Accepts a student_number or employee_id, determines session (morning/afternoon),
     * prevents duplicate scans, resolves school context, records attendance,
     * and dispatches an SMS to the student's guardian.
     */
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
                    'status'  => 'invalid',
                    'message' => 'Invalid QR code.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $input  = trim((string) $request->student_id);
            $person = null;
            $personType = 'student';

            // Resolve person from QR input
            $student = is_numeric($input)
                ? Student::find((int) $input)
                : Student::where('student_number', $input)->first();

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

            // Determine session based on time of day
            $session = now()->hour < 12 ? 'morning' : 'afternoon';

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

                // Dispatch async SMS to guardian
                if ($person->contact_number || $person->emergency_contact) {
                    SendSmsNotification::dispatch($person, $scannedAt->format('h:i A'), ucfirst($session));
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

            return response()->json([
                'status'     => 'success',
                'message'    => $personType === 'teacher' ? 'Teacher verified.' : 'Attendance recorded.',
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

    /* ====================================================================== */
    /*  Teacher-side scanner (authenticated)                                  */
    /* ====================================================================== */

    /**
     * Record attendance from the teacher-side scanner interface.
     *
     * Unlike the public scanner, this endpoint is auth-protected
     * and does not trigger SMS or session-based duplicate guards.
     */
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
                'errors'  => $validator->errors(),
            ], 422);
        }

        $input   = trim($request->student_id);
        $student = is_numeric($input)
            ? Student::find((int) $input)
            : Student::where('student_number', $input)->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        // Prevent double-scan by same teacher within 2 seconds
        $recent = Attendance::where('student_id', $student->id)
            ->where('scanned_by', $request->user()->id)
            ->where('scanned_at', '>=', now()->subSeconds(2))
            ->exists();

        if ($recent) {
            return response()->json(['message' => 'Duplicate scan. Please wait before scanning again.'], 422);
        }

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'scanned_by' => $request->user()->id,
            'scanned_at' => now(),
        ]);

        $student->load('teacher');

        return response()->json([
            'message'    => 'Attendance recorded.',
            'attendance' => [
                'id'         => $attendance->id,
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
