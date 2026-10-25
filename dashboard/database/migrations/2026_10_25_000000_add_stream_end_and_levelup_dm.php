<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            if (! Schema::hasColumn('guilds', 'stream_end_action')) {
                $table->string('stream_end_action', 10)->default('keep');
            }
            if (! Schema::hasColumn('guilds', 'levelup_dm')) {
                $table->boolean('levelup_dm')->default(false);
            }
        });

        DB::table('stream_subscriptions')->where('end_action', 'keep')->update(['end_action' => 'default']);
    }

    public function down(): void
    {
        DB::table('stream_subscriptions')->where('end_action', 'default')->update(['end_action' => 'keep']);

        Schema::table('guilds', function (Blueprint $table) {
            foreach (['stream_end_action', 'levelup_dm'] as $column) {
                if (Schema::hasColumn('guilds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
