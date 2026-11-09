<?php

use App\Support\Level;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guilds', 'levels_enabled')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->boolean('levels_enabled')->default(false);
            });
            DB::table('guilds')->where('levelup_enabled', true)->update(['levels_enabled' => true]);
        }

        if (! Schema::hasColumn('users', 'level_rewarded')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('level_rewarded')->default(0);
            });

            DB::table('users')->where('xp', '>', 0)->orderBy('id')->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'level_rewarded' => Level::fromXp((int) $user->xp)['level'],
                        'xp' => 0,
                        'xp_at' => null,
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('guilds', fn (Blueprint $table) => $table->dropColumn('levels_enabled'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('level_rewarded'));
    }
};
