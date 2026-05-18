<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_scanup_scanner_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
            $table->string('deped_school_id', 50);
            $table->string('scanner_key', 100)->default('main-terminal');
            $table->string('camera_status', 32)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'scanner_key'], 'scanup_scanner_school_key_unique');
            $table->index(['deped_school_id', 'last_seen_at'], 'scanup_scanner_deped_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_scanup_scanner_heartbeats');
    }
};
