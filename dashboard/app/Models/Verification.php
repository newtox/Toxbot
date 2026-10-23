<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    public $timestamps = false;

    protected $fillable = ['guild', 'user_id', 'created_at', 'verified_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'verified_at' => 'datetime'];
    }
}
