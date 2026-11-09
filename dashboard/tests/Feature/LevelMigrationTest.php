<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LevelMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_xp_is_reset_without_paying_level_rewards_twice(): void
    {
        $migration = require database_path('migrations/2026_11_09_000000_make_server_levels_opt_in.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('users', 'level_rewarded'));

        DB::table('users')->insert([
            ['id' => '1', 'language' => 'en_us', 'color' => '#7289da', 'xp' => 5000, 'credits' => 70, 'xp_at' => now()],
            ['id' => '2', 'language' => 'en_us', 'color' => '#7289da', 'xp' => 0, 'credits' => 0, 'xp_at' => null],
        ]);
        DB::table('guilds')->insert([['id' => '10', 'levelup_enabled' => true], ['id' => '20', 'levelup_enabled' => false]]);

        $migration->up();

        $this->assertSame(
            [['1', 0, 9, 70, null], ['2', 0, 0, 0, null]],
            DB::table('users')->orderBy('id')->get()->map(fn ($u) => [$u->id, (int) $u->xp, (int) $u->level_rewarded, (int) $u->credits, $u->xp_at])->all(),
        );
        $this->assertSame(['10' => 1, '20' => 0], DB::table('guilds')->pluck('levels_enabled', 'id')->map(fn ($v) => (int) $v)->all());
    }
}
