<?php

namespace App\Http\Controllers;

use App\Models\StreamSource;
use App\Services\Streams\Announcer;
use App\Services\Streams\YouTube;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use function Illuminate\Support\defer;

class YouTubeWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $channelId = YouTube::channelIdFromTopic((string) $request->query('hub_topic'));
        $source = $channelId
            ? StreamSource::query()->where('platform', StreamSource::YOUTUBE)->where('external_id', $channelId)->first()
            : null;

        $mode = $request->query('hub_mode');

        if ($mode === 'subscribe') {
            abort_if($source === null, 404);
            $source->update(['websub_expires_at' => now()->addSeconds((int) $request->query('hub_lease_seconds', YouTube::LEASE_SECONDS))]);
        } elseif ($mode !== 'unsubscribe') {
            abort(404);
        }

        return response((string) $request->query('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
    }

    public function notify(Request $request, YouTube $youtube, Announcer $announcer): Response
    {
        $body = $request->getContent();

        if (! $youtube->validSignature($body, (string) $request->header('X-Hub-Signature'))) {
            return response()->noContent();
        }

        $entries = YouTube::parseFeed($body);
        if ($entries !== []) {
            defer(fn () => $announcer->youtubeEntries($entries));
        }

        return response()->noContent();
    }
}
