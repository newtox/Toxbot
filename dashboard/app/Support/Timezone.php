<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

final class Timezone
{
    public const COOKIE = 'tz';

    public static function current(): string
    {
        $timezone = (string) request()->cookie(self::COOKIE);

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('app.timezone');
    }

    public static function format(?DateTimeInterface $value, string $format = 'lll'): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::instance($value)
            ->setTimezone(self::current())
            ->locale(app()->getLocale())
            ->isoFormat($format);
    }
}
