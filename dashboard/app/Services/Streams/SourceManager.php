<?php

namespace App\Services\Streams;

use App\Models\StreamSource;
use App\Models\StreamSubscription;
use App\Services\Premium;
use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

class SourceManager
{
    public function __construct(
        private readonly Twitch $twitch,
        private readonly YouTube $youtube,
        private readonly Premium $premium,
    ) {}

    public function limit(string $guildId): int
    {
        return $this->premium->isGuildPremium($guildId)
            ? config('toxbot.streams.premium_limit')
            : config('toxbot.streams.free_limit');
    }

    public function add(string $guildId, string $platform, string $input): StreamSubscription
    {
        if (StreamSubscription::query()->where('guild', $guildId)->count() >= $this->limit($guildId)) {
            throw new RuntimeException('streams.limit');
        }

        $source = match ($platform) {
            StreamSource::TWITCH => $this->twitchSource($input),
            StreamSource::YOUTUBE => $this->youtubeSource($input),
            default => throw new RuntimeException('streams.not_found'),
        };

        if (StreamSubscription::query()->where('guild', $guildId)->where('stream_source_id', $source->id)->exists()) {
            throw new RuntimeException('streams.already_added');
        }

        $this->ensureHooks($source);

        return StreamSubscription::create(['guild' => $guildId, 'stream_source_id' => $source->id]);
    }

    public function remove(StreamSubscription $subscription): void
    {
        $source = $subscription->source;
        $subscription->delete();

        if ($source && ! $source->subscriptions()->exists()) {
            $this->dropSource($source);
        }
    }

    public function ensureHooks(StreamSource $source): void
    {
        try {
            if ($source->platform === StreamSource::TWITCH) {
                $source->twitch_online_sub ??= $this->twitch->subscribe('stream.online', $source->external_id);
                $source->twitch_offline_sub ??= $this->twitch->subscribe('stream.offline', $source->external_id);
                $source->save();
            } elseif (! $source->websub_expires_at || $source->websub_expires_at->lessThan(now()->addDays(2))) {
                $this->youtube->subscribe($source->external_id);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function dropSource(StreamSource $source): void
    {
        try {
            if ($source->platform === StreamSource::TWITCH) {
                foreach (array_filter([$source->twitch_online_sub, $source->twitch_offline_sub]) as $id) {
                    $this->twitch->unsubscribe($id);
                }
            } else {
                $this->youtube->subscribe($source->external_id, 'unsubscribe');
            }
        } catch (Throwable $e) {
            report($e);
        }

        $source->delete();
    }

    private function twitchSource(string $input): StreamSource
    {
        $login = strtolower(trim(preg_replace('~^(?:https?://)?(?:www\.|m\.)?twitch\.tv/~i', '', trim($input)), '/@ '));
        if (! preg_match('/^[a-z0-9_]{3,25}$/', $login)) {
            throw new RuntimeException('streams.not_found');
        }

        try {
            $user = $this->twitch->user($login);
        } catch (RequestException|RuntimeException $e) {
            report($e);
            throw new RuntimeException('streams.platform_error');
        }

        if (! $user) {
            throw new RuntimeException('streams.not_found');
        }

        return StreamSource::query()->updateOrCreate(
            ['platform' => StreamSource::TWITCH, 'external_id' => $user['id']],
            ['login' => $user['login'], 'name' => $user['display_name'] ?: $user['login'], 'avatar_url' => $user['profile_image_url'] ?? null],
        );
    }

    private function youtubeSource(string $input): StreamSource
    {
        try {
            $channel = $this->youtube->resolve($input);
        } catch (RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'streams.')) {
                throw $e;
            }
            report($e);
            throw new RuntimeException('streams.platform_error');
        } catch (RequestException $e) {
            report($e);
            throw new RuntimeException('streams.platform_error');
        }

        if (! $channel) {
            throw new RuntimeException('streams.not_found');
        }

        return StreamSource::query()->updateOrCreate(
            ['platform' => StreamSource::YOUTUBE, 'external_id' => $channel['id']],
            ['login' => $channel['handle'], 'name' => $channel['name'], 'avatar_url' => $channel['avatar']],
        );
    }
}
