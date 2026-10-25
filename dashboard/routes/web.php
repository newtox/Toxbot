<?php

use App\Http\Controllers\AppealController;
use App\Http\Controllers\DiscordAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\VerifyController;
use App\Http\Middleware\EnsureBotOwner;
use App\Livewire\GuildSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/verify/{guild}', [VerifyController::class, 'show'])
    ->whereNumber('guild')
    ->name('verify.show');
Route::post('/verify/{guild}', [VerifyController::class, 'submit'])
    ->whereNumber('guild')
    ->middleware('throttle:10,1')
    ->name('verify.submit');

Route::get('/appeal/{guild}', [AppealController::class, 'show'])
    ->whereNumber('guild')
    ->name('appeals.show');
Route::post('/appeal/{guild}', [AppealController::class, 'submit'])
    ->whereNumber('guild')
    ->middleware('throttle:5,1')
    ->name('appeals.submit');

Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [DiscordAuthController::class, 'redirect'])->name('login');
    Route::get('/auth/discord/callback', [DiscordAuthController::class, 'callback'])->name('auth.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [DiscordAuthController::class, 'logout'])->name('logout');

    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/{guild}', [ServerController::class, 'show'])
        ->whereNumber('guild')
        ->name('servers.show');
    Route::get('/servers/{guild}/members', [ServerController::class, 'members'])
        ->whereNumber('guild')
        ->name('servers.members');
    Route::get('/servers/{guild}/members/{user}', [ServerController::class, 'member'])
        ->whereNumber('guild')
        ->whereNumber('user')
        ->name('servers.member');
    Route::get('/servers/{guild}/modlog', [ServerController::class, 'modlog'])
        ->whereNumber('guild')
        ->name('servers.modlog');
    Route::get('/servers/{guild}/ticket-history', [ServerController::class, 'tickets'])
        ->whereNumber('guild')
        ->name('servers.tickets');
    Route::get('/servers/{guild}/ticket-history/{ticket}', [ServerController::class, 'ticket'])
        ->whereNumber('guild')
        ->whereNumber('ticket')
        ->name('servers.ticket');
    Route::get('/servers/{guild}/appeals', [ServerController::class, 'appeals'])
        ->whereNumber('guild')
        ->name('servers.appeals');
    Route::get('/servers/{guild}/{section}', [ServerController::class, 'settings'])
        ->whereNumber('guild')
        ->whereIn('section', [...GuildSettings::SECTIONS, ...array_keys(GuildSettings::LEGACY_SECTIONS)])
        ->name('servers.settings');

    Route::view('/profile', 'profile')->name('profile');
    Route::view('/shop', 'shop')->name('shop');
    Route::view('/tags', 'tags')->name('tags');

    Route::view('/admin', 'admin')->middleware(EnsureBotOwner::class)->name('admin');
});
