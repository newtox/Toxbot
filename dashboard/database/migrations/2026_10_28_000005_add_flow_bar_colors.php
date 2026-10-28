<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const ITEMS = [
        ['Red Flow Bar', 'bar', 'bar-flow-red.gif', 2500, false],
        ['Orange Flow Bar', 'bar', 'bar-flow-orange.gif', 2500, false],
        ['Yellow Flow Bar', 'bar', 'bar-flow-yellow.gif', 2500, false],
        ['Green Flow Bar', 'bar', 'bar-flow-green.gif', 2500, false],
        ['Cyan Flow Bar', 'bar', 'bar-flow-cyan.gif', 2500, false],
        ['Indigo Flow Bar', 'bar', 'bar-flow-indigo.gif', 2500, false],
        ['Violet Flow Bar', 'bar', 'bar-flow-violet.gif', 2500, false],
        ['Pink Flow Bar', 'bar', 'bar-flow-pink.gif', 2500, false],
        ['Rainbow Flow Bar', 'bar', 'bar-flow-rainbow.gif', 3500, false],
    ];

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $disk = Storage::disk('local');
        $sort = (int) DB::table('backgrounds')->max('sort');

        foreach (self::ITEMS as [$name, $type, $source, $price, $premium]) {
            $path = resource_path("shop/{$source}");
            if (! is_file($path) || DB::table('backgrounds')->where('name', $name)->where('type', $type)->exists()) {
                continue;
            }

            $extension = pathinfo($source, PATHINFO_EXTENSION);
            $file = Str::random(24).'.'.$extension;
            $disk->put("backgrounds/{$file}", file_get_contents($path));

            DB::table('backgrounds')->insert([
                'name' => $name,
                'type' => $type,
                'file' => $file,
                'animated' => $extension === 'gif',
                'price' => $price,
                'premium_only' => $premium,
                'active' => true,
                'sort' => ++$sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {}
};
