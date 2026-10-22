<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBotOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(self::check($request->user()?->id), 403);

        return $next($request);
    }

    public static function check(int|string|null $userId): bool
    {
        $owner = (string) config('toxbot.owner_id');

        return $userId !== null && $owner !== '' && (string) $userId === $owner;
    }
}
