<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['author_bot' => 'boolean', 'attachments' => 'array', 'sent_at' => 'datetime'];
    }
}
