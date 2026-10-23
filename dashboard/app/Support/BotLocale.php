<?php

namespace App\Support;

use App\Models\BotUser;

final class BotLocale
{
    public static function forUser(string $userId): string
    {
        $language = BotUser::query()->find($userId)?->language ?? BotUser::DEFAULT_LANGUAGE;
        $locale = collect(config('app.locales'))->search(fn (array $l) => $l['bot'] === $language);

        return $locale === false ? config('app.locale') : $locale;
    }
}
