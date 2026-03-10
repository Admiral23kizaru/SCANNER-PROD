<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::dropIfExists('locator_slips');

try {
    Schema::create('locator_slips', function (Blueprint $table) {
        $table->id();
        $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
        $table->date('date_of_filing');
        $table->string('name');
        $table->string('position');
        $table->string('destination');
        $table->string('purpose_of_travel');
        $table->time('time_out')->nullable();
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->text('admin_remarks')->nullable();
        $table->timestamp('reviewed_at')->nullable();
        $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamps();
    });
    echo "Created perfectly.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
