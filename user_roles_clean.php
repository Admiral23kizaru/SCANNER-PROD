<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
foreach (User::all() as $u) {
    printf("ID: %d | Name: %s | Role: %s\n", $u->id, $u->name, $u->role?->name ?? 'NONE');
}
