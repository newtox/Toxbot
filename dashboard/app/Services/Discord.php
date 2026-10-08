<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Discord
{
    public const API = 'https://discord.com/api/v10';

    public const PERMISSION_ADMINISTRATOR = 0x8;

    public const PERMISSION_MANAGE_GUILD = 0x20;

    public const PERMISSION_MANAGE_ROLES = 0x10000000;

    public const INVITE_PERMISSIONS = 0x10000000 | 0x800 | 0x400 | 0x2000 | 0x4000;

    public function __construct(
        private readonly ?string $clientId,
        private readonly ?string $clientSecret,
        private readonly ?string $redirectUri,
        private readonly ?string $botToken,
    ) {}

    public function authorizeUrl(string $state): string
    {
        return 'https://discord.com/oauth2/authorize?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'identify guilds',
            'state' => $state,
            'prompt' => 'none',
        ]);
    }

    public function inviteUrl(?string $guildId = null): string
    {
        return 'https://discord.com/oauth2/authorize?'.http_build_query(array_filter([
            'client_id' => $this->clientId,
            'scope' => 'bot applications.commands',
            'permissions' => self::INVITE_PERMISSIONS,
            'guild_id' => $guildId,
            'disable_guild_select' => $guildId ? 'true' : null,
        ]));
    }

    public function exchangeCode(string $code): array
    {
        return Http::asForm()
            ->acceptJson()
            ->post(self::API.'/oauth2/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ])
            ->throw()
            ->json();
    }

    public function currentUser(string $accessToken): array
    {
        return $this->asUser($accessToken)->get('/users/@me')->throw()->json();
    }

    public function currentUserGuilds(string $accessToken): array
    {
        return $this->asUser($accessToken)->get('/users/@me/guilds')->throw()->json();
    }

    public function botGuildIds(): array
    {
        return Cache::remember('discord:bot:guild-ids', 60, function () {
            $ids = [];
            $after = null;

            do {
                $page = $this->asBot()
                    ->get('/users/@me/guilds', array_filter(['limit' => 200, 'after' => $after]))
                    ->throw()
                    ->json();

                foreach ($page as $guild) {
                    $ids[$guild['id']] = true;
                }

                $after = end($page)['id'] ?? null;
            } while (count($page) === 200);

            return $ids;
        });
    }

    public function botUser(): array
    {
        return Cache::remember('discord:bot:user', 3600, fn () => $this->asBot()->get('/users/@me')->throw()->json());
    }

    public function botAvatarUrl(): ?string
    {
        $url = Cache::remember('discord:bot:avatar-url', 600, function () {
            try {
                $bot = $this->botUser();
            } catch (\Throwable) {
                return '';
            }

            return isset($bot['avatar'])
                ? "https://cdn.discordapp.com/avatars/{$bot['id']}/{$bot['avatar']}.png?size=128"
                : '';
        });

        return $url ?: null;
    }

    public function guildChannels(string $guildId): array
    {
        return Cache::remember("discord:guild:{$guildId}:channels", 30, fn () => $this->asBot()->get("/guilds/{$guildId}/channels")->throw()->json());
    }

    public function guildRoles(string $guildId): array
    {
        return Cache::remember("discord:guild:{$guildId}:roles", 30, fn () => $this->asBot()->get("/guilds/{$guildId}/roles")->throw()->json());
    }

    public function botMember(string $guildId): array
    {
        $botId = $this->botUser()['id'];

        return Cache::remember("discord:guild:{$guildId}:bot-member", 30, fn () => $this->asBot()->get("/guilds/{$guildId}/members/{$botId}")->throw()->json());
    }

    public function forgetGuildCache(string $guildId): void
    {
        foreach (['channels', 'roles', 'bot-member'] as $key) {
            Cache::forget("discord:guild:{$guildId}:{$key}");
        }
    }

    private function asUser(string $accessToken): PendingRequest
    {
        return $this->client()->withToken($accessToken);
    }

    private function asBot(): PendingRequest
    {
        if (! $this->botToken) {
            throw new RuntimeException('DISCORD_BOT_TOKEN is not set.');
        }

        return $this->client()->withToken($this->botToken, 'Bot');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::API)
            ->acceptJson()
            ->timeout(10)
            ->withUserAgent('DiscordBot (https://github.com/newtox/Toxbot, 1.0) ToxbotDashboard')

            ->retry(2, function (int $attempt, \Throwable $e) {
                if ($e instanceof RequestException && $e->response->status() === 429) {
                    return (int) ceil(((float) $e->response->json('retry_after', 1)) * 1000);
                }

                return 200;
            }, fn (\Throwable $e) => $e instanceof RequestException && $e->response->status() === 429, throw: false);
    }
}
