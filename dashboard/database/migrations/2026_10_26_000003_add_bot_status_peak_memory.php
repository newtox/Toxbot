<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bot_status', 'memory_peak_bytes')) {
            return;
        }

        Schema::table('bot_status', function (Blueprint $table) {
            $table->unsignedBigInteger('memory_peak_bytes')->nullable()->after('memory_bytes');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('bot_status', 'memory_peak_bytes')) {
            Schema::table('bot_status', function (Blueprint $table) {
                $table->dropColumn('memory_peak_bytes');
            });
        }
    }
};
