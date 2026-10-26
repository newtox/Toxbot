<?php

namespace App\Http\Middleware;

use App\Services\Discord;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RefreshDiscordProfile
{
    public const INTERVAL = 3600;

    public function __construct(private readonly Discord $discord) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && Cache::add("dashboard:user:{$user->id}:profile-checked", true, self::INTERVAL)) {
            $profile = rescue(fn () => $this->discord->user((string) $user->id), null, report: false);

            if (is_array($profile) && isset($profile['username'])) {
                $user->fill([
                    'username' => $profile['username'],
                    'global_name' => $profile['global_name'] ?? null,
                    'avatar' => $profile['avatar'] ?? null,
                ]);

                if ($user->isDirty()) {
                    $user->save();
                }
            }
        }

        return $next($request);
    }
}
