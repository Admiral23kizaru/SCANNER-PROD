<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Cache;
use App\Models\Attendance;

$studentId = 7;
$session = (now()->hour < 12) ? 'morning' : 'afternoon';
$cacheKey = "sms_sent_{$studentId}_" . ucfirst($session) . "_" . date('Y-m-d');

Cache::forget($cacheKey);
Cache::forget("sms_cooldown_{$studentId}");
echo "Cache cleared for {$cacheKey} and sms_cooldown_{$studentId}\n";

// Also delete attendance for today so scanPublic doesn't block it
Attendance::where('student_id', $studentId)
    ->whereDate('scanned_at', now()->toDateString())
    ->where('session', $session)
    ->delete();
echo "Attendance records for student {$studentId} ({$session}) deleted for testing.\n";
