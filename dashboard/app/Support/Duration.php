<?php

namespace App\Support;

class Duration
{
    private const UNITS = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];

    public static function parse(?string $input): ?int
    {
        $input = strtolower(trim((string) $input));
        if ($input === '' || ! preg_match('/^(\d+\s*[smhdw]\s*)+$/', $input)) {
            return null;
        }

        preg_match_all('/(\d+)\s*([smhdw])/', $input, $matches, PREG_SET_ORDER);

        $seconds = 0;
        foreach ($matches as [, $value, $unit]) {
            $seconds += (int) $value * self::UNITS[$unit];
        }

        return $seconds > 0 ? $seconds : null;
    }

    public static function format(?int $seconds): string
    {
        if (! $seconds) {
            return '–';
        }

        $parts = [];
        foreach (['d' => 86400, 'h' => 3600, 'm' => 60, 's' => 1] as $unit => $size) {
            if ($seconds >= $size) {
                $parts[] = intdiv($seconds, $size).$unit;
                $seconds %= $size;
            }
        }

        return implode(' ', $parts);
    }
}
