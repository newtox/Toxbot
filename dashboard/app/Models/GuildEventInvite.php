<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuildEventInvite extends Model
{
    public const PENDING = 'pending';

    public const DECLINED = 'declined';

    protected $fillable = ['event_id', 'user_id', 'status', 'invited_by'];
}
