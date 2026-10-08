<?php

namespace App\Providers;

use App\Services\Discord;
use App\Services\GuildAccess;
use App\Support\Accent;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Discord::class, fn () => new Discord(
            clientId: config('services.discord.client_id'),
            clientSecret: config('services.discord.client_secret'),
            redirectUri: config('services.discord.redirect'),
            botToken: config('services.discord.bot_token'),
        ));

        $this->app->singleton(GuildAccess::class);
    }

    public function boot(): void
    {
        View::composer(['components.layouts.app', 'home'], function ($view) {
            $view->with([
                'palette' => Accent::forUser(auth()->user()),
                'botAvatar' => app(Discord::class)->botAvatarUrl(),
            ]);
        });
    }
}
