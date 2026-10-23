<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModAction extends Model
{
    public const TYPES = ['warn', 'timeout', 'untimeout', 'kick', 'ban', 'unban'];

    protected $fillable = [
        'guild', 'type', 'user_id', 'moderator_id', 'moderator_name',
        'reason', 'duration', 'delete_days', 'status',
    ];

    protected $attributes = [
        'status' => 'pending',
        'delete_days' => 0,
    ];

    protected function casts(): array
    {
        return ['duration' => 'integer', 'delete_days' => 'integer'];
    }

    public function modCase(): BelongsTo
    {
        return $this->belongsTo(ModCase::class, 'case_id');
    }

    public function finished(): bool
    {
        return in_array($this->status, ['done', 'failed'], true);
    }
}
