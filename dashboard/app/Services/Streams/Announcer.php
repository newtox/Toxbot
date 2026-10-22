<?php

namespace App\Services\Streams;

use App\Models\Guild;
use App\Models\StreamAnnouncement;
use App\Models\StreamSource;
use App\Models\StreamSubscription;
use App\Services\Discord;
use App\Services\Premium;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Throwable;

class Announcer
{
    private const TWITCH_COLOR = 0x9146FF;

    private const YOUTUBE_COLOR = 0xFF0000;

    private const ENDED_COLOR = 0x4E5058;

    public function __construct(
        private readonly Discord $discord,
        private readonly Premium $premium,
        private readonly Twitch $twitch,
        private readonly YouTube $youtube,
    ) {}

    public function twitchOnline(string $userId, string $streamId, ?string $startedAt): void
    {
        $source = StreamSource::query()->where('platform', StreamSource::TWITCH)->where('external_id', $userId)->first();
        if (! $source || $source->live_item === $streamId) {
            return;
        }

        $source->update(['live_item' => $streamId, 'live_since' => $startedAt ? Carbon::parse($startedAt) : now()]);

        $stream = rescue(fn () => $this->twitch->stream($userId), report: false);
        $channel = $stream ? null : rescue(fn () => $this->twitch->channel($userId), report: false);

        $details = [
            'title' => $stream['title'] ?? $channel['title'] ?? '',
            'game' => $stream['game_name'] ?? $channel['game_name'] ?? '',
            'url' => $source->url(),
            'image' => isset($stream['thumbnail_url'])
                ? str_replace(['{width}', '{height}'], ['1280', '720'], $stream['thumbnail_url']).'?t='.time()
                : null,
            'color' => self::TWITCH_COLOR,
            'footer' => 'Twitch',
            'started' => $source->live_since,
        ];

        foreach ($source->subscriptions as $subscription) {
            $premium = $this->premium->isGuildPremium($subscription->guild);
            if (! $this->allowed($subscription, $premium)) {
                continue;
            }
            if ($premium && ! $subscription->matches($details['game'], $details['title'])) {
                continue;
            }

            $this->announce($subscription, $source, $streamId, 'live', $details, $premium);
        }
    }

    public function twitchOffline(string $userId): void
    {
        $source = StreamSource::query()->where('platform', StreamSource::TWITCH)->where('external_id', $userId)->first();
        if (! $source || ! $source->live_item) {
            return;
        }

        $streamId = $source->live_item;
        $since = $source->live_since;
        $source->update(['live_item' => null, 'live_since' => null]);

        $announcements = StreamAnnouncement::query()
            ->with('subscription')
            ->where('item_id', $streamId)
            ->whereNull('ended_at')
            ->whereIn('stream_subscription_id', $source->subscriptions()->pluck('id'))
            ->get();

        $vod = null;
        foreach ($announcements as $announcement) {
            $announcement->update(['ended_at' => now()]);
            $subscription = $announcement->subscription;

            if (! $announcement->message_id || $subscription->end_action === 'keep'
                || ! $this->premium->isGuildPremium($subscription->guild)) {
                continue;
            }

            try {
                if ($subscription->end_action === 'delete') {
                    $this->discord->deleteMessage($announcement->channel_id, $announcement->message_id);

                    continue;
                }

                $vod ??= rescue(fn () => $this->twitch->latestArchive($userId), report: false) ?: [];
                $this->discord->editMessage($announcement->channel_id, $announcement->message_id, [
                    'embeds' => [$this->endedEmbed($source, $since, $vod['url'] ?? null)],
                    'allowed_mentions' => ['parse' => []],
                ]);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * @param  list<array{video: string, channel: string, title: string, published: ?string}>  $entries
     */
    public function youtubeEntries(array $entries): void
    {
        $fresh = array_values(array_filter($entries, fn ($e) => $e['published'] === null
            || CarbonImmutable::parse($e['published'])->greaterThan(now()->subDays(2))));
        if ($fresh === []) {
            return;
        }

        $details = rescue(fn () => $this->youtube->videos(array_column($fresh, 'video')), [], report: false);

        foreach ($fresh as $entry) {
            $source = StreamSource::query()->where('platform', StreamSource::YOUTUBE)->where('external_id', $entry['channel'])->first();
            if (! $source) {
                continue;
            }

            $video = $details[$entry['video']] ?? null;
            if ($video && $video['ended']) {
                continue;
            }

            $kind = match ($video['live'] ?? 'none') {
                'live' => 'live',
                'upcoming' => 'upcoming',
                default => 'video',
            };

            $this->publishYoutube($source, $entry['video'], $video['title'] ?? $entry['title'], $kind, $video['thumbnail'] ?? null);
        }
    }

    public function publishYoutube(StreamSource $source, string $videoId, string $title, string $kind, ?string $thumbnail): void
    {
        $details = [
            'title' => $title,
            'game' => '',
            'url' => 'https://www.youtube.com/watch?v='.$videoId,
            'image' => $thumbnail ?? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
            'color' => self::YOUTUBE_COLOR,
            'footer' => $kind === 'video' ? 'YouTube' : 'YouTube Live',
            'started' => now(),
        ];

        foreach ($source->subscriptions as $subscription) {
            if ($kind === 'video' && ! $subscription->notify_videos) {
                continue;
            }
            if ($kind !== 'video' && ! $subscription->notify_live) {
                continue;
            }

            $premium = $this->premium->isGuildPremium($subscription->guild);
            if (! $this->allowed($subscription, $premium)) {
                continue;
            }
            $keyword = mb_strtolower(trim((string) $subscription->filter_title));
            if ($premium && $keyword !== '' && ! str_contains(mb_strtolower($title), $keyword)) {
                continue;
            }

            if ($kind === 'upcoming') {
                StreamAnnouncement::query()->firstOrCreate(
                    ['stream_subscription_id' => $subscription->id, 'item_id' => $videoId],
                    ['kind' => 'upcoming'],
                );

                continue;
            }

            $this->announce($subscription, $source, $videoId, $kind, $details, $premium);
        }
    }

    private function allowed(StreamSubscription $subscription, bool $premium): bool
    {
        return $premium || in_array((int) $subscription->id, StreamSubscription::freeIds($subscription->guild), true);
    }

    private function announce(StreamSubscription $subscription, StreamSource $source, string $itemId, string $kind, array $details, bool $premium): void
    {
        $guild = Guild::find($subscription->guild);
        if (! $guild || ! $guild->stream) {
            return;
        }

        $announcement = StreamAnnouncement::query()->firstOrCreate(
            ['stream_subscription_id' => $subscription->id, 'item_id' => $itemId],
            ['kind' => $kind],
        );
        if ($announcement->message_id) {
            return;
        }

        $template = $kind === 'video'
            ? (($premium && $subscription->video_message) ? $subscription->video_message : ($guild->stream_video_msg ?: Guild::DEFAULT_VIDEO_MESSAGE))
            : (($premium && $subscription->message) ? $subscription->message : ($guild->stream_msg ?: Guild::DEFAULT_STREAM_MESSAGE));
        $role = ($premium && $subscription->role) ? $subscription->role : $guild->notify;

        $content = strtr($template, [
            '&streamer' => $source->name,
            '&title' => $details['title'],
            '&game' => $details['game'],
            '&link' => $details['url'],
            '&role' => $role ? "<@&{$role}>" : '',
        ]);
        $content = trim(preg_replace('/ {2,}/', ' ', $content));

        try {
            $messageId = $this->discord->sendMessage($guild->stream, [
                'content' => mb_substr($content, 0, 2000),
                'embeds' => [$this->embed($source, $details)],
                'allowed_mentions' => ['parse' => [], 'roles' => $role ? [$role] : []],
            ]);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        $announcement->update([
            'kind' => $kind,
            'channel_id' => $guild->stream,
            'message_id' => $messageId,
            'started_at' => $details['started'],
        ]);
    }

    private function embed(StreamSource $source, array $details): array
    {
        return array_filter([
            'author' => array_filter(['name' => $source->name, 'url' => $source->url(), 'icon_url' => $source->avatar_url]),
            'title' => mb_substr($details['title'] ?: $source->name, 0, 256),
            'url' => $details['url'],
            'color' => $details['color'],
            'fields' => $details['game'] !== '' ? [['name' => 'Game', 'value' => mb_substr($details['game'], 0, 1024), 'inline' => true]] : null,
            'image' => $details['image'] ? ['url' => $details['image']] : null,
            'footer' => ['text' => $details['footer']],
            'timestamp' => $details['started']?->toIso8601String(),
        ]);
    }

    private function endedEmbed(StreamSource $source, ?Carbon $since, ?string $vod): array
    {
        $minutes = $since ? (int) $since->diffInMinutes(now(), true) : null;
        $duration = $minutes !== null ? sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60) : null;

        return array_filter([
            'author' => array_filter(['name' => $source->name, 'url' => $source->url(), 'icon_url' => $source->avatar_url]),
            'title' => 'Stream ended',
            'url' => $vod ?? $source->url(),
            'color' => self::ENDED_COLOR,
            'description' => implode("\n", array_filter([
                $duration ? "Streamed for **{$duration}**" : null,
                $vod ? "[Watch the VOD]({$vod})" : null,
            ])) ?: null,
            'footer' => ['text' => 'Twitch'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
