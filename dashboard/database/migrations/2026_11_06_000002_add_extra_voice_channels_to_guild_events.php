<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guild_events', 'extra_voice_channel_ids')) {
            Schema::table('guild_events', function (Blueprint $table) {
                $table->string('extra_voice_channel_ids', 200)->nullable()->after('voice_channel_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('guild_events', 'extra_voice_channel_ids')) {
            Schema::table('guild_events', fn (Blueprint $table) => $table->dropColumn('extra_voice_channel_ids'));
        }
    }
};
