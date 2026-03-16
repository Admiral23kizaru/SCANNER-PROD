<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Role;
foreach (Role::all() as $r) {
    echo "ID: {$r->id} - Name: {$r->name}\n";
}
