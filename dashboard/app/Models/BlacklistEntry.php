<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistEntry extends Model
{
    protected $table = 'blacklist';

    public $timestamps = false;

    protected $fillable = ['user', 'guild'];
}
