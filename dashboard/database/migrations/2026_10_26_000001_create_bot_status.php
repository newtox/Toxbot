<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_status')) {
            return;
        }

        Schema::create('bot_status', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('heartbeat_at')->nullable();
            $table->string('commit', 64)->nullable();
            $table->string('build_date', 40)->nullable();
            $table->string('dart_version', 40)->nullable();
            $table->unsignedInteger('guilds')->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedBigInteger('memory_bytes')->nullable();
            $table->string('user_id', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_status');
    }
};
