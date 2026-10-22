<?php

namespace Tests\Feature;

use App\Models\DashboardUser;
use App\Services\DiscordTokens;
use App\Services\MissingDiscordToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_remembers_the_user_and_stores_the_discord_token(): void
    {
        Http::fake([
            'discord.com/api/v10/oauth2/token' => Http::response(['access_token' => 'access-1', 'refresh_token' => 'refresh-1', 'expires_in' => 604800]),
            'discord.com/api/v10/users/@me' => Http::response(['id' => '123456789012345678', 'username' => 'newtox']),
        ]);

        $response = $this->withSession(['discord.oauth_state' => 'state'])
            ->get('/auth/discord/callback?code=abc&state=state');

        $response->assertRedirect(route('servers.index'));
        $response->assertCookie(Auth::guard()->getRecallerName());

        $user = DashboardUser::find('123456789012345678');
        $this->assertSame('access-1', $user->discord_access_token);
        $this->assertSame('refresh-1', $user->discord_refresh_token);
        $this->assertNotSame('access-1', $user->getRawOriginal('discord_access_token'));
        $this->assertTrue($user->discord_token_expires_at->greaterThan(now()->addDays(6)));
    }

    public function test_expiring_token_is_refreshed(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame('refresh_token', $request['grant_type']);
            $this->assertSame('refresh-1', $request['refresh_token']);

            return Http::response(['access_token' => 'access-2', 'refresh_token' => 'refresh-2', 'expires_in' => 604800]);
        });

        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox']);
        $user->forceFill(['discord_access_token' => 'access-1', 'discord_refresh_token' => 'refresh-1', 'discord_token_expires_at' => now()->addMinutes(5)])->save();

        $this->assertSame('access-2', app(DiscordTokens::class)->accessToken($user));
        $this->assertSame('refresh-2', $user->fresh()->discord_refresh_token);
    }

    public function test_rejected_refresh_token_logs_the_user_out(): void
    {
        Http::fake(['discord.com/api/v10/oauth2/token' => Http::response(['error' => 'invalid_grant'], 400)]);

        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox']);
        $user->forceFill(['discord_access_token' => 'access-1', 'discord_refresh_token' => 'refresh-1', 'discord_token_expires_at' => now()->subDay()])->save();

        $this->assertThrows(fn () => app(DiscordTokens::class)->accessToken($user), MissingDiscordToken::class);

        $this->assertNull($user->fresh()->discord_refresh_token);
    }
}
