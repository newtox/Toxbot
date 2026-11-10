<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guilds', 'readonly_threads')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->boolean('readonly_threads')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::table('guilds', fn (Blueprint $table) => $table->dropColumn('readonly_threads'));
    }
};
