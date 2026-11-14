<?php

return [

    'name' => env('APP_NAME', 'Laravel'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'UTC',

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'locales' => [
        'da' => ['name' => 'Dansk', 'bot' => 'da_dk', 'discord' => 'da'],
        'de' => ['name' => 'Deutsch', 'bot' => 'de_de', 'discord' => 'de'],
        'en' => ['name' => 'English', 'bot' => 'en_us', 'discord' => 'en-US'],
        'es' => ['name' => 'Español', 'bot' => 'es_es', 'discord' => 'es-ES'],
        'fr' => ['name' => 'Français', 'bot' => 'fr_fr', 'discord' => 'fr'],
        'it' => ['name' => 'Italiano', 'bot' => 'it_it', 'discord' => 'it'],
        'pl' => ['name' => 'Polski', 'bot' => 'pl_pl', 'discord' => 'pl'],
        'pt' => ['name' => 'Português (Brasil)', 'bot' => 'pt_br', 'discord' => 'pt-BR'],
        'vi' => ['name' => 'Tiếng Việt', 'bot' => 'vi_vn', 'discord' => 'vi'],
        'tr' => ['name' => 'Türkçe', 'bot' => 'tr_tr', 'discord' => 'tr'],
        'ru' => ['name' => 'Русский', 'bot' => 'ru_ru', 'discord' => 'ru'],
        'uk' => ['name' => 'Українська', 'bot' => 'uk_ua', 'discord' => 'uk'],
        'hi' => ['name' => 'हिंदी', 'bot' => 'hi_hi', 'discord' => 'hi'],
        'th' => ['name' => 'ไทย', 'bot' => 'th_th', 'discord' => 'th'],
        'zh' => ['name' => '中文', 'bot' => 'zh_cn', 'discord' => 'zh-CN'],
        'zh_TW' => ['name' => '繁體中文', 'bot' => 'zh_tw', 'discord' => 'zh-TW'],
        'ja' => ['name' => '日本語', 'bot' => 'ja_jp', 'discord' => 'ja'],
        'ko' => ['name' => '한국어', 'bot' => 'ko_ko', 'discord' => 'ko'],
    ],

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
