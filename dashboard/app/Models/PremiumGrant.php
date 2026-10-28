<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumGrant extends Model
{
    public const TYPES = ['user', 'guild'];

    protected $fillable = ['type', 'target_id', 'ends_at', 'note', 'granted_by'];

    protected function casts(): array
    {
        return ['ends_at' => 'datetime'];
    }

    public function scopeActive($query)
    {
        return $query->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now('UTC')));
    }

    public function active(): bool
    {
        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    public static function covers(string $type, string $targetId): bool
    {
        return self::query()->active()->where('type', $type)->where('target_id', $targetId)->exists();
    }
}
