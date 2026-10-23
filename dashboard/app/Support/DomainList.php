<?php

namespace App\Support;

class DomainList
{
    private const PATTERN = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{1,62}$/';

    public static function entries(string $input): array
    {
        return collect(preg_split('/[\s,;]+/', mb_strtolower($input)) ?: [])
            ->map(fn (string $entry) => self::normalize($entry))
            ->filter(fn (string $entry) => $entry !== '')
            ->unique()
            ->values()
            ->all();
    }

    public static function invalid(string $input): array
    {
        return array_values(array_filter(self::entries($input), fn (string $entry) => ! preg_match(self::PATTERN, $entry)));
    }

    public static function serialize(string $input): ?string
    {
        $valid = array_filter(self::entries($input), fn (string $entry) => preg_match(self::PATTERN, $entry));

        return $valid === [] ? null : implode("\n", $valid);
    }

    private static function normalize(string $entry): string
    {
        $entry = preg_replace('~^[a-z][a-z0-9+.-]*://~', '', trim($entry));
        $entry = preg_replace('~[/?#].*$~', '', $entry);
        $entry = preg_replace('~^.*@~', '', $entry);
        $entry = preg_replace('~:\d+$~', '', $entry);
        $entry = preg_replace('~^(\*\.|www\.)~', '', $entry);

        return rtrim($entry, '.');
    }
}
