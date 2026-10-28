<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const ITEMS = [
        ['Midnight Waves', 'background', 'midnight-waves.jpg', 500, false],
        ['Sunset Dunes', 'background', 'sunset-dunes.jpg', 500, false],
        ['Forest Hills', 'background', 'forest-hills.jpg', 500, false],
        ['Sakura', 'background', 'sakura.jpg', 750, false],
        ['Low Poly', 'background', 'low-poly.jpg', 750, false],
        ['Aurora', 'background', 'aurora.jpg', 1000, false],
        ['Circuit', 'background', 'circuit.jpg', 1000, false],
        ['Neon Grid', 'background', 'neon-grid.jpg', 1500, false],
        ['Starfield', 'background', 'starfield.gif', 0, true],
        ['Retro Drive', 'background', 'retro-drive.gif', 5000, false],
        ['Pixel Ring', 'frame', 'frame-pixel.png', 750, false],
        ['Neon Ring', 'frame', 'frame-neon.png', 1000, false],
        ['Frost Ring', 'frame', 'frame-frost.png', 1250, false],
        ['Gold Ring', 'frame', 'frame-gold.png', 1500, false],
        ['Rainbow Ring', 'frame', 'frame-rainbow.gif', 4000, false],
        ['Ember Ring', 'frame', 'frame-ember.gif', 0, true],
        ['Sunset Bar', 'bar', 'bar-sunset.png', 300, false],
        ['Pixel Bar', 'bar', 'bar-pixel.png', 300, false],
        ['Candy Bar', 'bar', 'bar-candy.png', 400, false],
        ['Rainbow Bar', 'bar', 'bar-rainbow.png', 500, false],
        ['Neon Bar', 'bar', 'bar-neon.png', 600, false],
        ['Flow Bar', 'bar', 'bar-flow.gif', 2500, false],
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
