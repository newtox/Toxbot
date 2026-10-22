<?php

namespace App\Services;

use App\Models\DashboardUser;
use Illuminate\Http\Client\RequestException;

class DiscordTokens
{
    public function __construct(private readonly Discord $discord) {}

    public function store(DashboardUser $user, array $token): void
    {
        $user->forceFill([
            'discord_access_token' => $token['access_token'],
            'discord_refresh_token' => $token['refresh_token'] ?? $user->discord_refresh_token,
            'discord_token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 604800)),
        ])->save();
    }

    public function accessToken(DashboardUser $user): string
    {
        if (! $user->discord_access_token) {
            throw new MissingDiscordToken;
        }

        if ($user->discord_token_expires_at && $user->discord_token_expires_at->greaterThan(now()->addHour())) {
            return $user->discord_access_token;
        }

        if (! $user->discord_refresh_token) {
            $this->forget($user);
            throw new MissingDiscordToken;
        }

        try {
            $this->store($user, $this->discord->refreshToken($user->discord_refresh_token));
        } catch (RequestException $e) {
            if ($e->response->clientError()) {
                $this->forget($user);
                throw new MissingDiscordToken;
            }
            throw $e;
        }

        return $user->discord_access_token;
    }

    public function forget(DashboardUser $user): void
    {
        $user->forceFill([
            'discord_access_token' => null,
            'discord_refresh_token' => null,
            'discord_token_expires_at' => null,
        ])->save();
    }
}
