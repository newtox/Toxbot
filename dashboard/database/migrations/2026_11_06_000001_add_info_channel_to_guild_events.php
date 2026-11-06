<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guild_events', function (Blueprint $table) {
            if (! Schema::hasColumn('guild_events', 'team_role_id')) {
                $table->string('team_role_id', 32)->nullable()->after('role_id');
            }
            if (! Schema::hasColumn('guild_events', 'info_channel_id')) {
                $table->string('info_channel_id', 32)->nullable()->after('category_id');
            }
        });
    }

    public function down(): void
    {
        foreach (['team_role_id', 'info_channel_id'] as $column) {
            if (Schema::hasColumn('guild_events', $column)) {
                Schema::table('guild_events', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
