<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Student;
$s = Student::find(7);
if ($s) {
    echo "ID: 7 | Name: {$s->first_name} {$s->last_name} | Contact: " . ($s->contact_number ?: 'NULL') . " | Emergency: " . ($s->emergency_contact ?: 'NULL') . "\n";
} else {
    echo "Student 7 not found.\n";
}
