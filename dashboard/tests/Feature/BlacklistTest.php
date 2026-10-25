<?php

namespace Tests\Feature;

use App\Livewire\AdminBlacklist;
use App\Models\BlacklistEntry;
use App\Models\DashboardUser;
use App\Services\GuildAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BlacklistTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER = '402483602094555138';

    private const GUILD = '111111111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        config(['toxbot.owner_id' => self::OWNER, 'services.discord.bot_token' => 'bot-token']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                $isBot && $request->method() === 'DELETE' && str_contains($url, '/users/@me/guilds/') => Http::response(null, 204),
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => true, 'permissions' => '8'],
                    ['id' => '222222222222222222', 'name' => 'Other', 'icon' => null, 'owner' => true, 'permissions' => '8'],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD], ['id' => '222222222222222222']]),
                default => Http::response([], 404),
            };
        });
    }

    private function login(string $id, string $name = 'newtox'): DashboardUser
    {
        $user = DashboardUser::create(['id' => (int) $id, 'username' => $name]);
        $user->forceFill(['discord_access_token' => 'user-token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);

        return $user;
    }

    public function test_owner_blocks_users_and_servers(): void
    {
        $this->login(self::OWNER);

        Livewire::test(AdminBlacklist::class)
            ->set('target', '123456789012345678')
            ->set('reason', 'Spam')
            ->call('add')
            ->assertHasNoErrors()
            ->assertSet('notice', __('admin.blacklist_added'))
            ->set('type', 'guild')
            ->set('target', self::GUILD)
            ->call('add')
            ->assertSet('notice', __('admin.blacklist_added_left'))
            ->assertSee('Spam')
            ->set('type', 'user')
            ->set('target', 'abc')
            ->call('add')
            ->assertHasErrors('target')
            ->set('target', self::OWNER)
            ->call('add')
            ->assertHasErrors('target');

        $this->assertTrue(BlacklistEntry::where('user', '123456789012345678')->exists());
        $this->assertTrue(BlacklistEntry::where('guild', self::GUILD)->exists());
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/users/@me/guilds/'.self::GUILD));

        $ids = array_column(app(GuildAccess::class)->manageableGuilds(auth()->user(), true), 'id');
        $this->assertSame(['222222222222222222'], $ids);

        Livewire::test(AdminBlacklist::class)->call('remove', BlacklistEntry::where('guild', self::GUILD)->value('id'));
        $this->assertFalse(BlacklistEntry::where('guild', self::GUILD)->exists());
    }

    public function test_only_the_owner_manages_the_blacklist(): void
    {
        $this->login('123456789012345678', 'someone');

        Livewire::test(AdminBlacklist::class)->assertForbidden();
    }

    public function test_blocked_users_are_logged_out(): void
    {
        BlacklistEntry::create(['user' => '123456789012345678']);
        $this->login('123456789012345678', 'someone');

        $this->get('/profile')->assertRedirect(route('home'))->assertSessionHas('status', __('login.blacklisted'));
        $this->assertGuest();

        BlacklistEntry::create(['user' => self::OWNER]);
        $this->login(self::OWNER);
        $this->get('/profile')->assertOk();
    }
}
