<?php

return [
    'owner_id' => env('TOXBOT_OWNER_ID'),

    'premium_users' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOXBOT_PREMIUM_USERS', ''))))),

    'premium_user_skus' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOXBOT_PREMIUM_USER_SKUS', ''))))),

    'currency' => env('TOXBOT_CURRENCY', 'Credits'),

    'tags' => [
        'free_limit' => (int) env('TOXBOT_TAG_FREE_LIMIT', 10),
        'premium_limit' => (int) env('TOXBOT_TAG_PREMIUM_LIMIT', 250),
        'image_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOXBOT_TAG_IMAGE_HOSTS', 'cdn.discordapp.com,media.discordapp.net,i.imgur.com'))))),
    ],

    'topgg' => [
        'webhook_secret' => env('TOPGG_WEBHOOK_SECRET'),
        'vote_reward' => (int) env('TOPGG_VOTE_REWARD', 200),
    ],
];
