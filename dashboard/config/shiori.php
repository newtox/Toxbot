<?php

return [
    'owner_id' => env('SHIORI_OWNER_ID'),

    'premium_guilds' => array_values(array_filter(array_map('trim', explode(',', (string) env('SHIORI_PREMIUM_GUILDS', ''))))),

    'premium_guild_skus' => array_values(array_filter(array_map('trim', explode(',', (string) env('SHIORI_PREMIUM_GUILD_SKUS', ''))))),

    'repository' => env('SHIORI_REPOSITORY', 'https://github.com/newtox/shiori'),

    'contact_email' => env('SHIORI_CONTACT_EMAIL') ?: 'justin.lys26@gmail.com',

    'support_invite' => env('SHIORI_SUPPORT_INVITE'),

    'commit' => env('GIT_COMMIT'),

    'build_date' => env('BUILD_DATE'),

    'currency' => env('SHIORI_CURRENCY', 'Credits'),

    'daily' => [
        'reward' => (int) env('SHIORI_DAILY_REWARD', 100),
        'streak_bonus' => (int) env('SHIORI_DAILY_STREAK_BONUS', 10),
    ],

    'tags' => [
        'free_limit' => (int) env('SHIORI_TAG_FREE_LIMIT', 10),
        'slot_size' => (int) env('SHIORI_TAG_SLOT_SIZE', 5),
        'slot_price' => (int) env('SHIORI_TAG_SLOT_PRICE', 1000),
        'max_limit' => (int) env('SHIORI_TAG_MAX_LIMIT', 250),
        'image_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('SHIORI_TAG_IMAGE_HOSTS', 'cdn.discordapp.com,media.discordapp.net,i.imgur.com'))))),
    ],

    'streams' => [
        'free_limit' => (int) env('SHIORI_STREAMS_FREE_LIMIT', 5),
        'premium_limit' => (int) env('SHIORI_STREAMS_PREMIUM_LIMIT', 50),
        'webhook_secret' => env('STREAM_WEBHOOK_SECRET'),
        'twitch_client_id' => env('TWITCH_CLIENT_ID'),
        'twitch_client_secret' => env('TWITCH_CLIENT_SECRET'),
        'youtube_api_key' => env('YOUTUBE_API_KEY'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'topgg' => [
        'webhook_secret' => env('TOPGG_WEBHOOK_SECRET'),
        'vote_reward' => (int) env('TOPGG_VOTE_REWARD', 200),
    ],
];
