<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
$u1 = User::find(1);
echo "User 1: " . $u1->name . "\n";
echo "School Name: " . ($u1->school_name ?? 'NULL') . "\n";
echo "Role: " . ($u1->role?->name ?? 'NULL') . "\n";
