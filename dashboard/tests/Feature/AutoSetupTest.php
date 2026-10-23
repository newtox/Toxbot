<?php

namespace Tests\Feature;

use App\Livewire\AutoSetup;
use App\Livewire\GuildSettings;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AutoSetupTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const BOT = '999999999999999999';

    private const USER = '123456789012345678';

    private array $channels = [];

    private array $roles = [];

    private int $nextId = 5000;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);
        app()->setLocale('de');

        $this->channels = [
            ['id' => '40', 'type' => 4, 'name' => 'Allgemein', 'position' => 0, 'permission_overwrites' => []],
            ['id' => '41', 'type' => 0, 'name' => 'chat', 'position' => 0, 'parent_id' => '40', 'permission_overwrites' => []],
            ['id' => '42', 'type' => 2, 'name' => 'Talk', 'position' => 1, 'parent_id' => '40', 'permission_overwrites' => []],
        ];
        $this->roles = [
            ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0, 'managed' => false, 'permissions' => '0'],
            ['id' => '30', 'name' => 'Moderator', 'position' => 2, 'color' => 0, 'managed' => false, 'permissions' => (string) 0x2],
            ['id' => '31', 'name' => 'Mitglied', 'position' => 1, 'color' => 0, 'managed' => false, 'permissions' => '0'],
            ['id' => '21', 'name' => 'Toxbot', 'position' => 5, 'color' => 0, 'managed' => true, 'permissions' => (string) (0x10 | 0x10000000)],
        ];

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');
            $method = $request->method();

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) 0x20],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => self::BOT, 'username' => 'Toxbot', 'avatar' => null]),
                $method === 'POST' && str_ends_with($url, '/guilds/'.self::GUILD.'/channels') => Http::response($this->created($request->data())),
                $method === 'POST' && str_ends_with($url, '/guilds/'.self::GUILD.'/roles') => Http::response($this->createdRole($request->data())),
                $method === 'PUT' && str_contains($url, '/permissions/') => Http::response(null, 204),
                str_contains($url, '/messages') => Http::response(['id' => '700']),
                str_ends_with($url, '/guilds/'.self::GUILD.'/channels') => Http::response($this->channels),
                str_ends_with($url, '/guilds/'.self::GUILD.'/roles') => Http::response($this->roles),
                str_contains($url, '/members/'.self::BOT) => Http::response(['roles' => ['21']]),
                str_contains($url, '/guilds/'.self::GUILD) => Http::response(['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null]),
                default => Http::response([], 404),
            };
        });
    }

    private function created(array $data): array
    {
        $channel = [
            'id' => (string) $this->nextId++,
            'type' => $data['type'],
            'name' => $data['type'] === 4 ? $data['name'] : mb_strtolower($data['name']),
            'position' => 9,
            'parent_id' => $data['parent_id'] ?? null,
            'permission_overwrites' => $data['permission_overwrites'] ?? [],
        ];
        $this->channels[] = $channel;

        return $channel;
    }

    private function createdRole(array $data): array
    {
        $role = ['id' => (string) $this->nextId++, 'name' => $data['name'], 'position' => 1, 'color' => 0, 'managed' => false, 'permissions' => '0'];
        $this->roles[] = $role;

        return $role;
    }

    private function login(): void
    {
        $user = DashboardUser::create(['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'user-token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    private function autoSetup(array $areas = ['tickets', 'moderation', 'protection', 'verification'])
    {
        return Livewire::test(AutoSetup::class, ['guildId' => self::GUILD, 'guildName' => 'Toxic Lounge', 'areas' => $areas]);
    }

    private function channelNamed(string $name): ?array
    {
        return collect($this->channels)->firstWhere('name', $name);
    }

    public function test_preselects_moderator_roles_and_leaves_verification_off(): void
    {
        $this->login();

        $this->autoSetup()
            ->assertSet('teamRoles', ['30'])
            ->assertSet('selected', ['tickets', 'moderation', 'protection']);

        $this->autoSetup(['protection', 'verification'])->assertSet('selected', ['protection']);
        $this->autoSetup(['verification'])->assertSet('selected', ['verification']);
    }

    public function test_sets_up_everything_and_reports_each_step(): void
    {
        $this->login();

        $component = $this->autoSetup()
            ->set('selected', ['tickets', 'moderation', 'protection', 'verification'])
            ->call('run')
            ->assertHasNoErrors()
            ->assertDispatched('auto-setup-done');

        $steps = $component->get('steps');
        $this->assertNotEmpty($steps);
        $this->assertSame([], array_values(array_filter($steps, fn ($step) => ! $step['ok'])));

        $row = Guild::find(self::GUILD);
        $category = $this->channelNamed('Tickets');
        $this->assertSame($category['id'], $row->ticket_category);
        $this->assertSame($this->channelNamed('tickets')['id'], $row->ticket_panel_channel);
        $this->assertSame($this->channelNamed('ticket-log')['id'], $row->ticket_log_channel);
        $this->assertSame($this->channelNamed('mod-log')['id'], $row->modlog_channel);
        $this->assertSame('700', $row->ticket_panel_message_id);
        $this->assertTrue($row->mod_dm);
        $this->assertTrue($row->link_scam);
        $this->assertTrue($row->link_invites);
        $this->assertTrue($row->raid_enabled);
        $this->assertSame('30', $row->link_exempt_roles);

        $type = TicketType::sole();
        $this->assertSame('Support', $type->name);
        $this->assertSame(['30'], $type->roleIds());

        $role = collect($this->roles)->firstWhere('name', 'Unverifiziert');
        $verify = $this->channelNamed('verifizierung');
        $this->assertTrue($row->verify_enabled);
        $this->assertSame($role['id'], $row->verify_role);
        $this->assertSame($verify['id'], $row->verify_channel);
        $this->assertSame('700', $row->verify_message_id);

        $modlog = $this->channelNamed('mod-log');
        $this->assertSame((string) 0x400, collect($modlog['permission_overwrites'])->firstWhere('id', self::GUILD)['deny']);
        $this->assertNotNull(collect($modlog['permission_overwrites'])->firstWhere('id', '30'));
        $this->assertNotNull(collect($modlog['permission_overwrites'])->firstWhere('id', self::BOT));
        $this->assertSame((string) (0x800 | 0x40 | (1 << 35) | (1 << 36) | (1 << 38)), collect($this->channelNamed('tickets')['permission_overwrites'])->firstWhere('id', self::GUILD)['deny']);

        foreach (['40', '41', '42', $category['id'], $modlog['id']] as $hidden) {
            Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
                && str_ends_with($r->url(), "/channels/{$hidden}/permissions/{$role['id']}")
                && $r['deny'] === (string) 0x400);
        }
        Http::assertNotSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), "/channels/{$verify['id']}/permissions/{$role['id']}"));
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/guilds/'.self::GUILD.'/channels')
            && $r->header('X-Audit-Log-Reason')[0] === rawurlencode(__('setup.audit_reason', ['user' => 'Newtox'])));
    }

    public function test_reuses_configured_and_same_named_channels(): void
    {
        $this->login();
        $this->channels[] = ['id' => '50', 'type' => 0, 'name' => 'mod-log', 'position' => 3, 'permission_overwrites' => []];
        $this->channels[] = ['id' => '51', 'type' => 0, 'name' => 'meine-logs', 'position' => 4, 'permission_overwrites' => []];
        Guild::create(['id' => self::GUILD, 'ticket_log_channel' => '51']);
        TicketType::create(['guild' => self::GUILD, 'name' => 'Bewerbung']);

        $this->autoSetup()->set('selected', ['tickets', 'moderation'])->call('run')->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertSame('50', $row->modlog_channel);
        $this->assertSame('51', $row->ticket_log_channel);
        $this->assertSame(1, TicketType::count());
        $this->assertNull($this->channelNamed('ticket-log'));
        $this->assertSame(1, collect($this->channels)->where('name', 'mod-log')->count());
    }

    public function test_reuses_an_existing_verify_channel_and_role(): void
    {
        $this->login();
        $this->roles[] = ['id' => '32', 'name' => 'Unverified', 'position' => 1, 'color' => 0, 'managed' => false, 'permissions' => '0'];
        $this->channels[] = ['id' => '52', 'type' => 0, 'name' => 'verify', 'position' => 0, 'permission_overwrites' => []];
        $this->channels[1]['permission_overwrites'] = [['id' => '32', 'type' => 0, 'allow' => '0', 'deny' => (string) 0x400]];

        $this->autoSetup(['verification'])->call('run')->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertSame('32', $row->verify_role);
        $this->assertSame('52', $row->verify_channel);
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), '/channels/52/permissions/32') && $r['allow'] === (string) (0x400 | 0x10000));
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/channels/41/permissions/32'));
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/roles'));
    }

    public function test_stops_when_toxbot_lacks_permissions(): void
    {
        $this->login();
        $this->roles[3]['permissions'] = (string) 0x10;

        $steps = $this->autoSetup()->call('run')->get('steps');

        $this->assertCount(1, $steps);
        $this->assertFalse($steps[0]['ok']);
        $this->assertStringContainsString(__('setup.permission_roles'), $steps[0]['text']);
        $this->assertNull(Guild::find(self::GUILD));
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
    }

    public function test_refuses_a_verification_role_above_toxbot(): void
    {
        $this->login();
        $this->roles[] = ['id' => '33', 'name' => 'Unverifiziert', 'position' => 9, 'color' => 0, 'managed' => false, 'permissions' => '0'];

        $steps = $this->autoSetup(['verification'])->call('run')->get('steps');

        $this->assertFalse(end($steps)['ok']);
        $this->assertFalse((bool) Guild::find(self::GUILD)?->verify_enabled);
    }

    public function test_needs_a_team_role_and_an_area(): void
    {
        $this->login();

        $this->autoSetup()->set('teamRoles', [])->set('selected', [])->call('run')
            ->assertHasErrors(['teamRoles', 'selected'])
            ->assertSet('steps', null);

        $this->autoSetup()->set('teamRoles', ['999'])->call('run')->assertHasErrors(['teamRoles.0']);
    }

    public function test_settings_and_overview_show_the_setup(): void
    {
        $this->login();

        $this->get('/servers/'.self::GUILD)->assertOk()->assertSee(__('setup.title'))->assertSee(__('setup.area_verification'));
        $this->get('/servers/'.self::GUILD.'/tickets')->assertOk()->assertSee(__('setup.open'));

        Livewire::test(GuildSettings::class, [
            'guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null],
            'section' => 'moderation',
        ])->set('modlogChannel', '41')->dispatch('auto-setup-done')->assertSet('modlogChannel', null);
    }
}
