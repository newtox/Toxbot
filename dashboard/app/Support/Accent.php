<?php

namespace App\Support;

use App\Models\BotUser;
use App\Models\DashboardUser;

class Accent
{
    public const DEFAULT = '#6dbe33';

    private const BACKGROUND = '#1e1f22';

    private const DARK_TEXT = '#101113';

    public static function forUser(?DashboardUser $user): array
    {
        $color = $user ? (string) $user->botSettings()->color : self::DEFAULT;

        return self::palette($color);
    }

    public static function palette(string $color): array
    {
        $accent = self::normalize($color);

        return [
            'accent' => $accent,
            'on' => self::onAccent($accent),
            'fg' => self::readable($accent),
        ];
    }

    public static function normalize(string $hex): string
    {
        if (! preg_match(BotUser::COLOR_REGEX, $hex)) {
            return self::DEFAULT;
        }

        $hex = strtolower($hex);

        if (strlen($hex) === 4) {
            $hex = '#'.$hex[1].$hex[1].$hex[2].$hex[2].$hex[3].$hex[3];
        }

        return $hex;
    }

    public static function onAccent(string $hex): string
    {
        return self::contrast($hex, '#ffffff') >= 3 ? '#ffffff' : self::DARK_TEXT;
    }

    public static function readable(string $hex): string
    {
        for ($t = 0.0; $t <= 1.0; $t += 0.1) {
            $mixed = self::mix($hex, '#ffffff', $t);

            if (self::contrast($mixed, self::BACKGROUND) >= 4.5) {
                return $mixed;
            }
        }

        return '#ffffff';
    }

    public static function contrast(string $a, string $b): float
    {
        [$hi, $lo] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function (int $v) {
            $c = $v / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function mix(string $a, string $b, float $t): string
    {
        $ca = self::rgb($a);
        $cb = self::rgb($b);

        return sprintf('#%02x%02x%02x', ...array_map(
            fn ($i) => (int) round($ca[$i] + ($cb[$i] - $ca[$i]) * $t),
            [0, 1, 2],
        ));
    }

    private static function rgb(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }
}
