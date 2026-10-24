<?php

namespace App\Services\Streams;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Twitch
{
    public const API = 'https://api.twitch.tv/helix';

    public function configured(): bool
    {
        return config('toxbot.streams.twitch_client_id') && config('toxbot.streams.twitch_client_secret');
    }

    public function user(string $login): ?array
    {
        return $this->api()->get('/users', ['login' => $login])->throw()->json('data.0');
    }

    public function stream(string $userId): ?array
    {
        return $this->api()->get('/streams', ['user_id' => $userId])->throw()->json('data.0');
    }

    public function streams(array $userIds): array
    {
        $streams = [];
        foreach (array_chunk(array_values(array_unique($userIds)), 100) as $chunk) {
            $query = implode('&', array_map(fn ($id) => 'user_id='.rawurlencode($id), $chunk));
            foreach ($this->api()->get('/streams?'.$query.'&first=100')->throw()->json('data', []) as $stream) {
                if (isset($stream['user_id'], $stream['id'])) {
                    $streams[$stream['user_id']] = $stream;
                }
            }
        }

        return $streams;
    }

    public function channel(string $userId): ?array
    {
        return $this->api()->get('/channels', ['broadcaster_id' => $userId])->throw()->json('data.0');
    }

    public function latestArchive(string $userId): ?array
    {
        return $this->api()->get('/videos', ['user_id' => $userId, 'type' => 'archive', 'first' => 1])->throw()->json('data.0');
    }

    public function subscribe(string $type, string $userId): string
    {
        $response = $this->api()->post('/eventsub/subscriptions', [
            'type' => $type,
            'version' => '1',
            'condition' => ['broadcaster_user_id' => $userId],
            'transport' => [
                'method' => 'webhook',
                'callback' => route('webhooks.twitch'),
                'secret' => $this->secret(),
            ],
        ]);

        if ($response->status() === 409) {
            $existing = collect($this->api()->get('/eventsub/subscriptions', ['user_id' => $userId])->throw()->json('data'))
                ->firstWhere('type', $type);

            if ($existing) {
                return $existing['id'];
            }
        }

        return $response->throw()->json('data.0.id');
    }

    public function unsubscribe(string $subscriptionId): void
    {
        $response = $this->api()->delete('/eventsub/subscriptions?'.http_build_query(['id' => $subscriptionId]));
        if ($response->status() !== 404) {
            $response->throw();
        }
    }

    public function validSignature(string $messageId, string $timestamp, string $body, string $signature): bool
    {
        $expected = 'sha256='.hash_hmac('sha256', $messageId.$timestamp.$body, $this->secret());

        return hash_equals($expected, $signature);
    }

    public function secret(): string
    {
        $secret = (string) config('toxbot.streams.webhook_secret');
        if (strlen($secret) < 10) {
            throw new RuntimeException('STREAM_WEBHOOK_SECRET must be at least 10 characters long.');
        }

        return substr($secret, 0, 100);
    }

    private function api(): PendingRequest
    {
        if (! $this->configured()) {
            throw new RuntimeException('TWITCH_CLIENT_ID and TWITCH_CLIENT_SECRET are not set.');
        }

        return Http::baseUrl(self::API)
            ->acceptJson()
            ->timeout(10)
            ->withHeaders(['Client-Id' => config('toxbot.streams.twitch_client_id')])
            ->withToken($this->token());
    }

    private function token(): string
    {
        $cached = Cache::get('twitch:app-token');
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://id.twitch.tv/oauth2/token', [
                'client_id' => config('toxbot.streams.twitch_client_id'),
                'client_secret' => config('toxbot.streams.twitch_client_secret'),
                'grant_type' => 'client_credentials',
            ])->throw()->json();
        } catch (RequestException $e) {
            throw new RuntimeException('Twitch rejected the client credentials.', previous: $e);
        }

        Cache::put('twitch:app-token', $response['access_token'], max(60, (int) $response['expires_in'] - 300));

        return $response['access_token'];
    }
}
