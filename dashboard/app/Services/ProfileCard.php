<?php

namespace App\Services;

use App\Models\Background;
use GdImage;
use RuntimeException;

class ProfileCard
{
    private const SCALE = 2;

    private const W = Background::WIDTH;

    private const H = Background::HEIGHT;

    public function render(array $data): string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            throw new RuntimeException('The GD extension with FreeType support is required for profile cards.');
        }

        $s = self::SCALE;
        $img = imagecreatetruecolor(self::W * $s, self::H * $s);
        imagealphablending($img, true);
        imagesavealpha($img, false);

        $accent = $this->hex($data['accent']);

        $this->drawBackground($img, $data['background'], $accent);

        $this->roundedRect($img, 25, 415, 675, 575, 14, [10, 12, 16], 45);
        $this->drawAvatar($img, $data['avatar'], 107, 495, 66, $accent);

        $level = $data['level'];
        $this->text($img, 'LEVEL '.number_format($level['level'], 0, '', ' '), 'Regular', 42, 650, 489, [255, 255, 255], 'right');
        $this->text($img, number_format($level['current'], 0, '', ' '), 'Regular', 15, 197, 489, [235, 235, 235]);
        $currentWidth = $this->textWidth(number_format($level['current'], 0, '', ' '), 'Regular', 15);
        $this->text($img, ' / '.number_format($level['needed'], 0, '', ' ').' XP', 'Regular', 15, 197 + $currentWidth, 489, [150, 155, 165]);

        $barX1 = 197;
        $barX2 = 650;
        $this->roundedRect($img, $barX1, 505, $barX2, 531, 13, [72, 74, 80], 0);
        $progress = $level['needed'] > 0 ? min(1, $level['current'] / $level['needed']) : 0;
        if ($progress > 0) {
            $fillEnd = (int) round($barX1 + max(26, ($barX2 - $barX1) * $progress));
            $this->roundedRect($img, $barX1, 505, $fillEnd, 531, 13, $accent, 0);
        }

        $this->roundedRect($img, 695, 415, 975, 575, 14, [10, 12, 16], 45);
        $rows = array_slice($data['stats'], 0, 4, true);
        $top = 415 + (160 - count($rows) * 34) / 2 + 24;
        foreach (array_values(array_keys($rows)) as $i => $label) {
            $y = (int) round($top + $i * 34);
            $this->text($img, $label, 'Regular', 15, 717, $y, [200, 204, 212]);
            $this->text($img, $rows[$label], 'Regular', 15, 953, $y, [255, 255, 255], 'right');
        }

        if ($data['premium']) {
            $label = 'PREMIUM';
            $width = $this->textWidth($label, 'Bold', 11) + 28;
            $this->roundedRect($img, 25, 25, 25 + $width, 57, 16, $accent, 0);
            $this->text($img, $label, 'Bold', 11, 25 + 14, 47, $this->onColor($accent));
        }

        $out = imagecreatetruecolor(self::W, self::H);
        imagecopyresampled($out, $img, 0, 0, 0, 0, self::W, self::H, self::W * $s, self::H * $s);
        imagedestroy($img);

        ob_start();
        imagepng($out, null, 6);
        imagedestroy($out);

        return (string) ob_get_clean();
    }

    private function drawBackground(GdImage $img, ?string $path, array $accent): void
    {
        $source = $path ? @imagecreatefromstring((string) @file_get_contents($path)) : false;

        if (! $source) {
            $this->drawDefaultBackground($img, $accent);

            return;
        }

        $sw = imagesx($source);
        $sh = imagesy($source);
        $ratio = max(imagesx($img) / $sw, imagesy($img) / $sh);
        $cw = (int) round(imagesx($img) / $ratio);
        $ch = (int) round(imagesy($img) / $ratio);
        imagecopyresampled($img, $source, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), imagesx($img), imagesy($img), $cw, $ch);
        imagedestroy($source);
    }

    private function drawDefaultBackground(GdImage $img, array $accent): void
    {
        $top = [24, 25, 28];
        $bottom = array_map(fn ($c, $base) => (int) round($base + ($c - $base) * 0.35), $accent, [17, 18, 20]);
        $height = imagesy($img);
        $width = imagesx($img);

        for ($y = 0; $y < $height; $y++) {
            $t = $y / max(1, $height - 1);
            $color = imagecolorallocate(
                $img,
                (int) round($top[0] + ($bottom[0] - $top[0]) * $t),
                (int) round($top[1] + ($bottom[1] - $top[1]) * $t),
                (int) round($top[2] + ($bottom[2] - $top[2]) * $t),
            );
            imageline($img, 0, $y, $width - 1, $y, $color);
        }
    }

    private function drawAvatar(GdImage $img, ?string $bytes, int $cx, int $cy, int $r, array $accent): void
    {
        $s = self::SCALE;
        $this->circle($img, $cx, $cy, $r + 4, $accent, 0);

        $avatar = $bytes ? @imagecreatefromstring($bytes) : false;
        if (! $avatar) {
            $this->circle($img, $cx, $cy, $r, [88, 101, 242], 0);

            return;
        }

        $size = $r * 2 * $s;
        $scaled = imagecreatetruecolor($size, $size);
        imagecopyresampled($scaled, $avatar, 0, 0, 0, 0, $size, $size, imagesx($avatar), imagesy($avatar));
        imagedestroy($avatar);

        $x0 = ($cx - $r) * $s;
        $y0 = ($cy - $r) * $s;
        $radius = $size / 2;
        for ($y = 0; $y < $size; $y++) {
            $dy = $y - $radius + 0.5;
            $half = sqrt(max(0, $radius * $radius - $dy * $dy));
            $from = (int) floor($radius - $half);
            $to = (int) ceil($radius + $half);
            for ($x = $from; $x < $to; $x++) {
                imagesetpixel($img, $x0 + $x, $y0 + $y, imagecolorat($scaled, $x, $y));
            }
        }
        imagedestroy($scaled);
    }

    private function roundedRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $radius, array $rgb, int $alpha): void
    {
        $s = self::SCALE;
        [$x1, $y1, $x2, $y2, $r] = [$x1 * $s, $y1 * $s, $x2 * $s - 1, $y2 * $s - 1, $radius * $s];
        $r = (int) min($r, ($x2 - $x1) / 2, ($y2 - $y1) / 2);

        $layer = imagecreatetruecolor($x2 - $x1 + 1, $y2 - $y1 + 1);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));

        $color = imagecolorallocatealpha($layer, $rgb[0], $rgb[1], $rgb[2], $alpha);
        $w = $x2 - $x1;
        $h = $y2 - $y1;
        imagefilledrectangle($layer, $r, 0, $w - $r, $h, $color);
        imagefilledrectangle($layer, 0, $r, $w, $h - $r, $color);
        foreach ([[$r, $r], [$w - $r, $r], [$r, $h - $r], [$w - $r, $h - $r]] as [$cx, $cy]) {
            imagefilledellipse($layer, $cx, $cy, $r * 2, $r * 2, $color);
        }

        imagecopy($img, $layer, $x1, $y1, 0, 0, $w + 1, $h + 1);
        imagedestroy($layer);
    }

    private function circle(GdImage $img, int $cx, int $cy, int $r, array $rgb, int $alpha): void
    {
        $s = self::SCALE;
        $color = imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], $alpha);
        imagefilledellipse($img, $cx * $s, $cy * $s, $r * 2 * $s, $r * 2 * $s, $color);
    }

    private function text(GdImage $img, string $text, string $weight, float $size, int $x, int $baseline, array $rgb, string $align = 'left'): void
    {
        $s = self::SCALE;
        if ($align === 'right') {
            $x -= $this->textWidth($text, $weight, $size);
        }
        $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        imagettftext($img, $size * $s, 0, $x * $s, $baseline * $s, $color, $this->font($weight), $text);
    }

    private function textWidth(string $text, string $weight, float $size): int
    {
        $box = imagettfbbox($size * self::SCALE, 0, $this->font($weight), $text);

        return (int) round(($box[2] - $box[0]) / self::SCALE);
    }

    private function font(string $weight): string
    {
        return resource_path("fonts/Rubik-{$weight}.ttf");
    }

    private function hex(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $hex = '7289da';
        }

        return array_map('hexdec', str_split($hex, 2));
    }

    private function onColor(array $rgb): array
    {
        $luminance = (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255;

        return $luminance > 0.6 ? [16, 17, 19] : [255, 255, 255];
    }
}
