<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_users', function (Blueprint $table) {
            if (! Schema::hasColumn('dashboard_users', 'discord_access_token')) {
                $table->text('discord_access_token')->nullable();
                $table->text('discord_refresh_token')->nullable();
                $table->dateTime('discord_token_expires_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_users', function (Blueprint $table) {
            if (Schema::hasColumn('dashboard_users', 'discord_access_token')) {
                $table->dropColumn(['discord_access_token', 'discord_refresh_token', 'discord_token_expires_at']);
            }
        });
    }
};
