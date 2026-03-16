<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use App\Models\School;
use App\Models\Attendance;

$schoolCount = School::count();
$studentCount = Student::count();
$attendanceCount = Attendance::count();

echo "Schools: $schoolCount\n";
echo "Students: $studentCount\n";
echo "Attendance records: $attendanceCount\n";

if ($schoolCount > 0) {
    $firstSchool = School::first();
    $studentsInFirstSchool = Student::where('school_id', $firstSchool->id)->count();
    $attendanceInFirstSchool = Attendance::where('school_id', $firstSchool->id)->count();
    echo "First School ID: {$firstSchool->id}\n";
    echo "Students in First School: $studentsInFirstSchool\n";
    echo "Attendance in First School: $attendanceInFirstSchool\n";
}

$studentsWithoutSchool = Student::whereNull('school_id')->count();
echo "Students without school_id: $studentsWithoutSchool\n";
