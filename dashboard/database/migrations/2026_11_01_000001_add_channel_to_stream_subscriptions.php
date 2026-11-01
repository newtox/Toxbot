<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stream_subscriptions', 'channel')) {
            Schema::table('stream_subscriptions', function (Blueprint $table) {
                $table->string('channel', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stream_subscriptions', 'channel')) {
            Schema::table('stream_subscriptions', fn (Blueprint $table) => $table->dropColumn('channel'));
        }
    }
};
