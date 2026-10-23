<?php

use App\Http\Controllers\DiscordAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ServerController;
use App\Http\Middleware\EnsureBotOwner;
use App\Livewire\GuildSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

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
    Route::get('/servers/{guild}/{section}', [ServerController::class, 'settings'])
        ->whereNumber('guild')
        ->whereIn('section', GuildSettings::SECTIONS)
        ->name('servers.settings');

    Route::view('/profile', 'profile')->name('profile');
    Route::view('/shop', 'shop')->name('shop');
    Route::view('/tags', 'tags')->name('tags');

    Route::view('/admin', 'admin')->middleware(EnsureBotOwner::class)->name('admin');
});
