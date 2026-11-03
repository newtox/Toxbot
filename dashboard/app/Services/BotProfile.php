<?php

namespace App\Services;

use App\Models\Guild;
use Illuminate\Http\UploadedFile;

class BotProfile
{
    public const BIO_MAX = 190;

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
                $payload[$key] = $changes[$key] instanceof UploadedFile ? $this->dataUri($changes[$key]) : null;
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

    private function image(string $guildId, string $botId, string $type, ?string $hash): ?string
    {
        if (! $hash || $botId === '') {
            return null;
        }
        $ext = str_starts_with($hash, 'a_') ? 'gif' : 'png';

        return "https://cdn.discordapp.com/guilds/{$guildId}/users/{$botId}/{$type}/{$hash}.{$ext}?size=512";
    }

    private function dataUri(UploadedFile $file): string
    {
        return 'data:'.$file->getMimeType().';base64,'.base64_encode((string) file_get_contents($file->getRealPath()));
    }
}
