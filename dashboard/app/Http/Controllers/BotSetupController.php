<?php

namespace App\Http\Controllers;

use App\Services\AutoSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class BotSetupController extends Controller
{
    public const MAX_AGE = 300;

    public const LOCALES = [
        'da_dk' => 'da', 'de_de' => 'de', 'en_us' => 'en', 'es_es' => 'es', 'fr_fr' => 'fr',
        'hi_hi' => 'hi', 'ja_jp' => 'ja', 'ko_ko' => 'ko', 'ru_ru' => 'ru', 'zh_cn' => 'zh',
    ];

    public function __invoke(Request $request, AutoSetup $setup): JsonResponse
    {
        abort_unless(self::validSignature($request), 401);

        $data = $request->validate([
            'time' => ['required', 'integer'],
            'guild' => ['required', 'string', 'regex:/^\d{15,22}$/'],
            'guild_name' => ['required', 'string', 'max:100'],
            'areas' => ['required', 'array', 'min:1'],
            'areas.*' => [Rule::in(AutoSetup::AREAS)],
            'team_roles' => ['required', 'array', 'min:1', 'max:10'],
            'team_roles.*' => ['string', 'regex:/^\d{15,22}$/'],
            'user_name' => ['required', 'string', 'max:100'],
            'locale' => ['nullable', 'string'],
        ]);
        abort_if(abs(time() - $data['time']) > self::MAX_AGE, 401);

        App::setLocale(self::LOCALES[$data['locale'] ?? ''] ?? 'en');

        return response()->json([
            'steps' => $setup->run($data['guild'], $data['guild_name'], $data['areas'], $data['team_roles'], $data['user_name']),
        ]);
    }

    public static function validSignature(Request $request): bool
    {
        $secret = (string) config('services.discord.bot_token');
        $signature = (string) $request->header('X-Toxbot-Signature');

        return $secret !== '' && $signature !== ''
            && hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature);
    }
}
