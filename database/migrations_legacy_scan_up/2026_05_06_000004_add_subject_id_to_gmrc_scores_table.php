<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gmrc_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('gmrc_scores', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('student_id');
                $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
                $table->index(['subject_id', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('gmrc_scores', function (Blueprint $table) {
            if (Schema::hasColumn('gmrc_scores', 'subject_id')) {
                $table->dropForeign(['subject_id']);
                $table->dropIndex(['subject_id', 'created_at']);
                $table->dropColumn('subject_id');
            }
        });
    }
};

