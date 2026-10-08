<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotUser extends Model
{
    public const DEFAULT_LANGUAGE = 'en_us';

    public const DEFAULT_COLOR = '#7289da';

    public const COLOR_REGEX = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'language', 'color'];

    public static function languages(): array
    {
        return collect(config('app.locales'))->mapWithKeys(fn ($l) => [$l['bot'] => $l['name']])->all();
    }
}
