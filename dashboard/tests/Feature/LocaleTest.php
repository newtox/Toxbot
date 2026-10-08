<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\DashboardUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_to_browser_language(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
            ->get('/')
            ->assertSee('Mit Discord anmelden');

        $this->withHeader('Accept-Language', 'ja-JP,ja;q=0.9')
            ->get('/')
            ->assertSee('Discord でログイン');
    }

    public function test_switching_language_is_stored_in_session(): void
    {
        $this->from('/')->get('/language/fr')->assertRedirect('/');

        $this->get('/')->assertSee('Se connecter avec Discord');
    }

    public function test_unknown_language_is_ignored(): void
    {
        $this->get('/language/xx')->assertSessionMissing('locale');
    }

    public function test_every_language_has_every_key(): void
    {
        $locales = array_keys(config('app.locales'));
        $groups = array_map(fn ($f) => basename($f, '.php'), glob(lang_path('en/*.php')));

        foreach ($groups as $group) {
            $expected = array_keys(require lang_path("en/{$group}.php"));

            foreach ($locales as $locale) {
                $this->assertFileExists(lang_path("{$locale}/{$group}.php"));
                $this->assertSame($expected, array_keys(require lang_path("{$locale}/{$group}.php")), "{$locale}/{$group}.php");
            }
        }
    }

    public function test_logged_in_users_see_their_bot_language(): void
    {
        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox']);
        BotUser::create(['id' => '123456789012345678', 'language' => 'es_es', 'color' => '#7289da']);

        $this->actingAs($user)
            ->withSession(['locale' => 'da'])
            ->get('/profile')
            ->assertSee('Cerrar sesión');
    }

    public function test_switching_language_also_changes_the_bot_language(): void
    {
        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox']);

        $this->actingAs($user)->from('/profile')->get('/language/ja')->assertRedirect('/profile');

        $this->assertSame('ja_jp', BotUser::find('123456789012345678')->language);
        $this->get('/profile')->assertSee('ログアウト');
    }

    public function test_requests_without_a_language_use_the_default_locale(): void
    {
        config(['app.locale' => 'de']);

        $this->get('/', ['Accept-Language' => ''])->assertSee('Mit Discord anmelden');
    }

    public function test_guests_are_sent_to_the_start_page_instead_of_discord(): void
    {
        $this->get('/profile')->assertRedirect('/');
    }
}
