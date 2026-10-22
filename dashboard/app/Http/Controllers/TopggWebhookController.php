<?php

namespace App\Http\Controllers;

use App\Services\Economy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TopggWebhookController extends Controller
{
    public function __invoke(Request $request, Economy $economy): Response
    {
        $secret = (string) config('toxbot.topgg.webhook_secret');
        abort_if($secret === '', 404);

        $reward = (int) config('toxbot.topgg.vote_reward');

        if ($request->hasHeader('x-topgg-signature')) {
            abort_unless($this->validSignature($request, $secret), 401);

            if ($request->input('type') !== 'vote.create') {
                return response()->noContent();
            }

            $userId = (string) $request->input('data.user.platform_id');
            $weight = max(1, (int) $request->input('data.weight', 1));
            $reference = 'topgg:'.$request->input('data.id');
        } else {
            abort_unless(hash_equals($secret, (string) $request->header('Authorization')), 401);

            if ($request->input('type') !== 'upvote') {
                return response()->noContent();
            }

            $userId = (string) $request->input('user');
            $weight = $request->boolean('isWeekend') ? 2 : 1;
            $reference = 'topgg:'.$userId.':'.intdiv(now()->timestamp, 12 * 3600);
        }

        abort_unless(preg_match('/^\d{15,22}$/', $userId) === 1, 422);

        $economy->addCredits($userId, $reward * $weight, 'vote', $reference);

        return response()->noContent();
    }

    private function validSignature(Request $request, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', (string) $request->header('x-topgg-signature')) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key] = $value;
        }

        if (! isset($parts['t'], $parts['v1']) || abs(time() - (int) $parts['t']) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts['t'].'.'.$request->getContent(), $secret);

        return hash_equals($expected, $parts['v1']);
    }
}
