<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Imagick;

class ItemFrames
{
    public const DIR = 'item-frames';

    public function prepare(string $path, int $width, int $height, bool $cover): ?array
    {
        if (! class_exists(Imagick::class) || ! is_file($path)) {
            return null;
        }

        $disk = Storage::disk('local');
        $dir = self::DIR.'/'.md5(implode('|', [realpath($path), filemtime($path), filesize($path), $width, $height, $cover ? 1 : 0]));
        $meta = "{$dir}/frames.json";

        if (! $disk->exists($meta)) {
            $delays = $this->extract($path, $width, $height, $cover, $disk->path($dir));
            if ($delays === null) {
                return null;
            }
            $disk->put($meta, json_encode($delays));
        }

        touch($disk->path($meta));
        $delays = json_decode((string) $disk->get($meta), true);
        if (! is_array($delays) || count($delays) < 2) {
            return null;
        }

        return [
            'files' => array_map(fn (int $i) => $disk->path(sprintf('%s/%03d.png', $dir, $i)), array_keys($delays)),
            'delays' => $delays,
        ];
    }

    public static function fit(GdImage $source, int $width, int $height, bool $cover): GdImage
    {
        $out = imagecreatetruecolor($width, $height);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefilledrectangle($out, 0, 0, $width - 1, $height - 1, imagecolorallocatealpha($out, 0, 0, 0, 127));

        $sw = imagesx($source);
        $sh = imagesy($source);
        if ($cover) {
            $ratio = max($width / $sw, $height / $sh);
            $cw = (int) round($width / $ratio);
            $ch = (int) round($height / $ratio);
            imagecopyresampled($out, $source, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $width, $height, $cw, $ch);
        } else {
            imagecopyresampled($out, $source, 0, 0, 0, 0, $width, $height, $sw, $sh);
        }

        imagealphablending($out, true);

        return $out;
    }

    private function extract(string $path, int $width, int $height, bool $cover, string $target): ?array
    {
        try {
            $im = (new Imagick($path))->coalesceImages();
        } catch (\Throwable) {
            return null;
        }

        if ($im->getNumberImages() < 2) {
            $im->clear();

            return null;
        }

        if (! is_dir($target)) {
            mkdir($target, 0775, true);
        }

        $delays = [];
        foreach ($im as $i => $part) {
            $part->setImageFormat('png');
            $source = @imagecreatefromstring($part->getImageBlob());
            if (! $source) {
                continue;
            }
            $frame = self::fit($source, $width, $height, $cover);
            imagedestroy($source);
            imagesavealpha($frame, true);
            imagepng($frame, sprintf('%s/%03d.png', $target, count($delays)), 1);
            imagedestroy($frame);
            $delays[] = max(2, (int) $part->getImageDelay());
        }
        $im->clear();

        return count($delays) > 1 ? $delays : null;
    }
}
