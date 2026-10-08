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
        'da' => ['name' => 'Dansk', 'bot' => 'da_dk'],
        'de' => ['name' => 'Deutsch', 'bot' => 'de_de'],
        'en' => ['name' => 'English', 'bot' => 'en_us'],
        'es' => ['name' => 'Español', 'bot' => 'es_es'],
        'fr' => ['name' => 'Français', 'bot' => 'fr_fr'],
        'ru' => ['name' => 'Русский', 'bot' => 'ru_ru'],
        'hi' => ['name' => 'हिंदी', 'bot' => 'hi_hi'],
        'zh' => ['name' => '中文', 'bot' => 'zh_cn'],
        'ja' => ['name' => '日本語', 'bot' => 'ja_jp'],
        'ko' => ['name' => '한국어', 'bot' => 'ko_ko'],
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
