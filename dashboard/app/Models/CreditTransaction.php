<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user', 'amount', 'reason', 'reference'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }
}
