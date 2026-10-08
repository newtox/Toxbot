<?php

namespace Tests\Unit;

use App\Support\Accent;
use PHPUnit\Framework\TestCase;

class AccentTest extends TestCase
{
    public function test_invalid_colors_fall_back_to_toxbot_green(): void
    {
        $this->assertSame(Accent::DEFAULT, Accent::normalize('nope'));
        $this->assertSame('#aabbcc', Accent::normalize('#ABC'));
    }

    public function test_text_on_accent_stays_readable(): void
    {
        $this->assertSame('#101113', Accent::palette('#6dbe33')['on']);
        $this->assertSame('#ffffff', Accent::palette('#5865f2')['on']);
    }

    public function test_dark_accents_are_lightened_for_text(): void
    {
        $fg = Accent::palette('#000000')['fg'];

        $this->assertNotSame('#000000', $fg);
        $this->assertGreaterThanOrEqual(4.5, Accent::contrast($fg, '#1e1f22'));
    }
}
