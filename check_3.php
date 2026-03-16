<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$u3 = DB::table('users')->where('id', 3)->first();
if ($u3) {
    echo "User 3 in users table: " . json_encode($u3) . "\n";
} else {
    echo "User 3 NOT in users table.\n";
}
$t3 = DB::table('teachers')->where('id', 3)->first();
if ($t3) {
    echo "Teacher 3 in teachers table: " . json_encode($t3) . "\n";
} else {
    echo "Teacher 3 NOT in teachers table.\n";
}
