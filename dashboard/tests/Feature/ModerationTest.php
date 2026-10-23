<?php

namespace Tests\Feature;

use App\Livewire\GuildSettings;
use App\Livewire\MemberList;
use App\Livewire\MemberProfile;
use App\Livewire\ModlogList;
use App\Livewire\WarnRules;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\MemberLevel;
use App\Models\ModAction;
use App\Models\ModCase;
use App\Models\WarnRule;
use App\Support\Duration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const MEMBER = '333333333333333333';

    private const BANNED = '444444444444444444';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        $member = fn (string $id, string $name, array $roles = [], ?string $timeout = null) => [
            'user' => ['id' => $id, 'username' => strtolower($name), 'global_name' => $name, 'avatar' => null],
            'nick' => null,
            'roles' => $roles,
            'joined_at' => '2026-01-02T10:00:00+00:00',
            'communication_disabled_until' => $timeout,
        ];

        Http::fake(function ($request) use ($member) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => true, 'permissions' => '32'],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => '999', 'username' => 'Toxbot', 'avatar' => null]),
                str_contains($url, '/members/search') => Http::response([$member(self::MEMBER, 'Kevin', ['20'])]),
                str_contains($url, '/members/'.self::MEMBER) => Http::response($member(self::MEMBER, 'Kevin', ['20'], now()->addHour()->toIso8601String())),
                str_contains($url, '/members/999') => Http::response(['roles' => []]),
                str_contains($url, '/members/') => Http::response(['message' => 'Unknown Member'], 404),
                str_contains($url, '/members') => Http::response([
                    $member(self::MEMBER, 'Kevin', ['20']),
                    $member('555555555555555555', 'Lisa'),
                ]),
                str_contains($url, '/bans/'.self::BANNED) => Http::response(['reason' => 'Spam', 'user' => ['id' => self::BANNED]]),
                str_contains($url, '/bans/') => Http::response([], 404),
                str_contains($url, '/users/'.self::BANNED) => Http::response(['id' => self::BANNED, 'username' => 'spammer', 'avatar' => null]),
                str_contains($url, '/channels') => Http::response([['id' => '11', 'type' => 0, 'name' => 'modlog', 'position' => 0]]),
                str_contains($url, '/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0, 'managed' => false],
                    ['id' => '20', 'name' => 'Mitglied', 'position' => 1, 'color' => 0x57F287, 'managed' => false],
                ]),
                default => Http::response([], 404),
            };
        });

        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    private function modCase(int $number, string $type, string $userId = self::MEMBER, array $extra = []): ModCase
    {
        $case = new ModCase;
        $case->forceFill(array_merge([
            'guild' => self::GUILD,
            'number' => $number,
            'type' => $type,
            'user_id' => $userId,
            'user_name' => 'Kevin',
            'moderator_id' => '123456789012345678',
            'moderator_name' => 'Newtox',
            'reason' => 'Spam in #general',
            'active' => true,
            'source' => 'command',
        ], $extra))->save();

        return $case;
    }

    public function test_duration_parsing(): void
    {
        $this->assertSame(600, Duration::parse('10m'));
        $this->assertSame(93600, Duration::parse('1d 2h'));
        $this->assertNull(Duration::parse('10 minutes'));
        $this->assertNull(Duration::parse(''));
        $this->assertSame('1d 2h', Duration::format(93600));
    }

    public function test_saves_moderation_settings(): void
    {
        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'moderation'])
            ->assertSet('modDm', true)
            ->set('modlogChannel', '11')
            ->set('modDm', false)
            ->call('save')
            ->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertSame('11', $row->modlog_channel);
        $this->assertFalse($row->mod_dm);
    }

    public function test_warn_rules(): void
    {
        Livewire::test(WarnRules::class, ['guildId' => self::GUILD])
            ->set('warns', 3)->set('action', 'timeout')->set('duration', '2h')
            ->call('add')->assertHasNoErrors()
            ->set('warns', 3)->set('action', 'kick')
            ->call('add')->assertHasErrors('warns')
            ->set('warns', 4)->set('action', 'timeout')->set('duration', '30d')
            ->call('add')->assertHasErrors('duration')
            ->set('warns', 5)->set('action', 'ban')->set('duration', '')
            ->call('add')->assertHasNoErrors();

        $this->assertSame(7200, WarnRule::where('warns', 3)->value('duration'));
        $this->assertNull(WarnRule::where('warns', 5)->value('duration'));

        Livewire::test(WarnRules::class, ['guildId' => self::GUILD])
            ->call('remove', WarnRule::where('warns', 3)->value('id'));
        $this->assertSame(1, WarnRule::count());
    }

    public function test_member_list_shows_members_cases_and_search(): void
    {
        $this->modCase(1, 'warn');

        $this->get('/servers/'.self::GUILD.'/members')->assertOk()->assertSee(__('moderation.members_title'));

        Livewire::test(MemberList::class, ['guildId' => self::GUILD])
            ->assertSee('Kevin')->assertSee('Lisa')->assertSee('Mitglied')
            ->assertSee(trans_choice('moderation.cases_count', 1))
            ->set('filter', 'cases')
            ->assertSee('Kevin')->assertDontSee('Lisa')
            ->set('filter', 'all')
            ->set('search', 'kev')
            ->assertSee('Kevin')->assertDontSee('Lisa');
    }

    public function test_member_profile_queues_actions(): void
    {
        $this->modCase(1, 'warn');
        MemberLevel::create(['guild' => self::GUILD, 'user' => self::MEMBER, 'xp' => 350]);

        $this->get('/servers/'.self::GUILD.'/members/'.self::MEMBER)->assertOk()->assertSee('Kevin');

        Livewire::test(MemberProfile::class, ['guildId' => self::GUILD, 'userId' => self::MEMBER])
            ->assertSee('Spam in #general')
            ->assertSee(__('moderation.action_untimeout'))
            ->set('actionType', 'timeout')->set('duration', '')
            ->call('submit')->assertHasErrors('duration')
            ->set('actionType', 'ban')->set('duration', '7d')->set('deleteDays', 1)->set('reason', 'Raid')
            ->call('submit')->assertHasNoErrors()
            ->assertSee(__('moderation.pending'));

        $action = ModAction::sole();
        $this->assertSame('ban', $action->type);
        $this->assertSame(604800, $action->duration);
        $this->assertSame(1, $action->delete_days);
        $this->assertSame('Raid', $action->reason);
        $this->assertSame('pending', $action->status);
        $this->assertSame('123456789012345678', $action->moderator_id);
    }

    public function test_banned_user_can_only_be_unbanned_and_warnings_can_be_revoked(): void
    {
        $case = $this->modCase(1, 'warn', self::BANNED);

        Livewire::test(MemberProfile::class, ['guildId' => self::GUILD, 'userId' => self::BANNED])
            ->assertSee(__('moderation.banned'))
            ->assertSee('Spam')
            ->assertSet('actionType', 'unban')
            ->call('revokeWarning', $case->id);

        $this->assertFalse($case->fresh()->active);
    }

    public function test_finished_action_shows_result(): void
    {
        $case = $this->modCase(7, 'warn');

        $component = Livewire::test(MemberProfile::class, ['guildId' => self::GUILD, 'userId' => self::MEMBER])
            ->set('actionType', 'warn')
            ->call('submit');

        $action = ModAction::sole();
        $action->forceFill(['status' => 'done', 'case_id' => $case->id])->save();
        $component->call('$refresh')->assertSee(__('moderation.done', ['number' => 7]));

        $action->forceFill(['status' => 'failed', 'error' => 'mod_error_hierarchy'])->save();
        $component->call('$refresh')->assertSee(__('moderation.mod_error_hierarchy'));

        $component->call('dismiss')->assertDontSee(__('moderation.mod_error_hierarchy'));
    }

    public function test_modlog_filters(): void
    {
        $this->modCase(1, 'warn');
        $this->modCase(2, 'ban', '555555555555555555', ['user_name' => 'Lisa', 'reason' => 'Raid', 'duration' => 86400]);

        $this->get('/servers/'.self::GUILD.'/modlog')->assertOk();

        Livewire::test(ModlogList::class, ['guildId' => self::GUILD])
            ->assertSee('Kevin')->assertSee('Lisa')->assertSee('1d')
            ->set('type', 'ban')->assertDontSee('Kevin')->assertSee('Lisa')
            ->set('type', '')->set('search', '#1')->assertSee('Kevin')->assertDontSee('Lisa');
    }

    public function test_dates_follow_the_viewers_timezone(): void
    {
        $case = $this->modCase(1, 'warn');
        $case->forceFill(['created_at' => '2026-10-23 11:49:00'])->save();

        $this->withUnencryptedCookie('tz', 'Europe/Berlin')
            ->get('/servers/'.self::GUILD.'/modlog')
            ->assertSee('1:49')
            ->assertDontSee('11:49');

        $this->withUnencryptedCookie('tz', 'Not/AZone')
            ->get('/servers/'.self::GUILD.'/modlog')
            ->assertSee('11:49');
    }

    public function test_overview_lists_moderation(): void
    {
        $this->modCase(1, 'warn');

        $this->get('/servers/'.self::GUILD)
            ->assertOk()
            ->assertSee(route('servers.settings', [self::GUILD, 'moderation']))
            ->assertSee(trans_choice('moderation.cases_count', 1));
    }
}
