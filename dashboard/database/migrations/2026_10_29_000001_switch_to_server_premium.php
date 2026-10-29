<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('premium_grants')) {
            DB::table('premium_grants')->where('type', 'user')->delete();
        }

        Schema::dropIfExists('premium_servers');

        if (Schema::hasColumn('backgrounds', 'premium_only')) {
            DB::table('backgrounds')->where('premium_only', true)->where('name', 'Starfield')->where('price', 3000)->update(['price' => 6000]);
            DB::table('backgrounds')->where('premium_only', true)->where('name', 'Ember Ring')->where('price', 3500)->update(['price' => 5000]);

            Schema::table('backgrounds', function (Blueprint $table) {
                $table->dropColumn('premium_only');
            });
        }

        if (! Schema::hasColumn('backgrounds', 'sku_id')) {
            Schema::table('backgrounds', function (Blueprint $table) {
                $table->string('sku_id', 32)->nullable();
            });
        }

        if (Schema::hasColumn('users', 'xp_boost_until')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('xp_boost_until');
            });
        }

        if (! Schema::hasColumn('users', 'tag_slots')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('tag_slots')->default(0);
            });
        }
    }

    public function down(): void {}
};
