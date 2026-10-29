<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guilds', 'slots_enabled')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->boolean('slots_enabled')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('guilds', 'slots_enabled')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->dropColumn('slots_enabled');
            });
        }
    }
};
