<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
use App\Models\Role;

$guards = User::all();
foreach ($guards as $u) {
    echo "User: {$u->name} (Email: {$u->email}) - Role: " . ($u->role?->name ?? 'NONE') . " (ID: {$u->role_id})\n";
}
