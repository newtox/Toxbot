<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    public const CREATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'sku_id', 'user_id', 'guild_id', 'type', 'starts_at', 'ends_at', 'deleted', 'consumed'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'deleted' => 'boolean',
            'consumed' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        $now = now('UTC');

        return $query->where('deleted', false)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }
}
