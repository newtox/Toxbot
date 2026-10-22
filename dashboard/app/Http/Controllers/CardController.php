<?php

namespace App\Http\Controllers;

use App\Models\Background;
use App\Services\ProfileCard;
use App\Services\ProfileCardData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CardController extends Controller
{
    public function show(Request $request, string $user, ProfileCardData $data, ProfileCard $card): Response
    {
        $guild = (string) $request->query('guild');
        $guild = preg_match('/^\d{15,22}$/', $guild) === 1 ? $guild : null;

        $bg = (string) $request->query('bg');
        $preview = ctype_digit($bg) ? Background::query()->where('active', true)->find((int) $bg) : null;

        $png = $card->render($data->build($user, $guild, $preview));

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=30',
        ]);
    }
}
