<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberLevel extends Model
{
    protected $table = 'member_levels';

    public $timestamps = false;

    protected $fillable = ['guild', 'user', 'xp', 'xp_at'];

    protected function casts(): array
    {
        return ['xp' => 'integer', 'xp_at' => 'datetime'];
    }
}
