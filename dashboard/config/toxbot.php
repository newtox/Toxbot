<?php

return [
    'owner_id' => env('TOXBOT_OWNER_ID'),

    'premium_users' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOXBOT_PREMIUM_USERS', ''))))),

    'premium_user_skus' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOXBOT_PREMIUM_USER_SKUS', ''))))),

    'premium_guilds' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOXBOT_PREMIUM_GUILDS', ''))))),

    'premium_guild_skus' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOXBOT_PREMIUM_GUILD_SKUS', ''))))),

    'repository' => env('TOXBOT_REPOSITORY', 'https://github.com/newtox/Toxbot'),

    'contact_email' => env('TOXBOT_CONTACT_EMAIL') ?: 'justin.lys26@gmail.com',

    'support_invite' => env('TOXBOT_SUPPORT_INVITE'),

    'commit' => env('GIT_COMMIT'),

    'build_date' => env('BUILD_DATE'),

    'currency' => env('TOXBOT_CURRENCY', 'Credits'),

    'tags' => [
        'free_limit' => (int) env('TOXBOT_TAG_FREE_LIMIT', 10),
        'premium_limit' => (int) env('TOXBOT_TAG_PREMIUM_LIMIT', 250),
        'image_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOXBOT_TAG_IMAGE_HOSTS', 'cdn.discordapp.com,media.discordapp.net,i.imgur.com'))))),
    ],

    'streams' => [
        'free_limit' => (int) env('TOXBOT_STREAMS_FREE_LIMIT', 5),
        'premium_limit' => (int) env('TOXBOT_STREAMS_PREMIUM_LIMIT', 50),
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
