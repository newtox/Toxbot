<?php

use App\Http\Controllers\BackgroundImageController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\TopggWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/card/{user}.png', [CardController::class, 'show'])
    ->where('user', '\d{15,22}')
    ->middleware('throttle:60,1')
    ->name('card');

Route::get('/backgrounds/{background}', [BackgroundImageController::class, 'show'])
    ->whereNumber('background')
    ->name('backgrounds.show');

Route::post('/webhooks/topgg', TopggWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.topgg');
