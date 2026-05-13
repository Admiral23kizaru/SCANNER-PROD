<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gmrc_scores', function (Blueprint $table) {
            $table->id();
            // students.id is an INT in this project (not BIGINT), so keep types aligned for FK validity.
            $table->unsignedInteger('student_id');
            $table->string('section', 100);
            $table->string('grade_level', 50);
            $table->json('wrong_items')->nullable();
            $table->unsignedSmallInteger('total_items')->default(50);
            $table->unsignedSmallInteger('score');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->index(['grade_level', 'section']);
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gmrc_scores');
    }
};

