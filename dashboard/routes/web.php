<?php

use App\Http\Controllers\DiscordAuthController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('servers.index')
        : view('home');
})->name('home');

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

    Route::view('/profile', 'profile')->name('profile');
});
