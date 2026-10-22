<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamAnnouncement extends Model
{
    protected $fillable = [
        'stream_subscription_id', 'item_id', 'kind', 'channel_id', 'message_id', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(StreamSubscription::class, 'stream_subscription_id');
    }
}
