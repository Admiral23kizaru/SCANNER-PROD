<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * StatsController — aggregated reporting and PDF summary generation for the admin panel.
 *
 * Dashboard stats are cached for 3 minutes (180 seconds) to reduce DB load.
 */
class StatsController extends BaseController
{
    /* ====================================================================== */
    /*  Summary stats                                                          */
    /* ====================================================================== */

    /**
     * PURPOSE: Return high-level student/teacher/attendance counts for the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() so stats are always school-scoped and never wildcard across schools.
     * LIMITATION: Requires valid school assignment for the authenticated account.
     */
    public function index(): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $teacherRoleId = Role::where('name', 'Teacher')->value('id');
        if ($teacherRoleId === null) {
            return response()->json([
                'total_students'    => 0,
                'total_teachers'    => 0,
                'todays_attendance' => 0,
            ]);
        }

        return response()->json([
            'total_students'    => Student::where('school_id', $schoolId)->count(),
            'total_teachers'    => User::where('role_id', $teacherRoleId)->where('school_id', $schoolId)->count(),
            'todays_attendance' => Attendance::where('school_id', $schoolId)
                ->whereDate('scanned_at', now()->toDateString())->count(),
        ]);
    }

    /**
     * PURPOSE: Return dashboard summary plus recent activity scoped to the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() and strict school filters for attendance and user activity.
     * LIMITATION: Activity feed remains limited to latest six combined records.
     */
    public function overview(): JsonResponse
    {
        $base     = $this->index()->getData(true);
        $schoolId = $this->getAuthSchoolId();

        $recentAttendance = Attendance::with('student')
            ->where('school_id', $schoolId)
            ->orderByDesc('scanned_at')
            ->limit(6)
            ->get()
            ->map(fn (Attendance $a) => [
                'type'     => 'attendance',
                'title'    => 'Student check-in',
                'subtitle' => trim(($a->student
                    ? (($a->student->first_name ?? '') . ' ' . ($a->student->last_name ?? '') . ' - ' . ($a->student->grade_section ?? '—'))
                    : '—')),
                'time'     => $a->scanned_at?->toIso8601String(),
            ]);

        $recentUsers = User::where('school_id', $schoolId)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (User $u) => [
                'type'     => 'registration',
                'title'    => 'New user registered',
                'subtitle' => trim(($u->name ?? '—') . ' - ' . ($u->role?->name ?? 'User')),
                'time'     => $u->created_at?->toIso8601String(),
            ]);

        $combined = $recentAttendance
            ->concat($recentUsers)
            ->sortByDesc('time')
            ->values()
            ->take(6)
            ->all();

        return response()->json(['stats' => $base, 'recent_activity' => $combined]);
    }

    /* ====================================================================== */
    /*  Dashboard charts                                                       */
    /* ====================================================================== */

    /**
     * PURPOSE: Return cached dashboard statistics for the authenticated admin's school.
     * FIX: Uses mandatory getAuthSchoolId() and school-specific cache key (no global/null fallback key).
     * LIMITATION: Cache TTL remains 180 seconds.
     */
    public function dashboardStats(): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $cacheKey = 'admin_dashboard_stats_' . $schoolId;

        $teacherRoleId = Role::where('name', 'Teacher')->value('id');

        $data = Cache::remember($cacheKey, 180, function () use ($schoolId, $teacherRoleId) {
            $totalStudents    = Student::where('school_id', $schoolId)->count();
            $totalTeachers    = $teacherRoleId === null
                ? 0
                : User::where('role_id', $teacherRoleId)->where('school_id', $schoolId)->count();
            $todaysAttendance = Attendance::where('school_id', $schoolId)
                ->whereDate('scanned_at', now()->toDateString())->count();

            $maleToday   = Student::where('school_id', $schoolId)->where('gender', 'Male')->count();
            $femaleToday = Student::where('school_id', $schoolId)->where('gender', 'Female')->count();

            $presentCount = Attendance::where('school_id', $schoolId)
                ->whereDate('scanned_at', now()->toDateString())
                ->distinct('student_id')
                ->count('student_id');
            $absentToday = max(0, $totalStudents - $presentCount);

            $attendanceQuery = DB::table('tbl_scanup_attendance as attendance')
                ->join('tbl_scanup_students as students', 'attendance.student_id', '=', 'students.id')
                ->whereDate('attendance.scanned_at', now()->toDateString())
                ->where('attendance.school_id', $schoolId)
                ->select('students.grade', DB::raw('count(*) as count'))
                ->groupBy('students.grade');
            $attendancePerGrade = $attendanceQuery->get()->toArray();

            $historicalAverage = Attendance::where('school_id', $schoolId)
                ->whereDate('scanned_at', '<', now()->toDateString())
                ->select(DB::raw('DATE(scanned_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->get()
                ->avg('count') ?: 0;

            return [
                'totals' => [
                    'students'         => $totalStudents,
                    'teachers'         => $totalTeachers,
                    'attendance_today' => $todaysAttendance,
                    'male_today'       => $maleToday,
                    'female_today'     => $femaleToday,
                    'absent_today'     => $absentToday,
                    'is_above_average' => $todaysAttendance > $historicalAverage,
                ],
                'attendance_by_grade' => $attendancePerGrade,
                'historical_average'  => round($historicalAverage, 2),
            ];
        });

        return response()->json($data);
    }

    /**
     * PURPOSE: Return attendance trend data for charts scoped to the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() and hard school filter in the base attendance query.
     * LIMITATION: Optional grade/section filters remain client-driven but still school-constrained.
     */
    public function attendanceTrends(Request $request): JsonResponse
    {
        $groupBy  = $request->input('group_by', 'day');
        $grade    = $request->input('grade');
        $section  = $request->input('section');
        $schoolId = $this->getAuthSchoolId();

        $query = Attendance::query()
            ->from('tbl_scanup_attendance', 'attendance')
            ->join('tbl_scanup_students as students', 'attendance.student_id', '=', 'students.id')
            ->where('attendance.school_id', $schoolId);

        if ($grade)   { $query->where('students.grade', $grade); }
        if ($section) { $query->where('students.section', $section); }

        match ($groupBy) {
            'month' => $query
                ->select(DB::raw("DATE_FORMAT(attendance.scanned_at, '%Y-%m') as label"), DB::raw('count(*) as count'))
                ->where('attendance.scanned_at', '>=', now()->subMonths(12)),
            'week' => $query
                ->select(DB::raw('YEARWEEK(attendance.scanned_at) as label'), DB::raw('count(*) as count'))
                ->where('attendance.scanned_at', '>=', now()->subWeeks(12)),
            default => $query
                ->select(DB::raw('DATE(attendance.scanned_at) as label'), DB::raw('count(*) as count'))
                ->where('attendance.scanned_at', '>=', now()->subDays(30)),
        };

        $trends = $query->groupBy('label')->orderBy('label')->get();

        return response()->json($trends);
    }

    /* ====================================================================== */
    /*  PDF report                                                             */
    /* ====================================================================== */

    /**
     * PURPOSE: Generate and stream a same-school daily attendance summary PDF for the authenticated admin.
     * FIX: Uses getAuthSchoolId() and strict school filters to prevent cross-school report aggregation.
     * LIMITATION: Requires TCPDF package availability.
     */
    public function summaryReportPdf(): Response
    {
        if (!class_exists(\TCPDF::class)) {
            return response()->json(['message' => 'TCPDF is not installed.'], 500);
        }

        $schoolId = $this->getAuthSchoolId();
        $stats  = $this->index()->getData(true);
        $recent = Attendance::with('student')
            ->where('school_id', $schoolId)
            ->whereDate('scanned_at', now()->toDateString())
            ->orderByDesc('scanned_at')
            ->limit(20)
            ->get();

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Attendance Summary Report', 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Date: ' . now()->format('F d, Y'), 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Counts', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Total Students: ' . ($stats['total_students'] ?? 0), 0, 1, 'L');
        $pdf->Cell(0, 6, 'Total Teachers: ' . ($stats['total_teachers'] ?? 0), 0, 1, 'L');
        $pdf->Cell(0, 6, "Today's Attendance: " . ($stats['todays_attendance'] ?? 0), 0, 1, 'L');
        $pdf->Ln(4);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, "Today's Recent Attendance (latest 20)", 0, 1, 'L');
        $pdf->Ln(1);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(10, 6, '#', 1, 0, 'C');
        $pdf->Cell(80, 6, 'Student', 1, 0, 'L');
        $pdf->Cell(35, 6, 'Grade/Section', 1, 0, 'L');
        $pdf->Cell(45, 6, 'Time In', 1, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);
        $i = 1;
        foreach ($recent as $a) {
            $s    = $a->student;
            $name = $s ? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) : '—';
            $gs   = $s?->grade_section ?? '—';
            $time = $a->scanned_at?->format('h:i A') ?? '—';

            $pdf->Cell(10, 6, (string) $i++, 1, 0, 'C');
            $pdf->Cell(80, 6, $name, 1, 0, 'L');
            $pdf->Cell(35, 6, $gs, 1, 0, 'L');
            $pdf->Cell(45, 6, $time, 1, 1, 'L');
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        $content = $pdf->Output('summary_report.pdf', 'S');

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="summary_report.pdf"');
    }

    /**
     * PURPOSE: Return population detail subsets (male/female/absent/teacher_students) for the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() as mandatory scope and removes nullable wildcard behavior.
     * LIMITATION: teacher_students filter still relies on teacher grade/section metadata quality.
     */
    public function getPopulationDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        $type     = $request->query('type');
        $schoolId = $this->getAuthSchoolId();
        $query    = \App\Models\Student::query()
            ->where('school_id', $schoolId)
            ->orderBy('last_name')->orderBy('first_name');

        switch ($type) {
            case 'male':
                $query->where('gender', 'Male');
                break;
            case 'female':
                $query->where('gender', 'Female');
                break;
            case 'absent':
                $today = now()->toDateString();
                $query->whereDoesntHave('attendance', function ($q) use ($today) {
                    $q->whereDate('scanned_at', $today);
                });
                break;
            case 'teacher_students':
                $teacherId = $request->query('teacher_id');
                $teacher   = \App\Models\User::find($teacherId);
                if ($teacher && $teacher->grade_level && $teacher->section) {
                    $query->where('grade', $teacher->grade_level)
                          ->where('section', $teacher->section);
                } else {
                    $query->where('id', 0);
                }
                break;
            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }

        return response()->json(['data' => $query->get()]);
    }
}
