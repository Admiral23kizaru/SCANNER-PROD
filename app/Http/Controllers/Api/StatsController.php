<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;

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
}
