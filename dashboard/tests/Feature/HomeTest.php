<?php

namespace Tests\Feature;

use App\Models\DashboardUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_features_premium_and_invite(): void
    {
        config(['services.discord.client_id' => '561253410591211520', 'services.discord.bot_token' => 'bot-token']);
        Http::fake(['discord.com/api/v10/users/@me/guilds*' => Http::response([['id' => '1'], ['id' => '2'], ['id' => '3']])]);

        $this->get('/')
            ->assertOk()
            ->assertSee(__('home.hero_title'))
            ->assertSee(__('home.f_streams_t'))
            ->assertSee(__('home.f_mod_t'))
            ->assertSee(__('home.premium_server_1'))
            ->assertSee('client_id=561253410591211520', false)
            ->assertSee(__('home.stat_servers'));
    }

    public function test_logged_in_users_go_to_their_servers(): void
    {
        $this->actingAs(DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox']));

        $this->get('/')->assertRedirect(route('servers.index'));
    }
}
