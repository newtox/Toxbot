<?php

namespace App\Services;

use App\Models\ShopItem;
use GdImage;
use Imagick;
use RuntimeException;

class ProfileCard
{
    public const FRAME_SIZE = 196;

    public const AVATAR_X = 107;

    public const AVATAR_Y = 495;

    public const BAR_X1 = 197;

    public const BAR_X2 = 650;

    public const BAR_Y1 = 505;

    public const BAR_Y2 = 531;

    private const SCALE = 2;

    private const W = ShopItem::WIDTH;

    private const H = ShopItem::HEIGHT;

    private const AVATAR_SIZE = 132;

    private const FRAME_DELAY = 6;

    private const MAX_DURATION = 400;

    private const MAX_FRAMES = 32;

    private array $layers = [];

    private ?GdImage $avatarMask = null;

    public function __construct(private readonly ItemFrames $itemFrames) {}

    public function render(array $data): array
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            throw new RuntimeException('The GD extension with FreeType support is required for profile cards.');
        }

        $accent = $this->hex($data['accent']);
        $this->layers = [
            'background' => $this->layer($data['background'] ?? null, self::W, self::H, true),
            'frame' => $this->layer($data['frame'] ?? null, self::FRAME_SIZE, self::FRAME_SIZE, false),
            'bar' => $this->layer($data['bar'] ?? null, self::BAR_X2 - self::BAR_X1, self::BAR_Y2 - self::BAR_Y1, true),
            'avatar' => $this->animatedOnly($this->layer($data['avatar_animated'] ?? null, self::AVATAR_SIZE, self::AVATAR_SIZE, true)),
        ];
        $avatarMask = $this->layers['avatar'] !== null ? $this->circleMask(self::AVATAR_SIZE) : null;

        $overlay = $this->overlay($data, $accent);
        $this->avatarMask = $avatarMask;
        $progress = $data['level']['needed'] > 0 ? min(1, $data['level']['current'] / $data['level']['needed']) : 0;
        $mask = $this->barMask($progress);

        $durations = array_filter(array_map(fn (?array $layer) => $layer['duration'] ?? 0, $this->layers));

        if ($durations === []) {
            $image = $this->compose($overlay, $mask, $accent, 0);
            $png = $this->png($image, 6);
            imagedestroy($image);
            $this->layers = [];
            $this->avatarMask = null;

            return [$png, 'image/png'];
        }

        [$count, $delay] = $this->timeline(min(self::MAX_DURATION, max($durations)));

        $gif = new Imagick;
        for ($i = 0; $i < $count; $i++) {
            $image = $this->compose($overlay, $mask, $accent, $i * $delay);
            $frame = new Imagick;
            $frame->readImageBlob($this->png($image, 1));
            imagedestroy($image);
            $frame->setImageDelay($delay);
            $gif->addImage($frame);
            $frame->clear();
        }
        $this->layers = [];
        $this->avatarMask = null;

        return [$this->gif($gif), 'image/gif'];
    }

    private function timeline(int $duration): array
    {
        for ($delay = self::FRAME_DELAY; $delay <= self::FRAME_DELAY * 2; $delay++) {
            if ($duration % $delay === 0 && $duration / $delay <= self::MAX_FRAMES) {
                return [max(2, intdiv($duration, $delay)), $delay];
            }
        }

        $count = (int) min(self::MAX_FRAMES, max(2, ceil($duration / self::FRAME_DELAY)));

        return [$count, (int) max(self::FRAME_DELAY, round($duration / $count))];
    }

    private function compose(GdImage $overlay, ?GdImage $mask, array $accent, int $time): GdImage
    {
        $img = imagecreatetruecolor(self::W, self::H);
        imagealphablending($img, true);

        $background = $this->at('background', $time);
        if ($background !== null) {
            imagecopy($img, $background, 0, 0, 0, 0, self::W, self::H);
        } else {
            $this->drawDefaultBackground($img, $accent);
        }

        imagecopy($img, $overlay, 0, 0, 0, 0, self::W, self::H);

        if ($mask !== null) {
            $this->drawBarFill($img, $mask, $this->at('bar', $time), $accent);
        }

        $avatar = $this->at('avatar', $time);
        if ($avatar !== null && $this->avatarMask !== null) {
            $half = intdiv(self::AVATAR_SIZE, 2);
            $this->drawMasked($img, $avatar, $this->avatarMask, self::AVATAR_X - $half, self::AVATAR_Y - $half);
        }

        $frame = $this->at('frame', $time);
        if ($frame !== null) {
            $half = intdiv(self::FRAME_SIZE, 2);
            imagecopy($img, $frame, self::AVATAR_X - $half, self::AVATAR_Y - $half, 0, 0, self::FRAME_SIZE, self::FRAME_SIZE);
        }

        return $img;
    }

    private function overlay(array $data, array $accent): GdImage
    {
        $s = self::SCALE;
        $img = $this->transparent(self::W * $s, self::H * $s);
        imagealphablending($img, true);

        $this->roundedRect($img, 25, 415, 675, 575, 14, [10, 12, 16], 45);
        $this->drawAvatar($img, $data['avatar'], self::AVATAR_X, self::AVATAR_Y, 66, $accent, ($data['frame'] ?? null) === null);

        $level = $data['level'];
        $this->text($img, 'LEVEL '.number_format($level['level'], 0, '', ' '), 'Regular', 42, 650, 489, [255, 255, 255], 'right');
        $this->text($img, number_format($level['current'], 0, '', ' '), 'Regular', 15, 197, 489, [235, 235, 235]);
        $currentWidth = $this->textWidth(number_format($level['current'], 0, '', ' '), 'Regular', 15);
        $this->text($img, ' / '.number_format($level['needed'], 0, '', ' ').' XP', 'Regular', 15, 197 + $currentWidth, 489, [150, 155, 165]);

        $this->roundedRect($img, self::BAR_X1, self::BAR_Y1, self::BAR_X2, self::BAR_Y2, 13, [72, 74, 80], 0);

        $this->roundedRect($img, 695, 415, 975, 575, 14, [10, 12, 16], 45);
        $rows = array_slice($data['stats'], 0, 4, true);
        $top = 415 + (160 - count($rows) * 34) / 2 + 24;
        foreach (array_values(array_keys($rows)) as $i => $label) {
            $y = (int) round($top + $i * 34);
            $this->text($img, $label, 'Regular', 15, 717, $y, [200, 204, 212]);
            $this->text($img, $rows[$label], 'Regular', 15, 953, $y, [255, 255, 255], 'right');
        }

        $out = $this->transparent(self::W, self::H);
        imagecopyresampled($out, $img, 0, 0, 0, 0, self::W, self::H, self::W * $s, self::H * $s);
        imagedestroy($img);
        imagealphablending($out, true);

        return $out;
    }

    private function barMask(float $progress): ?GdImage
    {
        if ($progress <= 0) {
            return null;
        }

        $s = self::SCALE;
        $width = self::BAR_X2 - self::BAR_X1;
        $height = self::BAR_Y2 - self::BAR_Y1;
        $fill = (int) round(max($height, $width * $progress));

        $big = $this->transparent($width * $s, $height * $s);
        $white = imagecolorallocatealpha($big, 255, 255, 255, 0);
        $r = $height * $s / 2;
        $right = $fill * $s - 1;
        imagefilledrectangle($big, (int) $r, 0, (int) ($right - $r), $height * $s - 1, $white);
        imagefilledellipse($big, (int) $r, (int) $r, (int) ($r * 2), (int) ($r * 2), $white);
        imagefilledellipse($big, (int) ($right - $r), (int) $r, (int) ($r * 2), (int) ($r * 2), $white);

        $mask = $this->transparent($width, $height);
        imagecopyresampled($mask, $big, 0, 0, 0, 0, $width, $height, $width * $s, $height * $s);
        imagedestroy($big);

        return $mask;
    }

    private function drawBarFill(GdImage $img, GdImage $mask, ?GdImage $style, array $accent): void
    {
        $width = imagesx($mask);
        $height = imagesy($mask);
        $layer = $this->transparent($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = (imagecolorat($mask, $x, $y) >> 24) & 0x7F;
                if ($alpha === 127) {
                    continue;
                }
                if ($style !== null) {
                    $rgb = imagecolorat($style, $x, $y);
                    $sourceAlpha = ($rgb >> 24) & 0x7F;
                    $combined = 127 - (int) round((127 - $alpha) * (127 - $sourceAlpha) / 127);
                    $color = ($combined << 24) | ($rgb & 0xFFFFFF);
                } else {
                    $color = ($alpha << 24) | ($accent[0] << 16) | ($accent[1] << 8) | $accent[2];
                }
                imagesetpixel($layer, $x, $y, $color);
            }
        }

        imagecopy($img, $layer, self::BAR_X1, self::BAR_Y1, 0, 0, $width, $height);
        imagedestroy($layer);
    }

    private function layer(?array $source, int $width, int $height, bool $cover): ?array
    {
        $path = $source['path'] ?? null;
        if (! $path || ! is_file($path)) {
            return null;
        }

        if ($source['animated'] ?? false) {
            $prepared = $this->itemFrames->prepare($path, $width, $height, $cover);
            if ($prepared !== null) {
                return $prepared + ['duration' => array_sum($prepared['delays']), 'index' => null, 'image' => null];
            }
        }

        $image = @imagecreatefromstring((string) @file_get_contents($path));
        if (! $image) {
            return null;
        }

        return ['image' => ItemFrames::fit($image, $width, $height, $cover), 'duration' => 0];
    }

    private function animatedOnly(?array $layer): ?array
    {
        return $layer !== null && $layer['duration'] > 0 ? $layer : null;
    }

    private function circleMask(int $size): GdImage
    {
        $s = self::SCALE;
        $big = $this->transparent($size * $s, $size * $s);
        imagefilledellipse($big, intdiv($size * $s, 2), intdiv($size * $s, 2), $size * $s, $size * $s, imagecolorallocatealpha($big, 255, 255, 255, 0));
        $mask = $this->transparent($size, $size);
        imagecopyresampled($mask, $big, 0, 0, 0, 0, $size, $size, $size * $s, $size * $s);
        imagedestroy($big);

        return $mask;
    }

    private function drawMasked(GdImage $img, GdImage $source, GdImage $mask, int $x0, int $y0): void
    {
        $width = imagesx($mask);
        $height = imagesy($mask);
        $layer = $this->transparent($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = (imagecolorat($mask, $x, $y) >> 24) & 0x7F;
                if ($alpha === 127) {
                    continue;
                }
                imagesetpixel($layer, $x, $y, ($alpha << 24) | (imagecolorat($source, $x, $y) & 0xFFFFFF));
            }
        }

        imagecopy($img, $layer, $x0, $y0, 0, 0, $width, $height);
        imagedestroy($layer);
    }

    private function at(string $name, int $time): ?GdImage
    {
        $layer = $this->layers[$name] ?? null;
        if ($layer === null) {
            return null;
        }
        if ($layer['duration'] === 0) {
            return $layer['image'];
        }

        $time %= $layer['duration'];
        $index = 0;
        foreach ($layer['delays'] as $i => $delay) {
            if ($time < $delay) {
                $index = $i;
                break;
            }
            $time -= $delay;
        }

        if ($layer['index'] !== $index) {
            if ($layer['image'] instanceof GdImage) {
                imagedestroy($layer['image']);
            }
            $image = @imagecreatefrompng($layer['files'][$index]);
            if ($image) {
                imagealphablending($image, true);
            }
            $this->layers[$name]['image'] = $image ?: null;
            $this->layers[$name]['index'] = $index;
        }

        return $this->layers[$name]['image'];
    }

    private function png(GdImage $image, int $level): string
    {
        ob_start();
        imagepng($image, null, $level);

        return (string) ob_get_clean();
    }

    private function gif(Imagick $gif): string
    {
        $gif->quantizeImages(256, Imagick::COLORSPACE_SRGB, 0, true, false);
        $gif = $gif->optimizeImageLayers();
        $gif->quantizeImages(256, Imagick::COLORSPACE_SRGB, 0, false, false);
        $gif->setFormat('gif');
        $gif->setImageIterations(0);

        $blob = $gif->getImagesBlob();
        $gif->clear();

        return $blob;
    }

    private function transparent(int $width, int $height): GdImage
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, imagecolorallocatealpha($img, 0, 0, 0, 127));

        return $img;
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

    private function drawAvatar(GdImage $img, ?string $bytes, int $cx, int $cy, int $r, array $accent, bool $ring): void
    {
        $s = self::SCALE;
        if ($ring) {
            $this->circle($img, $cx, $cy, $r + 4, $accent, 0);
        }

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
                imagesetpixel($img, $x0 + $x, $y0 + $y, imagecolorat($scaled, $x, $y) & 0xFFFFFF);
            }
        }
        imagedestroy($scaled);
    }

    private function roundedRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $radius, array $rgb, int $alpha): void
    {
        $s = self::SCALE;
        [$x1, $y1, $x2, $y2, $r] = [$x1 * $s, $y1 * $s, $x2 * $s - 1, $y2 * $s - 1, $radius * $s];
        $r = (int) min($r, ($x2 - $x1) / 2, ($y2 - $y1) / 2);

        $layer = $this->transparent($x2 - $x1 + 1, $y2 - $y1 + 1);

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
}
