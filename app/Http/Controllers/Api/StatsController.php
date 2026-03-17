<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $teacherRoleId = \App\Models\Role::where('name', 'Teacher')->value('id');
        if ($teacherRoleId === null) {
            return response()->json([
                'total_students' => 0,
                'total_teachers' => 0,
                'todays_attendance' => 0,
            ]);
        }

        $totalStudents = Student::count();
        $totalTeachers = \App\Models\Teacher::count();
        $todaysAttendance = Attendance::whereDate('scanned_at', now()->toDateString())->count();

        return response()->json([
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'todays_attendance' => $todaysAttendance,
        ]);
    }

    public function overview(): JsonResponse
    {
        $base = $this->index()->getData(true);

        $recentAttendance = Attendance::with('student')
            ->orderByDesc('scanned_at')
            ->limit(6)
            ->get()
            ->map(function (Attendance $a) {
                $s = $a->student;
                $fullName = $s ? trim(($s->first_name ?? '').' '.($s->last_name ?? '')) : '—';
                $gradeSection = $s ? ($s->grade_section ?? '—') : '—';
                return [
                    'type' => 'attendance',
                    'title' => 'Student check-in',
                    'subtitle' => trim($fullName.' - '.$gradeSection),
                    'time' => $a->scanned_at?->toIso8601String(),
                ];
            });

        $recentUsers = User::orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(function (User $u) {
                $roleName = $u->role?->name ?: 'User';
                return [
                    'type' => 'registration',
                    'title' => 'New user registered',
                    'subtitle' => trim(($u->name ?? '—').' - '.$roleName),
                    'time' => $u->created_at?->toIso8601String(),
                ];
            });

        $combined = $recentAttendance
            ->concat($recentUsers)
            ->sortByDesc('time')
            ->values()
            ->take(6)
            ->all();

        return response()->json([
            'stats' => $base,
            'recent_activity' => $combined,
        ]);
    }

    public function summaryReportPdf(): Response
    {
        if (!class_exists(\TCPDF::class)) {
            return response()->json(['message' => 'TCPDF is not installed.'], 500);
        }

        $stats = $this->index()->getData(true);
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
        $pdf->Cell(0, 6, 'Date: '.now()->format('F d, Y'), 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Counts', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Total Students: '.($stats['total_students'] ?? 0), 0, 1, 'L');
        $pdf->Cell(0, 6, 'Total Teachers: '.($stats['total_teachers'] ?? 0), 0, 1, 'L');
        $pdf->Cell(0, 6, "Today's Attendance: ".($stats['todays_attendance'] ?? 0), 0, 1, 'L');
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
            $s = $a->student;
            $name = $s ? trim(($s->first_name ?? '').' '.($s->last_name ?? '')) : '—';
            $gs = $s ? ($s->grade_section ?? '—') : '—';
            $time = $a->scanned_at ? $a->scanned_at->format('h:i A') : '—';
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

    public function dashboardStats(): JsonResponse
    {
        $data = Cache::remember('admin_dashboard_stats', 180, function () {
            $totalStudents = Student::count();
            $totalTeachers = \App\Models\Teacher::count();
            $todaysAttendance = Attendance::whereDate('scanned_at', now()->toDateString())->count();

            // Attendance per grade
            $attendancePerGrade = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.id')
                ->whereDate('attendance.scanned_at', now()->toDateString())
                ->select('students.grade', DB::raw('count(*) as count'))
                ->groupBy('students.grade')
                ->get()
                ->toArray();

            // Historical average
            $historicalAverage = Attendance::whereDate('scanned_at', '<', now()->toDateString())
                ->select(DB::raw('DATE(scanned_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->get()
                ->avg('count') ?: 0;

            return [
                'totals' => [
                    'students' => $totalStudents,
                    'teachers' => $totalTeachers,
                    'attendance_today' => $todaysAttendance,
                    'is_above_average' => $todaysAttendance > $historicalAverage,
                ],
                'attendance_by_grade' => $attendancePerGrade,
                'historical_average' => round($historicalAverage, 2),
            ];
        });

        return response()->json($data);
    }

    public function attendanceTrends(Request $request): JsonResponse
    {
        $groupBy = $request->input('group_by', 'day');
        $grade = $request->input('grade');
        $section = $request->input('section');

        $query = Attendance::query()
            ->join('students', 'attendance.student_id', '=', 'students.id');

        if ($grade) {
            $query->where('students.grade', $grade);
        }
        if ($section) {
            $query->where('students.section', $section);
        }

        if ($groupBy === 'month') {
            $query->select(DB::raw("DATE_FORMAT(attendance.scanned_at, '%Y-%m') as label"), DB::raw('count(*) as count'))
                ->where('attendance.scanned_at', '>=', now()->subMonths(12));
        } elseif ($groupBy === 'week') {
            $query->select(DB::raw("YEARWEEK(attendance.scanned_at) as label"), DB::raw('count(*) as count'))
                ->where('attendance.scanned_at', '>=', now()->subWeeks(12));
        } else {
            $query->select(DB::raw("DATE(attendance.scanned_at) as label"), DB::raw('count(*) as count'))
                ->where('attendance.scanned_at', '>=', now()->subDays(30));
        }

        $trends = $query->groupBy('label')
            ->orderBy('label')
            ->get();

        return response()->json($trends);
    }
}
