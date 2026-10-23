<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarnRule extends Model
{
    public const ACTIONS = ['timeout', 'kick', 'ban'];

    protected $fillable = ['guild', 'warns', 'action', 'duration'];

    protected function casts(): array
    {
        return ['warns' => 'integer', 'duration' => 'integer'];
    }
}
