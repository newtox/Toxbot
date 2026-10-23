<?php

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\Verification;
use App\Services\Discord;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class VerifyController extends Controller
{
    private const TURNSTILE_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(private readonly Discord $discord) {}

    public function show(Request $request, string $guild): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        return view('verify', $this->state($request, $guild));
    }

    public function submit(Request $request, string $guild): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        $state = $this->state($request, $guild);
        if ($state['status'] !== 'pending') {
            return view('verify', $state);
        }

        if (! $this->passedChallenge($request)) {
            return view('verify', [...$state, 'error' => __('protection.verify_captcha_failed')]);
        }

        $settings = $state['settings'];
        $userId = (string) $request->user()->id;

        try {
            $this->discord->removeMemberRole($guild, $userId, $settings->verify_role, 'Toxbot verification');
            if ($settings->autorole && $settings->autorole !== $settings->verify_role) {
                $this->discord->addMemberRole($guild, $userId, $settings->autorole, 'Toxbot verification');
            }
        } catch (RequestException $e) {
            report($e);

            return view('verify', [...$state, 'error' => __('protection.verify_role_failed')]);
        }

        Verification::query()->updateOrCreate(
            ['guild' => $guild, 'user_id' => $userId],
            ['verified_at' => now()],
        );

        return view('verify', [...$state, 'status' => 'done']);
    }

    private function state(Request $request, string $guild): array
    {
        $settings = Guild::query()->find($guild);
        $state = [
            'guildId' => $guild,
            'guildName' => null,
            'guildIcon' => null,
            'settings' => $settings,
            'siteKey' => config('toxbot.turnstile.site_key'),
            'error' => null,
        ];

        if (! $settings || ! $settings->verify_enabled || ! $settings->verify_role) {
            return [...$state, 'status' => 'disabled'];
        }

        try {
            $info = $this->discord->guildInfo($guild);
            $member = $this->discord->guildMember($guild, (string) $request->user()->id);
        } catch (RequestException) {
            return [...$state, 'status' => 'disabled'];
        }

        $state['guildName'] = $info['name'] ?? null;
        $state['guildIcon'] = isset($info['icon'])
            ? "https://cdn.discordapp.com/icons/{$guild}/{$info['icon']}.png?size=128"
            : null;

        if ($member === null) {
            return [...$state, 'status' => 'not_member'];
        }

        $pending = in_array($settings->verify_role, $member['roles'] ?? [], true);

        return [...$state, 'status' => $pending ? 'pending' : 'verified'];
    }

    private function passedChallenge(Request $request): bool
    {
        $secret = config('toxbot.turnstile.secret_key');
        if (! $secret || ! config('toxbot.turnstile.site_key')) {
            return true;
        }

        $token = (string) $request->input('cf-turnstile-response');
        if ($token === '') {
            return false;
        }

        try {
            return (bool) Http::asForm()->timeout(10)->post(self::TURNSTILE_URL, [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ])->json('success', false);
        } catch (ConnectionException $e) {
            report($e);

            return false;
        }
    }
}
