<?php

namespace Tests\Feature;

use App\Livewire\AppealList;
use App\Livewire\MemberList;
use App\Livewire\MemberProfile;
use App\Livewire\ModlogList;
use App\Models\DashboardUser;
use App\Models\ModAction;
use App\Models\ModCase;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\UnbanAppeal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const MEMBER = '333333333333333333';

    private const USER = '123456789012345678';

    private int $permissions = 0;

    private array $roles = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function ($request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');
            $member = fn (string $id, array $roles) => [
                'user' => ['id' => $id, 'username' => 'kevin', 'global_name' => 'Kevin', 'avatar' => null],
                'nick' => null,
                'roles' => $roles,
                'joined_at' => '2026-01-02T10:00:00+00:00',
                'communication_disabled_until' => null,
            ];

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) $this->permissions],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => '999', 'username' => 'Toxbot', 'avatar' => null]),
                str_contains($url, '/members/'.self::USER) => Http::response($member(self::USER, $this->roles)),
                str_contains($url, '/members/'.self::MEMBER) => Http::response($member(self::MEMBER, [])),
                str_contains($url, '/members/') => Http::response(['message' => 'Unknown Member'], 404),
                str_contains($url, '/members') => Http::response([$member(self::MEMBER, [])]),
                str_contains($url, '/bans/') => Http::response([], 404),
                str_contains($url, '/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0, 'managed' => false],
                ]),
                default => Http::response([], 404),
            };
        });

        $user = DashboardUser::create(['id' => (int) self::USER, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    public function test_kick_members_opens_the_modlog_but_not_the_settings(): void
    {
        $this->permissions = 0x2;

        $this->get(route('servers.modlog', self::GUILD))->assertOk();
        $this->get(route('servers.members', self::GUILD))->assertOk()->assertSee(route('servers.modlog', self::GUILD));
        $this->get(route('servers.settings', [self::GUILD, 'welcome']))->assertForbidden();
        $this->get(route('servers.appeals', self::GUILD))->assertForbidden();
        $this->get(route('servers.tickets', self::GUILD))->assertForbidden();
        $this->get(route('servers.data', self::GUILD))->assertForbidden();
    }

    public function test_plain_members_see_no_moderation_pages(): void
    {
        $this->get(route('servers.members', self::GUILD))->assertOk()->assertDontSee(route('servers.modlog', self::GUILD));
        $this->get(route('servers.modlog', self::GUILD))->assertForbidden();
        $this->get(route('servers.appeals', self::GUILD))->assertForbidden();
        $this->get(route('servers.tickets', self::GUILD))->assertForbidden();
        Livewire::test(ModlogList::class, ['guildId' => self::GUILD])->assertForbidden();
        Livewire::test(MemberList::class, ['guildId' => self::GUILD])->assertSet('moderator', false);
    }

    public function test_member_profile_only_offers_the_allowed_actions(): void
    {
        $this->permissions = 0x2;
        ModCase::forceCreate(['guild' => self::GUILD, 'number' => 1, 'type' => 'warn', 'user_id' => self::MEMBER, 'active' => true, 'source' => 'command']);

        $profile = Livewire::test(MemberProfile::class, ['guildId' => self::GUILD, 'userId' => self::MEMBER])
            ->assertSet('moderator', true)
            ->assertViewHas('types', ['kick'])
            ->assertViewHas('canRevoke', false)
            ->assertSet('actionType', 'kick');

        $profile->set('actionType', 'ban')->assertSet('actionType', 'kick')->call('submit');
        $this->assertSame(['kick'], ModAction::query()->pluck('type')->all());

        $case = ModCase::query()->first();
        Livewire::test(MemberProfile::class, ['guildId' => self::GUILD, 'userId' => self::MEMBER])
            ->call('revokeWarning', $case->id)
            ->assertForbidden();
    }

    public function test_manage_server_without_moderation_rights_sees_no_actions(): void
    {
        $this->permissions = 0x20;

        Livewire::test(MemberProfile::class, ['guildId' => self::GUILD, 'userId' => self::MEMBER])
            ->assertSet('moderator', true)
            ->assertViewHas('types', [])
            ->assertDontSee(__('moderation.action_title'));
    }

    public function test_ban_members_handles_appeals_but_cannot_toggle_them(): void
    {
        $this->permissions = 0x4;
        $appeal = UnbanAppeal::create(['guild' => self::GUILD, 'user_id' => '555', 'user_name' => 'kira', 'reason' => 'Bitte entbannen, es tut mir leid.']);

        $this->get(route('servers.appeals', self::GUILD))->assertOk();

        Livewire::test(AppealList::class, ['guildId' => self::GUILD])
            ->assertSet('manage', false)
            ->call('toggleEnabled')
            ->assertForbidden();

        Livewire::test(AppealList::class, ['guildId' => self::GUILD])->call('reject', $appeal->id);
        $this->assertSame('rejected', $appeal->fresh()->status);
    }

    public function test_team_roles_see_only_the_tickets_of_their_types(): void
    {
        $this->roles = ['70'];
        $support = TicketType::create(['guild' => self::GUILD, 'name' => 'Support', 'roles' => '70']);
        $report = TicketType::create(['guild' => self::GUILD, 'name' => 'Meldung', 'roles' => '71']);
        $own = $this->ticket(['number' => 1, 'type_id' => $support->id, 'user_name' => 'kira']);
        $other = $this->ticket(['number' => 2, 'type_id' => $report->id, 'type_name' => 'Meldung', 'user_name' => 'jay']);

        $this->get(route('servers.members', self::GUILD))->assertSee(route('servers.tickets', self::GUILD));
        $this->get(route('servers.tickets', self::GUILD))->assertOk()->assertSee('kira')->assertDontSee('jay');
        $this->get(route('servers.ticket', [self::GUILD, $own->id]))->assertOk();
        $this->get(route('servers.ticket', [self::GUILD, $other->id]))->assertNotFound();
    }

    public function test_without_a_team_role_tickets_stay_hidden(): void
    {
        $this->roles = ['72'];
        TicketType::create(['guild' => self::GUILD, 'name' => 'Support', 'roles' => '70']);

        $this->get(route('servers.members', self::GUILD))->assertDontSee(route('servers.tickets', self::GUILD));
        $this->get(route('servers.tickets', self::GUILD))->assertForbidden();
    }

    public function test_manage_server_sees_all_tickets(): void
    {
        $this->permissions = 0x20;
        $type = TicketType::create(['guild' => self::GUILD, 'name' => 'Meldung', 'roles' => '71']);
        $this->ticket(['number' => 1, 'type_id' => $type->id, 'user_name' => 'jay']);
        $this->ticket(['number' => 2, 'type_id' => null, 'user_name' => 'kira']);

        $this->get(route('servers.tickets', self::GUILD))->assertOk()->assertSee('jay')->assertSee('kira');
    }

    private function ticket(array $attributes): Ticket
    {
        $ticket = new Ticket;
        $ticket->forceFill($attributes + [
            'guild' => self::GUILD,
            'type_name' => 'Support',
            'user_id' => '555',
            'channel_id' => '900',
            'status' => 'closed',
            'closed_at' => now(),
        ])->save();

        return $ticket;
    }
}
