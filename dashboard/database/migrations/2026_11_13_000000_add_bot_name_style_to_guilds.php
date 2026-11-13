<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guilds', 'bot_name_style')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->json('bot_name_style')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('guilds', fn (Blueprint $table) => $table->dropColumn('bot_name_style'));
    }
};
