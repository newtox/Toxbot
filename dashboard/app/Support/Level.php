<?php

namespace App\Support;

final class Level
{
    public static function xpToNext(int $level): int
    {
        return 100 * $level + 100;
    }

    public static function fromXp(int $xp): array
    {
        $level = 1;
        $xp = max(0, $xp);

        while ($xp >= self::xpToNext($level)) {
            $xp -= self::xpToNext($level);
            $level++;
        }

        return ['level' => $level, 'current' => $xp, 'needed' => self::xpToNext($level)];
    }
}
