<?php

namespace App\Http\Controllers;

use App\Models\BotUser;
use App\Models\ShopItem;
use App\Services\ProfileCard;
use App\Services\ProfileCardData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CardController extends Controller
{
    public const CACHE_DIR = 'cards';

    private const PREVIEW_PARAMS = [
        ShopItem::BACKGROUND => 'bg',
        ShopItem::FRAME => 'frame',
        ShopItem::BAR => 'bar',
    ];

    public function show(Request $request, string $user, ProfileCardData $data, ProfileCard $card): Response
    {
        $guild = (string) $request->query('guild');
        $guild = preg_match('/^\d{15,22}$/', $guild) === 1 ? $guild : null;

        $previews = [];
        foreach (self::PREVIEW_PARAMS as $type => $param) {
            $value = (string) $request->query($param);
            $item = ctype_digit($value) ? ShopItem::query()->ofType($type)->find((int) $value) : null;
            if ($item && ($item->active || BotUser::forDiscordId($user)->items()->whereKey($item->id)->exists())) {
                $previews[$type] = $item;
            }
        }

        $input = $data->build($user, $guild, $previews);
        $animated = collect([$input['background'], $input['frame'], $input['bar'], $input['avatar_animated'] ?? null])->contains(fn (?array $item) => $item['animated'] ?? false);

        if (! $animated) {
            [$body, $type] = $card->render($input);

            return $this->image($body, $type);
        }

        $key = self::CACHE_DIR.'/'.md5(serialize($input)).'.gif';
        $disk = Storage::disk('local');
        if (! $disk->exists($key)) {
            [$body, $type] = $card->render($input);
            if ($type !== 'image/gif') {
                return $this->image($body, $type);
            }
            $disk->put($key, $body);
        }

        return $this->image($disk->get($key), 'image/gif');
    }

    private function image(string $body, string $type): Response
    {
        return response($body, 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=30',
        ]);
    }
}
