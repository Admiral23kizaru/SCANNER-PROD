<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Users: " . implode(', ', Schema::getColumnListing('users')) . "\n";
echo "Students: " . implode(', ', Schema::getColumnListing('students')) . "\n";
echo "Teachers: " . implode(', ', Schema::getColumnListing('teachers')) . "\n";
