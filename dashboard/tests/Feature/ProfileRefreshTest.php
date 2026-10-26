<?php

namespace Tests\Feature;

use App\Models\DashboardUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileRefreshTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '123456789012345678';

    public function test_avatar_and_name_are_refreshed_once_an_hour(): void
    {
        config(['services.discord.bot_token' => 'bot-token']);
        $avatar = 'newhash';

        Http::fake(function ($request) use (&$avatar) {
            return str_ends_with($request->url(), '/users/'.self::USER)
                ? Http::response(['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Justin', 'avatar' => $avatar])
                : Http::response([], 404);
        });

        $user = DashboardUser::create(['id' => (int) self::USER, 'username' => 'newtox', 'global_name' => 'Newtox', 'avatar' => 'oldhash']);
        $this->actingAs($user);

        $this->get(route('info'))->assertOk();

        $user->refresh();
        $this->assertSame('newhash', $user->avatar);
        $this->assertSame('Justin', $user->global_name);
        $this->assertStringContainsString('/newhash.png', $user->avatar_url);

        $avatar = 'later';
        $this->get(route('info'))->assertOk();
        $this->assertSame('newhash', $user->refresh()->avatar);

        $this->travel(61)->minutes();
        cache()->forget('discord:user:'.self::USER.':profile');
        $this->get(route('info'))->assertOk();
        $this->assertSame('later', $user->refresh()->avatar);
    }

    public function test_a_failing_lookup_keeps_the_stored_profile(): void
    {
        config(['services.discord.bot_token' => 'bot-token']);
        Http::fake(fn () => Http::response(['message' => 'Unknown User'], 404));

        $user = DashboardUser::create(['id' => (int) self::USER, 'username' => 'newtox', 'global_name' => 'Newtox', 'avatar' => 'oldhash']);
        $this->actingAs($user)->get(route('info'))->assertOk();

        $this->assertSame('oldhash', $user->refresh()->avatar);
    }
}
