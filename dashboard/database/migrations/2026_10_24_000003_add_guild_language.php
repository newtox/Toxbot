<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guilds', 'language')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->string('language', 16)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('guilds', 'language')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->dropColumn('language');
            });
        }
    }
};
