<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected function casts(): array
    {
        return ['number' => 'integer', 'closed_at' => 'datetime'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('sent_at')->orderBy('id');
    }

    public function scopeForGuild($query, string $guildId)
    {
        return $query->where('guild', $guildId);
    }
}
