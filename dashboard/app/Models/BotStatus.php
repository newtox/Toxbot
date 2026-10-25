<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotStatus extends Model
{
    public const OFFLINE_AFTER_MINUTES = 3;

    protected $table = 'bot_status';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'heartbeat_at' => 'datetime',
            'guilds' => 'integer',
            'latency_ms' => 'integer',
            'memory_bytes' => 'integer',
        ];
    }

    public static function current(): ?self
    {
        return rescue(fn () => static::query()->find(1), null, report: false);
    }

    public function online(): bool
    {
        return $this->heartbeat_at !== null && $this->heartbeat_at->gt(now()->subMinutes(self::OFFLINE_AFTER_MINUTES));
    }
}
