<?php

namespace App\Http\Controllers;

use App\Models\StreamSource;
use App\Models\WebhookDelivery;
use App\Services\Streams\Announcer;
use App\Services\Streams\Twitch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

use function Illuminate\Support\defer;

class TwitchWebhookController extends Controller
{
    public function __invoke(Request $request, Twitch $twitch, Announcer $announcer): Response
    {
        $id = (string) $request->header('Twitch-Eventsub-Message-Id');
        $timestamp = (string) $request->header('Twitch-Eventsub-Message-Timestamp');
        $signature = (string) $request->header('Twitch-Eventsub-Message-Signature');

        abort_if($id === '' || $timestamp === '' || $signature === '', 400);
        abort_unless($twitch->validSignature($id, $timestamp, $request->getContent(), $signature), 403);
        abort_if(Carbon::parse($timestamp)->lessThan(now()->subMinutes(10)), 403);

        $type = $request->header('Twitch-Eventsub-Message-Type');

        if ($type === 'webhook_callback_verification') {
            return response((string) $request->input('challenge'), 200, ['Content-Type' => 'text/plain']);
        }

        if (! WebhookDelivery::firstTime('twitch:'.$id)) {
            return response()->noContent();
        }

        if ($type === 'revocation') {
            $subscriptionId = (string) $request->input('subscription.id');
            StreamSource::query()->where('twitch_online_sub', $subscriptionId)->update(['twitch_online_sub' => null]);
            StreamSource::query()->where('twitch_offline_sub', $subscriptionId)->update(['twitch_offline_sub' => null]);

            return response()->noContent();
        }

        $event = $request->input('event', []);
        $userId = (string) ($event['broadcaster_user_id'] ?? '');

        match ($request->input('subscription.type')) {
            'stream.online' => defer(fn () => $announcer->twitchOnline($userId, (string) ($event['id'] ?? ''), $event['started_at'] ?? null)),
            'stream.offline' => defer(fn () => $announcer->twitchOffline($userId)),
            default => null,
        };

        return response()->noContent();
    }
}
