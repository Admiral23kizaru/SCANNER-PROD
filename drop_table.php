<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

try {
    Schema::dropIfExists('locator_slips');
    echo "Table dropped.\n";
} catch (\Exception $e) {
    echo "Error dropping table: " . $e->getMessage() . "\n";
}
