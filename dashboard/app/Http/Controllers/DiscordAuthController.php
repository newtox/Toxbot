<?php

namespace App\Http\Controllers;

use App\Models\BlacklistEntry;
use App\Models\DashboardUser;
use App\Services\Discord;
use App\Services\DiscordTokens;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DiscordAuthController extends Controller
{
    public const LOGIN_TARGETS = ['profile', 'shop', 'tags', 'servers.index'];

    public function __construct(
        private readonly Discord $discord,
        private readonly DiscordTokens $tokens,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('discord.oauth_state', $state);

        if (in_array($request->query('to'), self::LOGIN_TARGETS, true)) {
            $request->session()->put('url.intended', route($request->query('to')));
        }

        return redirect()->away($this->discord->authorizeUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expected = $request->session()->pull('discord.oauth_state');

        if ($request->has('error')) {
            return redirect()->route('home')->with('status', __('login.cancelled'));
        }

        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('home')->with('status', __('login.invalid'));
        }

        try {
            $token = $this->discord->exchangeCode((string) $request->query('code'));
            $discordUser = $this->discord->currentUser($token['access_token']);
        } catch (RequestException $e) {
            report($e);

            return redirect()->route('home')->with('status', __('login.rejected'));
        }

        if (BlacklistEntry::userBlocked($discordUser['id'])) {
            return redirect()->route('home')->with('status', __('login.blacklisted'));
        }

        $user = DashboardUser::updateOrCreate(
            ['id' => $discordUser['id']],
            [
                'username' => $discordUser['username'],
                'global_name' => $discordUser['global_name'] ?? null,
                'avatar' => $discordUser['avatar'] ?? null,
            ],
        );

        $this->tokens->store($user, $token);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        Cache::forget("discord:user:{$user->id}:guilds");

        return redirect()->intended(route('servers.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
