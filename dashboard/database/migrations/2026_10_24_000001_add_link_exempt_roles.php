<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guilds', 'link_exempt_roles')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->text('link_exempt_roles')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('guilds', 'link_exempt_roles')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->dropColumn('link_exempt_roles');
            });
        }
    }
};
