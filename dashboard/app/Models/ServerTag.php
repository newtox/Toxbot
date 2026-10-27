<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerTag extends Model
{
    public const FREE_LIMIT = 20;

    public const PREMIUM_LIMIT = 100;

    protected $fillable = ['guild', 'name', 'content', 'created_by'];

    protected function casts(): array
    {
        return ['uses' => 'integer'];
    }
}
