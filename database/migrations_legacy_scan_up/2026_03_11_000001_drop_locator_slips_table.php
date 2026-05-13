<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('locator_slips');
    }

    public function down(): void
    {
        Schema::create('locator_slips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->date('date_of_filing');
            $table->string('name');
            $table->string('position');
            $table->string('permanent_station');
            $table->string('destination');
            $table->text('purpose_of_travel');
            $table->enum('official_type', ['Official Business', 'Official Time']);
            $table->dateTime('date_time');
            $table->time('time_out');
            $table->time('expected_return');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_remarks')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};

