<?php

namespace Tests\Unit;

use App\Support\Level;
use PHPUnit\Framework\TestCase;

class LevelTest extends TestCase
{
    public function test_matches_the_bot(): void
    {
        $this->assertSame(['level' => 1, 'current' => 0, 'needed' => 200], Level::fromXp(0));
        $this->assertSame(['level' => 1, 'current' => 100, 'needed' => 200], Level::fromXp(100));
        $this->assertSame(['level' => 2, 'current' => 0, 'needed' => 300], Level::fromXp(200));
        $this->assertSame(3, Level::fromXp(500)['level']);
        $this->assertSame(1, Level::fromXp(-5)['level']);
    }
}
