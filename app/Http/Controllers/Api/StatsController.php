<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
        $totalTeachers = User::where('role_id', $teacherRoleId)->count();
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
}
