<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * StatsController — aggregated reporting and PDF summary generation for the admin panel.
 *
 * Dashboard stats are cached for 3 minutes (180 seconds) to reduce DB load.
 */
class StatsController extends Controller
{
    /* ====================================================================== */
    /*  Summary stats                                                          */
    /* ====================================================================== */

    /** Return high-level counts: total students, teachers, and today's scan count. */
    public function index(): JsonResponse
    {
        $teacherRoleId = Role::where('name', 'Teacher')->value('id');
        if ($teacherRoleId === null) {
            return response()->json([
                'total_students'    => 0,
                'total_teachers'    => 0,
                'todays_attendance' => 0,
            ]);
        }

        return response()->json([
            'total_students'    => Student::count(),
            'total_teachers'    => Teacher::count(),
            'todays_attendance' => Attendance::whereDate('scanned_at', now()->toDateString())->count(),
        ]);
    }

    /**
     * Return dashboard stats combined with a recent-activity feed.
     *
     * Merges recent attendance check-ins with recently created user accounts,
     * sorted by time and limited to the 6 most recent events.
     */
    public function overview(): JsonResponse
    {
        $base = $this->index()->getData(true);

        $recentAttendance = Attendance::with('student')
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

        $recentUsers = User::orderByDesc('created_at')
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
     * Return school-wide dashboard statistics (cached for 3 minutes).
     *
     * Includes:
     *   - Total student and teacher counts
     *   - Today's attendance count vs. historical average
     *   - Attendance breakdown by grade level
     */
    public function dashboardStats(): JsonResponse
    {
        $data = Cache::remember('admin_dashboard_stats', 180, function () {
            $totalStudents    = Student::count();
            $totalTeachers    = Teacher::count();
            $todaysAttendance = Attendance::whereDate('scanned_at', now()->toDateString())->count();

            // Male/Female counts for today
            $maleToday = Attendance::query()
                ->join('students', 'attendance.student_id', '=', 'students.id')
                ->whereDate('attendance.scanned_at', now()->toDateString())
                ->where('students.gender', 'Male')
                ->count();

            $femaleToday = Attendance::query()
                ->join('students', 'attendance.student_id', '=', 'students.id')
                ->whereDate('attendance.scanned_at', now()->toDateString())
                ->where('students.gender', 'Female')
                ->count();

            // Absent today = Total students - Total unique students present today
            // Note: Use DISTINCT student_id in case students check in/out multiple times.
            $presentCount = Attendance::whereDate('scanned_at', now()->toDateString())
                ->distinct('student_id')
                ->count('student_id');
            $absentToday = max(0, $totalStudents - $presentCount);

            $attendancePerGrade = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.id')
                ->whereDate('attendance.scanned_at', now()->toDateString())
                ->select('students.grade', DB::raw('count(*) as count'))
                ->groupBy('students.grade')
                ->get()
                ->toArray();

            $historicalAverage = Attendance::whereDate('scanned_at', '<', now()->toDateString())
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
     * Return attendance trend data for line/bar chart rendering.
     *
     * Supports grouping by: day (last 30 days), week (last 12 weeks), month (last 12 months).
     * Optionally filterable by grade or section.
     */
    public function attendanceTrends(Request $request): JsonResponse
    {
        $groupBy = $request->input('group_by', 'day');
        $grade   = $request->input('grade');
        $section = $request->input('section');

        $query = Attendance::query()
            ->join('students', 'attendance.student_id', '=', 'students.id');

        if ($grade) {
            $query->where('students.grade', $grade);
        }
        if ($section) {
            $query->where('students.section', $section);
        }

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
     * Generate and stream a TCPDF summary report for today's attendance.
     *
     * Requires the `tecnickcom/tcpdf` package. Returns a 500 JSON error if not installed.
     */
    public function summaryReportPdf(): Response
    {
        if (!class_exists(\TCPDF::class)) {
            return response()->json(['message' => 'TCPDF is not installed.'], 500);
        }

        $stats  = $this->index()->getData(true);
        $recent = Attendance::with('student')
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
     * // Description: getPopulationDetails - Handles fetching of students classified by specific population analytics arrays. (Male, Female, Absent, Teacher Specific).
     * // Author: Antigravity System Agent
     */
    public function getPopulationDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        $type = $request->query('type');
        $query = \App\Models\Student::query()->orderBy('last_name')->orderBy('first_name');

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
                $teacher = \App\Models\User::find($teacherId);
                if ($teacher && $teacher->grade_level && $teacher->section) {
                    $query->where('grade', $teacher->grade_level)
                          ->where('section', $teacher->section);
                } else {
                    $query->where('id', 0); // Empty result fallback if missing assignments
                }
                break;
            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }

        return response()->json(['data' => $query->get()]);
    }
}
