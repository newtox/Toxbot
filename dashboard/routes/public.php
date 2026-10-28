<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\ShopItemImageController;
use App\Http\Controllers\TopggWebhookController;
use App\Http\Controllers\TwitchWebhookController;
use App\Http\Controllers\YouTubeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/card/{user}.png', [CardController::class, 'show'])
    ->where('user', '\d{15,22}')
    ->middleware('throttle:60,1')
    ->name('card');

Route::get('/backgrounds/{background}', [ShopItemImageController::class, 'show'])
    ->whereNumber('background')
    ->name('backgrounds.show');

Route::post('/webhooks/topgg', TopggWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.topgg');

Route::post('/webhooks/twitch', TwitchWebhookController::class)
    ->middleware('throttle:600,1')
    ->name('webhooks.twitch');

Route::get('/webhooks/youtube', [YouTubeWebhookController::class, 'verify'])
    ->middleware('throttle:600,1')
    ->name('webhooks.youtube');

Route::post('/webhooks/youtube', [YouTubeWebhookController::class, 'notify'])
    ->middleware('throttle:600,1');
