<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class WebhookDelivery extends Model
{
    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id'];

    public static function firstTime(string $id): bool
    {
        try {
            static::create(['id' => $id]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
