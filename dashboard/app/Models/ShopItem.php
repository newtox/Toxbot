<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ShopItem extends Model
{
    public const WIDTH = 1000;

    public const HEIGHT = 600;

    public const BACKGROUND = 'background';

    public const FRAME = 'frame';

    public const BAR = 'bar';

    public const TYPES = [self::BACKGROUND, self::FRAME, self::BAR];

    public const COLUMNS = [
        self::BACKGROUND => 'background_id',
        self::FRAME => 'frame_id',
        self::BAR => 'bar_id',
    ];

    protected $table = 'backgrounds';

    protected $fillable = ['name', 'type', 'file', 'animated', 'price', 'premium_only', 'active', 'sort'];

    protected $attributes = [
        'type' => self::BACKGROUND,
        'animated' => false,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'premium_only' => 'boolean',
            'active' => 'boolean',
            'animated' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public static function pathFor(string $file): ?string
    {
        $file = basename($file);
        $disk = Storage::disk('local');

        return $disk->exists("backgrounds/{$file}") ? $disk->path("backgrounds/{$file}") : null;
    }

    public function path(): ?string
    {
        return self::pathFor($this->file);
    }

    public function url(): string
    {
        return route('backgrounds.show', ['background' => $this->id, 'v' => $this->updated_at?->timestamp]);
    }

    public function column(): string
    {
        return self::COLUMNS[$this->type] ?? self::COLUMNS[self::BACKGROUND];
    }

    public function isFree(): bool
    {
        return $this->price === 0 && ! $this->premium_only;
    }

    public function scopeInShop($query)
    {
        return $query->where('active', true)->orderBy('sort')->orderBy('price')->orderBy('id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
