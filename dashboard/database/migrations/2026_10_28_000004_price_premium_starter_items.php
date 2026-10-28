<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRICES = [
        ['Starfield', 'background', 3000],
        ['Ember Ring', 'frame', 3500],
    ];

    public function up(): void
    {
        foreach (self::PRICES as [$name, $type, $price]) {
            DB::table('backgrounds')
                ->where('name', $name)
                ->where('type', $type)
                ->where('price', 0)
                ->where('premium_only', true)
                ->update(['price' => $price, 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};
