<?php

namespace App\Services;

use App\Models\Guild;
use Illuminate\Http\UploadedFile;
use Imagick;

class BotProfile
{
    public const BIO_MAX = 190;

    public const SIZES = ['avatar' => [512, 512, 'png'], 'banner' => [1360, 480, 'jpeg']];

    public function __construct(private readonly Discord $discord) {}

    public function current(string $guildId): array
    {
        $member = rescue(fn () => $this->discord->botMember($guildId), [], report: false);
        $botId = (string) ($member['user']['id'] ?? '');

        return [
            'avatar' => $this->image($guildId, $botId, 'avatars', $member['avatar'] ?? null),
            'banner' => $this->image($guildId, $botId, 'banners', $member['banner'] ?? null),
            'default_avatar' => $this->discord->botAvatarUrl(),
            'name' => $member['nick'] ?? $member['user']['global_name'] ?? $member['user']['username'] ?? 'Toxbot',
        ];
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

        $this->discord->updateBotMember($guild->id, $payload);

        if (array_key_exists('bio', $payload)) {
            $guild->bot_bio = $payload['bio'];
        }
        $guild->bot_profile = true;
        $guild->save();
    }

    public function reset(Guild $guild): void
    {
        if ($guild->bot_profile) {
            rescue(fn () => $this->discord->updateBotMember($guild->id, ['avatar' => null, 'banner' => null, 'bio' => null]), report: false);
        }

        $guild->bot_bio = null;
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
