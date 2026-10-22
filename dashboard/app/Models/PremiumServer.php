<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumServer extends Model
{
    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['user_id', 'guild_id'];
}
