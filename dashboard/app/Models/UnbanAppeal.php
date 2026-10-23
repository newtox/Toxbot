<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnbanAppeal extends Model
{
    protected $fillable = [
        'guild', 'user_id', 'user_name', 'reason', 'status',
        'handled_by', 'handled_by_name', 'response', 'handled_at',
    ];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function scopeForGuild($query, string $guildId)
    {
        return $query->where('guild', $guildId);
    }
}
