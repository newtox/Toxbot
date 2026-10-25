<?php

namespace Tests\Feature;

use App\Livewire\CommandList;
use App\Models\BotStatus;
use App\Models\BotUser;
use App\Models\DashboardUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class InfoTest extends TestCase
{
    use RefreshDatabase;

    private const MANAGED = '111111111111111111';

    private const JOINED = '222222222222222222';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => '555555555555555555']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                str_contains($url, '/applications/555555555555555555/commands') => Http::response([
                    ['type' => 1, 'name' => 'hug', 'description' => 'Hug someone.', 'description_localizations' => ['de' => 'Umarme jemanden.'], 'default_member_permissions' => null, 'options' => [
                        ['type' => 6, 'name' => 'user', 'description' => 'Who to hug.', 'description_localizations' => ['de' => 'Wen du umarmst.'], 'required' => true],
                    ]],
                    ['type' => 1, 'name' => 'mod', 'description' => 'Moderation.', 'default_member_permissions' => '1099511627776', 'options' => [
                        ['type' => 1, 'name' => 'warn', 'description' => 'Warn a member.', 'options' => [
                            ['type' => 6, 'name' => 'member', 'description' => 'The member.', 'required' => true],
                            ['type' => 3, 'name' => 'reason', 'description' => 'Why.'],
                        ]],
                        ['type' => 1, 'name' => 'kick', 'description' => 'Kick a member.'],
                    ]],
                    ['type' => 1, 'name' => 'system', 'description' => 'Commands for the developer.'],
                    ['type' => 2, 'name' => 'Profile', 'description' => ''],
                ]),
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::MANAGED, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => true, 'permissions' => '32'],
                    ['id' => self::JOINED, 'name' => 'Valheim Crew', 'icon' => null, 'owner' => false, 'permissions' => '0'],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::MANAGED], ['id' => self::JOINED]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => '999', 'username' => 'Toxbot', 'avatar' => null]),
                str_contains($url, '/channels') || str_contains($url, '/roles') => Http::response([]),
                str_contains($url, '/members/') => Http::response(['roles' => []]),
                default => Http::response([], 404),
            };
        });
    }

    private function login(): DashboardUser
    {
        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'user-token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);

        return $user;
    }

    public function test_general_page_links_everything(): void
    {
        $this->login();
        config(['toxbot.contact_email' => 'hallo@toxbot.test', 'toxbot.support_invite' => null]);

        $this->get('/info')
            ->assertOk()
            ->assertSee('https://github.com/newtox/Toxbot/issues')
            ->assertSee('PRIVACY_POLICY.md')
            ->assertSee('mailto:hallo@toxbot.test', false)
            ->assertSee(route('profile.data'))
            ->assertSee(route('info.system'))
            ->assertDontSee(__('info.support_server'))
            ->assertDontSee('#readme');
    }

    public function test_commands_are_listed_from_discord(): void
    {
        $this->login();
        BotUser::create(['id' => '123456789012345678', 'language' => 'de_de', 'color' => '#7289da']);

        $this->get('/info/commands')->assertOk()->assertSee(__('info.commands_title'));

        Livewire::test(CommandList::class)
            ->assertSee('/hug')
            ->assertSee('Umarme jemanden.')
            ->assertSee('Wen du umarmst.')
            ->assertSee('/mod warn')
            ->assertSee('/mod kick')
            ->assertSee(__('info.commands_restricted'))
            ->assertDontSee('/system')
            ->assertDontSee('Profile')
            ->set('search', '/mod kick')
            ->assertSee('/mod kick')
            ->assertDontSee('/hug');
    }

    public function test_system_page_shows_bot_status(): void
    {
        $this->login();
        config(['toxbot.commit' => 'abcdef1234567890', 'toxbot.build_date' => '2026-10-25T10:00:00Z']);

        $this->get('/info/system')->assertOk()->assertSee(__('info.no_status'))->assertSee('abcdef1')->assertSee(PHP_VERSION);

        BotStatus::create(['id' => 1, 'started_at' => now()->subDay(), 'heartbeat_at' => now(), 'commit' => '1234567abcdef', 'dart_version' => '3.9.0', 'guilds' => 42, 'latency_ms' => 87, 'memory_bytes' => 120 * 1024 * 1024, 'user_id' => '999']);
        $this->get('/info/system')->assertOk()->assertSee(__('info.online'))->assertSee('87 ms')->assertSee('github.com/newtox/Toxbot/commit/1234567abcdef');

        BotStatus::query()->update(['heartbeat_at' => now()->subMinutes(10)]);
        $this->get('/info/system')->assertOk()->assertSee(__('info.offline'));
    }

    public function test_user_area_has_its_own_pages(): void
    {
        $this->login();

        $this->get('/profile')->assertOk()->assertSee(route('profile.settings'))->assertSee(route('profile.premium'))->assertSee(route('profile.data'));
        $this->get('/profile/settings')->assertOk()->assertSee(__('profile.language_title'));
        $this->get('/profile/premium')->assertOk()->assertSee(__('profile.premium_inactive'))->assertSee(__('profile.premium_server_title'));
        $this->get('/profile/data')->assertOk()->assertSee(__('data.account_title'));
        $this->get('/shop')->assertOk()->assertSee(route('profile.premium'));
    }

    public function test_server_navigation_and_data_page(): void
    {
        $this->login();

        $this->get('/servers/'.self::MANAGED.'/data')
            ->assertOk()
            ->assertSee(__('data.reset_title'))
            ->assertSee(route('servers.settings', [self::MANAGED, 'starboard']))
            ->assertSee(route('servers.data', self::MANAGED));

        $this->get('/servers/'.self::MANAGED.'/welcome')->assertOk()->assertSee(__('guild.reload'));
        foreach (['language', 'tags', 'raid'] as $section) {
            $this->get('/servers/'.self::MANAGED.'/'.$section)->assertOk()->assertDontSee(__('guild.reload'));
        }

        $this->get('/servers/'.self::JOINED.'/data')->assertForbidden();
        $this->get('/servers/'.self::JOINED.'/members')->assertDontSee(route('servers.data', self::JOINED));
    }
}
