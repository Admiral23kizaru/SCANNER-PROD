<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
foreach (User::all() as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Role: " . ($u->role?->name ?? 'NONE') . "\n";
}
