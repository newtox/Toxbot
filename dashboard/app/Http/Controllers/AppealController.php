<?php

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\UnbanAppeal;
use App\Services\Discord;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppealController extends Controller
{
    private const RETRY_DAYS = 30;

    public function __construct(private readonly Discord $discord) {}

    public function show(Request $request, string $guild): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        return view('appeal', $this->state($request, $guild));
    }

    public function submit(Request $request, string $guild): View|RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        $state = $this->state($request, $guild);
        if ($state['status'] !== 'form') {
            return view('appeal', $state);
        }

        $data = $request->validate(['reason' => ['required', 'string', 'min:20', 'max:2000']], [
            'reason.*' => __('tickets.appeal_reason_length'),
        ]);

        $user = $request->user();
        $appeal = UnbanAppeal::create([
            'guild' => $guild,
            'user_id' => (string) $user->id,
            'user_name' => $user->username,
            'reason' => trim($data['reason']),
        ]);

        $this->notifyTeam($state['settings'], $appeal);

        return view('appeal', [...$state, 'status' => 'sent']);
    }

    private function state(Request $request, string $guild): array
    {
        $settings = Guild::query()->find($guild);
        $userId = (string) $request->user()->id;
        $state = ['guildId' => $guild, 'guildName' => null, 'guildIcon' => null, 'settings' => $settings, 'appeal' => null];

        if (! $settings || ! $settings->unban_enabled) {
            return [...$state, 'status' => 'disabled'];
        }

        try {
            $info = $this->discord->guildInfo($guild);
            $ban = $this->discord->guildBan($guild, $userId);
        } catch (RequestException) {
            return [...$state, 'status' => 'disabled'];
        }

        $state['guildName'] = $info['name'] ?? null;
        $state['guildIcon'] = isset($info['icon'])
            ? "https://cdn.discordapp.com/icons/{$guild}/{$info['icon']}.png?size=128"
            : null;

        if ($ban === null) {
            return [...$state, 'status' => 'not_banned'];
        }

        $latest = UnbanAppeal::query()->forGuild($guild)->where('user_id', $userId)->latest('id')->first();
        if ($latest?->status === 'pending') {
            return [...$state, 'status' => 'pending', 'appeal' => $latest];
        }
        if ($latest?->status === 'rejected' && $latest->handled_at?->gt(now()->subDays(self::RETRY_DAYS))) {
            return [...$state, 'status' => 'rejected', 'appeal' => $latest, 'retryAt' => $latest->handled_at->addDays(self::RETRY_DAYS)];
        }

        return [...$state, 'status' => 'form', 'banReason' => $ban['reason'] ?? null];
    }

    private function notifyTeam(Guild $settings, UnbanAppeal $appeal): void
    {
        $channel = $settings->ticket_log_channel ?: $settings->modlog_channel;
        if (! $channel) {
            return;
        }

        rescue(fn () => $this->discord->sendMessage($channel, [
            'embeds' => [[
                'color' => 0xFEE75C,
                'title' => __('tickets.appeal_new_title'),
                'description' => Str::limit($appeal->reason, 1500),
                'fields' => [['name' => __('tickets.appeal_user'), 'value' => "<@{$appeal->user_id}> `{$appeal->user_id}`", 'inline' => false]],
                'url' => route('servers.appeals', $appeal->guild),
                'timestamp' => now()->toIso8601String(),
            ]],
            'allowed_mentions' => ['parse' => []],
        ]), report: false);
    }
}
