<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEEP = ['498132065171275786'];

    public function up(): void
    {
        $enabled = DB::table('guilds')->where('levels_enabled', true)->pluck('id')->map(fn ($id) => (string) $id)->all();

        DB::table('member_levels')->whereNotIn('guild', [...$enabled, ...self::KEEP])->delete();
    }

    public function down(): void {}
};
