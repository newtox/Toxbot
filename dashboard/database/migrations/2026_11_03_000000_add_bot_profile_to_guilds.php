<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            if (! Schema::hasColumn('guilds', 'bot_bio')) {
                $table->string('bot_bio', 190)->nullable();
            }
            if (! Schema::hasColumn('guilds', 'bot_profile')) {
                $table->boolean('bot_profile')->default(false);
            }
        });
    }

    public function down(): void
    {
        foreach (['bot_bio', 'bot_profile'] as $column) {
            if (Schema::hasColumn('guilds', $column)) {
                Schema::table('guilds', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
