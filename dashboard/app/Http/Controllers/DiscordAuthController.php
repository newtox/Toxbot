<?php

namespace App\Http\Controllers;

use App\Models\DashboardUser;
use App\Services\Discord;
use App\Services\GuildAccess;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DiscordAuthController extends Controller
{
    public function __construct(private readonly Discord $discord) {}

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('discord.oauth_state', $state);

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

        $user = DashboardUser::updateOrCreate(
            ['id' => $discordUser['id']],
            [
                'username' => $discordUser['username'],
                'global_name' => $discordUser['global_name'] ?? null,
                'avatar' => $discordUser['avatar'] ?? null,
            ],
        );

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(GuildAccess::SESSION_TOKEN_KEY, $token['access_token']);

        Cache::forget("discord:user:{$user->id}:guilds");

        return redirect()->intended(route('servers.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
