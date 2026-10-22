<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Background extends Model
{
    public const WIDTH = 1000;

    public const HEIGHT = 600;

    protected $fillable = ['name', 'file', 'price', 'premium_only', 'active', 'sort'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'premium_only' => 'boolean',
            'active' => 'boolean',
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

    public function scopeInShop($query)
    {
        return $query->where('active', true)->orderBy('sort')->orderBy('price')->orderBy('id');
    }
}
