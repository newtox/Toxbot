<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $table->string('starboard_emoji', 1000)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $table->string('starboard_emoji', 80)->nullable()->change();
        });
    }
};
