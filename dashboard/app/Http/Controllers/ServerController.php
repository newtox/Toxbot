<?php

namespace App\Http\Controllers;

use App\Services\Discord;
use App\Services\GuildAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly GuildAccess $access,
        private readonly Discord $discord,
    ) {}

    public function index(Request $request): View
    {
        $guilds = $this->access->manageableGuilds($request->user(), fresh: $request->boolean('refresh'));

        return view('servers.index', [
            'withBot' => array_values(array_filter($guilds, fn ($g) => $g['has_bot'])),
            'withoutBot' => array_values(array_filter($guilds, fn ($g) => ! $g['has_bot'])),
            'discord' => $this->discord,
        ]);
    }

    public function show(Request $request, string $guild): View
    {
        $guilds = array_values(array_filter(
            $this->access->manageableGuilds($request->user()),
            fn ($g) => $g['has_bot'],
        ));

        $found = collect($guilds)->firstWhere('id', $guild);

        abort_if($found === null, 403, __('servers.forbidden'));

        return view('servers.show', ['guild' => $found, 'guilds' => $guilds]);
    }
}
