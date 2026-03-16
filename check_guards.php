<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Teacher;

$guardRole = Role::where('name', 'Guard')->first();
if ($guardRole) {
    $guards = User::where('role_id', $guardRole->id)->get();
    echo "Guard count: " . $guards->count() . "\n";
    foreach ($guards as $g) {
        $t = Teacher::where('user_id', $g->id)->first();
        echo "Guard {$g->name} (ID: {$g->id}) - Teacher entry: " . ($t ? "Yes (School ID: {$t->school_id})" : "No") . "\n";
    }
} else {
    echo "Guard role not found.\n";
}
