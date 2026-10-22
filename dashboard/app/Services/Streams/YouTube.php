<?php

namespace App\Services\Streams;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class YouTube
{
    public const HUB = 'https://pubsubhubbub.appspot.com/subscribe';

    public const LEASE_SECONDS = 864000;

    public function hasApiKey(): bool
    {
        return (bool) config('toxbot.streams.youtube_api_key');
    }

    public static function topic(string $channelId): string
    {
        return 'https://www.youtube.com/xml/feeds/videos.xml?channel_id='.$channelId;
    }

    public static function channelIdFromTopic(string $topic): ?string
    {
        parse_str((string) parse_url($topic, PHP_URL_QUERY), $query);
        $id = $query['channel_id'] ?? null;

        return is_string($id) && preg_match('/^UC[\w-]{22}$/', $id) ? $id : null;
    }

    public function resolve(string $input): ?array
    {
        $input = trim($input);

        if (preg_match('~(UC[\w-]{22})~', $input, $m)) {
            return $this->byId($m[1]);
        }

        if (preg_match('~(?:youtube\.com/)?@([\w.\-]{3,100})~', $input, $m) || preg_match('~^([\w.\-]{3,100})$~', $input, $m)) {
            if (! $this->hasApiKey()) {
                throw new RuntimeException('streams.youtube_need_id');
            }

            return $this->byHandle($m[1]);
        }

        return null;
    }

    public function subscribe(string $channelId, string $mode = 'subscribe'): void
    {
        Http::asForm()->timeout(10)->post(self::HUB, [
            'hub.callback' => route('webhooks.youtube'),
            'hub.topic' => self::topic($channelId),
            'hub.mode' => $mode,
            'hub.verify' => 'async',
            'hub.secret' => $this->secret(),
            'hub.lease_seconds' => self::LEASE_SECONDS,
        ])->throw();
    }

    public function validSignature(string $body, string $header): bool
    {
        [$algo, $hash] = array_pad(explode('=', $header, 2), 2, '');
        if (! in_array($algo, ['sha1', 'sha256'], true)) {
            return false;
        }

        return hash_equals(hash_hmac($algo, $body, $this->secret()), $hash);
    }

    public static function parseFeed(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        libxml_use_internal_errors($previous);

        if (! $feed) {
            return [];
        }

        $entries = [];
        foreach ($feed->entry as $entry) {
            $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
            $video = (string) $yt->videoId;
            $channel = (string) $yt->channelId;
            if ($video === '' || $channel === '') {
                continue;
            }
            $entries[] = [
                'video' => $video,
                'channel' => $channel,
                'title' => (string) $entry->title,
                'published' => (string) $entry->published ?: null,
            ];
        }

        return $entries;
    }

    public function videos(array $ids): array
    {
        if (! $this->hasApiKey() || $ids === []) {
            return [];
        }

        $items = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet,liveStreamingDetails',
            'id' => implode(',', array_slice($ids, 0, 50)),
            'key' => config('toxbot.streams.youtube_api_key'),
        ])->throw()->json('items', []);

        $videos = [];
        foreach ($items as $item) {
            $videos[$item['id']] = [
                'live' => $item['snippet']['liveBroadcastContent'] ?? 'none',
                'title' => $item['snippet']['title'] ?? '',
                'thumbnail' => $item['snippet']['thumbnails']['high']['url'] ?? null,
                'ended' => isset($item['liveStreamingDetails']['actualEndTime']),
                'started' => $item['liveStreamingDetails']['actualStartTime'] ?? null,
            ];
        }

        return $videos;
    }

    private function byId(string $id): ?array
    {
        if ($this->hasApiKey()) {
            return $this->channel(['id' => $id]);
        }

        $response = Http::timeout(10)->get('https://www.youtube.com/feeds/videos.xml', ['channel_id' => $id]);
        if (! $response->successful()) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($response->body(), SimpleXMLElement::class, LIBXML_NONET);
        libxml_use_internal_errors($previous);
        if (! $feed) {
            return null;
        }

        $name = (string) ($feed->author->name ?? '') ?: (string) $feed->title;

        return ['id' => $id, 'name' => $name ?: $id, 'handle' => null, 'avatar' => null];
    }

    private function byHandle(string $handle): ?array
    {
        return $this->channel(['forHandle' => '@'.ltrim($handle, '@')]);
    }

    private function channel(array $query): ?array
    {
        $item = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/channels', $query + [
            'part' => 'snippet',
            'key' => config('toxbot.streams.youtube_api_key'),
        ])->throw()->json('items.0');

        if (! $item) {
            return null;
        }

        return [
            'id' => $item['id'],
            'name' => $item['snippet']['title'] ?? $item['id'],
            'handle' => isset($item['snippet']['customUrl']) ? ltrim($item['snippet']['customUrl'], '@') : null,
            'avatar' => $item['snippet']['thumbnails']['default']['url'] ?? null,
        ];
    }

    private function secret(): string
    {
        $secret = (string) config('toxbot.streams.webhook_secret');
        if (strlen($secret) < 10) {
            throw new RuntimeException('STREAM_WEBHOOK_SECRET must be at least 10 characters long.');
        }

        return $secret;
    }
}
