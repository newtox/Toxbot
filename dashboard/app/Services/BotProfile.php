<?php

namespace App\Services;

use App\Models\Guild;
use Illuminate\Http\UploadedFile;
use Imagick;

class BotProfile
{
    public const BIO_MAX = 190;

    public const FONTS = [11, 12, 3, 4, 6, 7, 8, 10, 13, 14, 15, 16];

    public const EFFECTS = [1 => 'solid', 2 => 'gradient', 3 => 'neon', 4 => 'toon', 5 => 'pop', 8 => 'gummy', 7 => 'prism'];

    public const COLORS = [1 => [1, 1], 2 => [2, 2], 3 => [1, 1], 4 => [1, 1], 5 => [1, 1], 8 => [2, 5], 7 => [2, 5]];

    public const DEFAULT_STYLE = ['font' => 11, 'effect' => 1, 'colors' => []];

    public const SIZES = ['avatar' => [512, 512, 'png'], 'banner' => [1360, 480, 'jpeg']];

    public function __construct(private readonly Discord $discord) {}

    public function current(string $guildId): array
    {
        $member = rescue(fn () => $this->discord->botMember($guildId), [], report: false);
        $botId = (string) ($member['user']['id'] ?? '');

        $user = rescue(fn () => $this->discord->botUser(), [], report: false);
        $discriminator = (string) ($user['discriminator'] ?? '0');

        return [
            'verified' => self::isVerified($user),
            'tag' => ($user['username'] ?? 'Toxbot').($discriminator !== '0' ? '#'.$discriminator : ''),
            'avatar' => $this->image($guildId, $botId, 'avatars', $member['avatar'] ?? null),
            'banner' => $this->image($guildId, $botId, 'banners', $member['banner'] ?? null),
            'default_avatar' => $this->discord->botAvatarUrl() ?? 'https://cdn.discordapp.com/embed/avatars/0.png',
            'name' => $member['nick'] ?? $member['user']['global_name'] ?? $member['user']['username'] ?? 'Toxbot',
        ];
    }

    public static function stylePayload(array $style): array
    {
        return [
            'display_name_font_id' => (int) $style['font'],
            'display_name_effect_id' => (int) $style['effect'],
            'display_name_colors' => array_map(fn (string $hex) => hexdec(ltrim($hex, '#')), $style['colors']),
        ];
    }

    public static function isVerified(array $user): bool
    {
        return ((int) ($user['public_flags'] ?? $user['flags'] ?? 0) & (1 << 16)) !== 0;
    }

    public function update(Guild $guild, array $changes): void
    {
        $payload = [];
        foreach (['avatar', 'banner'] as $key) {
            if (array_key_exists($key, $changes)) {
                $payload[$key] = $changes[$key] !== null ? $this->prepare($changes[$key]['file'], $changes[$key]['crop'] ?? null, ...self::SIZES[$key]) : null;
            }
        }
        if (array_key_exists('bio', $changes)) {
            $payload['bio'] = $changes['bio'] !== '' ? $changes['bio'] : null;
        }
        if (array_key_exists('style', $changes)) {
            $payload += self::stylePayload($changes['style'] ?? self::DEFAULT_STYLE);
        }

        $this->discord->updateBotMember($guild->id, $payload);

        if (array_key_exists('bio', $payload)) {
            $guild->bot_bio = $payload['bio'];
        }
        if (array_key_exists('style', $changes)) {
            $guild->bot_name_style = $changes['style'];
        }
        $guild->bot_profile = true;
        $guild->save();
    }

    public function reset(Guild $guild): void
    {
        if ($guild->bot_profile) {
            $payload = ['avatar' => null, 'banner' => null, 'bio' => null];
            if ($guild->bot_name_style !== null) {
                $payload += self::stylePayload(self::DEFAULT_STYLE);
            }
            rescue(fn () => $this->discord->updateBotMember($guild->id, $payload), report: false);
        }

        $guild->bot_bio = null;
        $guild->bot_name_style = null;
        $guild->bot_profile = false;
        if ($guild->exists) {
            $guild->save();
        }
    }

    public function prepare(UploadedFile $file, ?array $crop, int $width, int $height, string $format = 'png'): string
    {
        $image = new Imagick($file->getRealPath());
        $animated = $image->getNumberImages() > 1;
        if ($animated) {
            $image = $image->coalesceImages();
            [$width, $height] = [intdiv($width, 2), intdiv($height, 2)];
        }

        $sourceWidth = $image->getImageWidth();
        $sourceHeight = $image->getImageHeight();
        [$x, $y, $w, $h] = $crop ?? $this->cover($sourceWidth / $sourceHeight, $width / $height);

        $left = min(max(0, (int) round($x * $sourceWidth)), $sourceWidth - 1);
        $top = min(max(0, (int) round($y * $sourceHeight)), $sourceHeight - 1);
        $cropWidth = min(max(1, (int) round($w * $sourceWidth)), $sourceWidth - $left);
        $cropHeight = min(max(1, (int) round($h * $sourceHeight)), $sourceHeight - $top);

        foreach ($image as $frame) {
            $frame->cropImage($cropWidth, $cropHeight, $left, $top);
            $frame->setImagePage(0, 0, 0, 0);
            $frame->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
        }

        if ($animated) {
            $image = $image->deconstructImages();
            $image->setFormat('gif');

            return 'data:image/gif;base64,'.base64_encode($image->getImagesBlob());
        }

        if ($format === 'jpeg') {
            $image->setImageBackgroundColor('black');
            $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageCompressionQuality(90);
        }
        $image->setImageFormat($format);

        return "data:image/{$format};base64,".base64_encode($image->getImageBlob());
    }

    private function image(string $guildId, string $botId, string $type, ?string $hash): ?string
    {
        if (! $hash || $botId === '') {
            return null;
        }
        $ext = str_starts_with($hash, 'a_') ? 'gif' : 'png';

        return "https://cdn.discordapp.com/guilds/{$guildId}/users/{$botId}/{$type}/{$hash}.{$ext}?size=512";
    }

    private function cover(float $sourceRatio, float $targetRatio): array
    {
        if ($sourceRatio > $targetRatio) {
            $w = $targetRatio / $sourceRatio;

            return [(1 - $w) / 2, 0.0, $w, 1.0];
        }
        $h = $sourceRatio / $targetRatio;

        return [0.0, (1 - $h) / 2, 1.0, $h];
    }
}
