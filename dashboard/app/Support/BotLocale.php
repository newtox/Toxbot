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

    public static function fromDiscord(?string $discordLocale): ?string
    {
        if ($discordLocale === null || $discordLocale === '') {
            return null;
        }

        $locales = collect(config('app.locales'));
        $exact = $locales->first(fn (array $l) => strcasecmp($l['discord'], $discordLocale) === 0);

        if ($exact !== null) {
            return $exact['bot'];
        }

        $base = strtolower(explode('-', $discordLocale)[0]);

        return $locales->first(fn (array $l) => strtolower(explode('-', $l['discord'])[0]) === $base)['bot'] ?? null;
    }

    public static function applyClientLanguage(string $userId, ?string $discordLocale): void
    {
        $language = self::fromDiscord($discordLocale);

        if ($language === null || $language === BotUser::DEFAULT_LANGUAGE) {
            return;
        }

        $settings = BotUser::forDiscordId($userId);

        if ($settings->exists && ($settings->language !== BotUser::DEFAULT_LANGUAGE || (int) $settings->commands_used > 0)) {
            return;
        }

        $settings->language = $language;
        $settings->save();
    }
}
