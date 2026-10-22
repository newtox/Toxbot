<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            if (! Schema::hasColumn('guilds', 'stream_video_msg')) {
                $table->text('stream_video_msg')->nullable();
            }
        });

        Schema::table('stream_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('stream_subscriptions', 'video_message')) {
                $table->text('video_message')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stream_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('stream_subscriptions', 'video_message')) {
                $table->dropColumn('video_message');
            }
        });

        Schema::table('guilds', function (Blueprint $table) {
            if (Schema::hasColumn('guilds', 'stream_video_msg')) {
                $table->dropColumn('stream_video_msg');
            }
        });
    }
};
