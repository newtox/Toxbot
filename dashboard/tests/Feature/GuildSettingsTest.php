<?php

namespace Tests\Feature;

use App\Livewire\GuildSettings;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\Tag;
use App\Models\TagGuildBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GuildSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const OTHER_GUILD = '222222222222222222';

    private const BOT = '999999999999999999';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function ($request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) 0x20],
                    ['id' => self::OTHER_GUILD, 'name' => 'Fremd', 'icon' => null, 'owner' => false, 'permissions' => '0'],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD], ['id' => self::OTHER_GUILD],
                ]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => self::BOT, 'username' => 'Toxbot', 'avatar' => null]),
                str_contains($url, '/channels') => Http::response([
                    ['id' => '10', 'type' => 4, 'name' => 'Info', 'position' => 0],
                    ['id' => '11', 'type' => 0, 'name' => 'willkommen', 'position' => 0, 'parent_id' => '10'],
                    ['id' => '12', 'type' => 2, 'name' => 'Voice', 'position' => 1, 'parent_id' => '10'],
                ]),
                str_contains($url, '/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0, 'managed' => false],
                    ['id' => '20', 'name' => 'Mitglied', 'position' => 1, 'color' => 0x57F287, 'managed' => false],
                    ['id' => '21', 'name' => 'Toxbot', 'position' => 2, 'color' => 0, 'managed' => true],
                    ['id' => '22', 'name' => 'Admin', 'position' => 3, 'color' => 0, 'managed' => false],
                ]),
                str_contains($url, '/members/') => Http::response(['roles' => ['21']]),
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

    public function test_server_list_shows_managed_and_member_servers(): void
    {
        $this->login();

        $this->get('/servers')
            ->assertOk()
            ->assertSee('Toxic Lounge')
            ->assertSee('Fremd')
            ->assertSee(__('servers.member'));
    }

    public function test_overview_and_section_pages(): void
    {
        $this->login();
        Guild::create(['id' => self::GUILD, 'levelup_enabled' => true]);

        $this->get('/servers/'.self::GUILD)
            ->assertOk()
            ->assertSee(route('servers.settings', [self::GUILD, 'streams']))
            ->assertSee(__('guild.levelup_title'));

        $this->get('/servers/'.self::GUILD.'/liverole')->assertOk()->assertSee(__('streams.live_role_hint'));
        $this->get('/servers/'.self::GUILD.'/streams')->assertOk()->assertDontSee(__('streams.live_role_hint'));
        $this->get('/servers/'.self::GUILD.'/general')->assertRedirect(route('servers.settings', [self::GUILD, 'welcome']));
        $this->get('/servers/'.self::GUILD.'/protection')->assertRedirect(route('servers.settings', [self::GUILD, 'verification']));
        $this->get('/servers/'.self::GUILD.'/nope')->assertNotFound();
        $this->get('/servers/'.self::OTHER_GUILD.'/welcome')->assertForbidden();
    }

    public function test_members_without_permission_only_get_members_and_starboard(): void
    {
        $this->login();

        $this->get('/servers/'.self::OTHER_GUILD)->assertRedirect(route('servers.members', self::OTHER_GUILD));
        $this->get('/servers/'.self::OTHER_GUILD.'/welcome')->assertForbidden();
        $this->get('/servers/'.self::OTHER_GUILD.'/modlog')->assertForbidden();
        $this->get('/servers/'.self::OTHER_GUILD.'/ticket-history')->assertForbidden();
        $this->get('/servers/'.self::OTHER_GUILD.'/members')->assertOk()->assertSee(__('moderation.members_description_member'));
        $this->get('/servers/'.self::OTHER_GUILD.'/starboard')->assertOk()
            ->assertSee(__('starboard.not_set_up'))
            ->assertDontSee(__('starboard.threshold'));
        $this->get('/servers/333333333333333333')->assertForbidden();
    }

    public function test_saves_welcome_message_and_autorole(): void
    {
        $this->login();

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null]])
            ->set('welcomeEnabled', true)
            ->set('welcomeChannel', '11')
            ->set('welcomeMsg', 'Hey &mention, willkommen auf &server!')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('saved');

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'autorole'])
            ->set('autorole', '20')
            ->call('save')
            ->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertSame('11', $row->welcome_channel);
        $this->assertSame('Hey &mention, willkommen auf &server!', $row->welcome_msg);
        $this->assertSame('20', $row->autorole);
        $this->assertNull($row->bye_channel);
    }

    public function test_rejects_voice_channel_and_roles_above_the_bot(): void
    {
        $this->login();

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null]])
            ->set('welcomeEnabled', true)
            ->set('welcomeChannel', '12')
            ->set('welcomeMsg', 'Hallo')
            ->call('save')
            ->assertHasErrors(['welcomeChannel']);

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'autorole'])
            ->set('autorole', '22')
            ->call('save')
            ->assertHasErrors(['autorole']);

        $this->assertNull(Guild::find(self::GUILD));
    }

    public function test_disabling_keeps_the_text_but_clears_the_channel(): void
    {
        $this->login();
        Guild::create(['id' => self::GUILD, 'welcome_channel' => '11', 'welcome_msg' => 'Hallo &user']);

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null]])
            ->assertSet('welcomeEnabled', true)
            ->set('welcomeEnabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertNull($row->welcome_channel);
        $this->assertSame('Hallo &user', $row->welcome_msg);
    }

    public function test_saves_level_up_and_tag_settings(): void
    {
        $this->login();

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'levels'])
            ->assertSet('levelupEnabled', false)
            ->set('levelupEnabled', true)
            ->set('levelupChannel', '11')
            ->set('levelupDm', true)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'tags'])
            ->assertSet('tagsEnabled', true)
            ->set('tagsEnabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertFalse($row->tags_enabled);
        $this->assertTrue($row->levelup_enabled);
        $this->assertTrue($row->levelup_dm);
        $this->assertSame('11', $row->levelup_channel);
    }

    public function test_level_up_channel_must_be_a_text_channel(): void
    {
        $this->login();

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'levels'])
            ->set('levelupEnabled', true)
            ->set('levelupChannel', '12')
            ->call('save')
            ->assertHasErrors('levelupChannel');
    }

    public function test_unblocks_a_tag(): void
    {
        $this->login();
        $tag = Tag::create(['name' => 'spam', 'owner' => '1', 'content' => 'x']);
        TagGuildBlock::create(['guild' => self::GUILD, 'tag_id' => $tag->id]);

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'tags'])
            ->assertSee('spam')
            ->call('unblockTag', $tag->id);

        $this->assertSame(0, TagGuildBlock::count());
    }

    public function test_saves_stream_and_live_role_settings(): void
    {
        $this->login();

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'streams'])
            ->set('streamChannel', '11')
            ->set('streamRole', '22')
            ->set('streamMsg', '&role &streamer ist live!')
            ->set('streamVideoMsg', 'Neues Video: &title &link')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'liverole'])
            ->set('liveRole', '20')
            ->set('liveRoleRequired', '22')
            ->call('save')
            ->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertSame('11', $row->stream);
        $this->assertSame('22', $row->notify);
        $this->assertSame('&role &streamer ist live!', $row->stream_msg);
        $this->assertSame('Neues Video: &title &link', $row->stream_video_msg);
        $this->assertSame('20', $row->live_role);
        $this->assertSame('22', $row->live_role_required);

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'liverole'])
            ->set('liveRole', '22')
            ->call('save')
            ->assertHasErrors('liveRole');
    }
}
