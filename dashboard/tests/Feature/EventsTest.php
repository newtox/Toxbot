<?php

namespace Tests\Feature;

use App\Livewire\EventList;
use App\Livewire\GuildSettings;
use App\Models\DashboardUser;
use App\Models\GuildEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const USER = '123456789012345678';

    private array $requests = [];

    private int $nextId = 500000000000000000;

    private array $failingDms = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $method = $request->method();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');
            if ($isBot && $method !== 'GET') {
                $this->requests[] = ['method' => $method, 'url' => $url, 'data' => json_decode($request->body(), true)];
            }

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => true, 'permissions' => '8'],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => '999', 'username' => 'Toxbot']),
                $isBot && $method === 'POST' && str_ends_with($url, '/users/@me/channels') => in_array($request['recipient_id'], $this->failingDms, true)
                    ? Http::response(['message' => 'Cannot send messages to this user'], 403)
                    : Http::response(['id' => 'dm-'.$request['recipient_id']]),
                $isBot && $method === 'POST' && str_contains($url, '/channels/dm-') => Http::response(['id' => 'msg']),
                $isBot && $method === 'POST' && str_ends_with($url, '/guilds/'.self::GUILD.'/roles') => Http::response(['id' => (string) $this->nextId++]),
                $isBot && $method === 'POST' && str_ends_with($url, '/guilds/'.self::GUILD.'/channels') => Http::response(['id' => (string) $this->nextId++]),
                $isBot && in_array($method, ['PUT', 'DELETE'], true) => Http::response(null, 204),
                str_contains($url, '/guilds/'.self::GUILD.'/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0],
                    ['id' => '700000000000000001', 'name' => 'Orga', 'position' => 3, 'color' => 0],
                    ['id' => '700000000000000002', 'name' => 'Gamer', 'position' => 2, 'color' => 0],
                    ['id' => '700000000000000003', 'name' => 'Toxbot', 'position' => 4, 'color' => 0, 'tags' => ['bot_id' => '999']],
                ]),
                str_contains($url, '/guilds/'.self::GUILD.'/channels') => Http::response([]),
                str_contains($url, '/members/search') => Http::response([
                    ['user' => ['id' => '800000000000000002', 'username' => 'bennyboy', 'global_name' => 'Benny'], 'roles' => ['700000000000000002']],
                    ['user' => ['id' => '800000000000000001', 'username' => 'anna', 'global_name' => 'Anna'], 'roles' => []],
                    ['user' => ['id' => '800000000000000009', 'username' => 'somebot', 'bot' => true], 'roles' => []],
                ]),
                str_contains($url, '/guilds/'.self::GUILD.'/members') => Http::response([
                    ['user' => ['id' => '800000000000000001', 'username' => 'anna', 'global_name' => 'Anna'], 'roles' => []],
                    ['user' => ['id' => '800000000000000002', 'username' => 'ben', 'avatar' => 'abc'], 'nick' => 'Benny', 'roles' => ['700000000000000002']],
                    ['user' => ['id' => '800000000000000003', 'username' => 'cleo'], 'roles' => ['700000000000000002']],
                    ['user' => ['id' => '800000000000000009', 'username' => 'somebot', 'bot' => true], 'roles' => ['700000000000000002']],
                ]),
                str_contains($url, '/guilds/'.self::GUILD) => Http::response(['id' => self::GUILD, 'name' => 'Toxic Lounge']),
                default => Http::response([], 404),
            };
        });

        $user = DashboardUser::create(['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    private function requests(string $method, string $contains): array
    {
        return array_values(array_filter($this->requests, fn ($r) => $r['method'] === $method && str_contains($r['url'], $contains)));
    }

    private function createEvent(?string $team = '700000000000000001'): GuildEvent
    {
        Livewire::test(EventList::class, ['guildId' => self::GUILD])
            ->set('name', '  Spieleabend ')
            ->set('teamRole', $team ?? '')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSee(__('events.created', ['name' => 'Spieleabend']));

        return GuildEvent::query()->sole();
    }

    public function test_events_get_a_role_a_category_and_three_channels(): void
    {
        $event = $this->createEvent();

        $this->assertSame(['Spieleabend', '0'], [$this->requests('POST', '/roles')[0]['data']['name'], $this->requests('POST', '/roles')[0]['data']['permissions']]);
        $channels = array_column($this->requests('POST', '/guilds/'.self::GUILD.'/channels'), 'data');
        $this->assertSame([[4, 'Spieleabend'], [0, 'info'], [0, 'chat'], [2, 'Voice']], array_map(fn ($c) => [$c['type'], $c['name']], $channels));

        $overwrites = fn (array $channel) => collect($channel['permission_overwrites'])->keyBy('id');
        $info = $overwrites($channels[1]);
        $chat = $overwrites($channels[2]);
        $view = 1 << 10;
        $send = 1 << 11;
        $this->assertSame((string) $view, $info[self::GUILD]['deny']);
        $this->assertSame(0, (int) $info[$event->role_id]['allow'] & $send);
        $this->assertSame($send, (int) $info[$event->role_id]['deny'] & $send);
        $this->assertSame($send, (int) $info['700000000000000001']['allow'] & $send);
        $this->assertSame($send, (int) $info[self::USER]['allow'] & $send);
        $this->assertSame($send, (int) $chat[$event->role_id]['allow'] & $send);
        $this->assertSame($view, (int) $chat['999']['allow'] & $view);

        $this->assertSame('700000000000000001', $event->team_role_id);
        $this->assertNotNull($event->info_channel_id);
        $this->assertCount(1, $this->requests('PUT', '/members/'.self::USER.'/roles/'.$event->role_id));
    }

    public function test_event_names_teams_and_limits_are_checked(): void
    {
        Livewire::test(EventList::class, ['guildId' => self::GUILD])
            ->set('name', '')->call('create')->assertHasErrors('name')
            ->set('name', str_repeat('a', 51))->call('create')->assertHasErrors('name')
            ->set('name', 'Ok')->set('teamRole', '700000000000000003')->call('create')->assertHasErrors('teamRole');

        foreach (range(1, 10) as $i) {
            GuildEvent::query()->create(['guild' => self::GUILD, 'name' => "E{$i}", 'created_by' => self::USER]);
        }
        Livewire::test(EventList::class, ['guildId' => self::GUILD])
            ->set('name', 'Elf')->call('create')->assertHasErrors('name');

        $this->assertSame([], $this->requests('POST', '/roles'));
    }

    public function test_members_are_invited_by_direct_message(): void
    {
        $event = $this->createEvent(null);
        $this->failingDms = ['800000000000000003'];

        Livewire::test(EventList::class, ['guildId' => self::GUILD])
            ->call('startInvite', $event->id)
            ->set('search', 'an')
            ->assertSee('Anna')
            ->assertDontSee('somebot')
            ->call('addUser', '800000000000000001', 'Anna')
            ->assertSet('inviteUsers', ['800000000000000001' => 'Anna'])
            ->set('inviteRoles', ['700000000000000002'])
            ->set('inviteMessage', 'Freitag 20 Uhr')
            ->call('sendInvites')
            ->assertHasNoErrors()
            ->assertSee(__('events.invited', ['count' => 2, 'name' => 'Spieleabend']))
            ->assertSee(__('events.invite_failed', ['count' => 1]))
            ->assertSet('invitingId', null);

        $dms = $this->requests('POST', '/channels/dm-');
        $this->assertSame(['/channels/dm-800000000000000001/messages', '/channels/dm-800000000000000002/messages'],
            array_map(fn ($r) => parse_url($r['url'], PHP_URL_PATH) === null ? '' : substr(parse_url($r['url'], PHP_URL_PATH), strlen('/api/v10')), $dms));
        $payload = $dms[0]['data'];
        $this->assertSame('Freitag 20 Uhr', $payload['embeds'][0]['description']);
        $this->assertSame(['event_join:'.$event->id, 'event_leave:'.$event->id], array_column($payload['components'][0]['components'], 'custom_id'));
    }

    public function test_participants_and_event_roles_are_not_offered_again(): void
    {
        $event = $this->createEvent(null);
        GuildEvent::query()->whereKey($event->id)->update(['role_id' => '700000000000000002']);

        Livewire::test(EventList::class, ['guildId' => self::GUILD])
            ->assertDontSee('value="700000000000000002"', false)
            ->assertSee('value="700000000000000001"', false)
            ->call('startInvite', $event->id)
            ->set('inviteRoles', ['700000000000000002'])
            ->call('sendInvites')
            ->assertHasErrors('inviteRoles.0');
    }

    public function test_inviting_needs_someone_and_respects_the_limit(): void
    {
        $event = $this->createEvent(null);

        Livewire::test(EventList::class, ['guildId' => self::GUILD])
            ->call('startInvite', $event->id)
            ->call('sendInvites')
            ->assertHasErrors('invite');

        $component = Livewire::test(EventList::class, ['guildId' => self::GUILD])->call('startInvite', $event->id);
        foreach (range(1, 51) as $i) {
            $component->call('addUser', (string) (800000000000000100 + $i), "User {$i}");
        }
        $this->assertCount(50, $component->get('inviteUsers'));
        $this->assertSame([], $this->requests('POST', '/users/@me/channels'));
    }

    public function test_participants_are_listed_and_events_can_be_ended(): void
    {
        $event = $this->createEvent();
        GuildEvent::query()->whereKey($event->id)->update(['role_id' => '700000000000000002']);

        Livewire::test(EventList::class, ['guildId' => self::GUILD])
            ->assertSee(__('events.participants', ['count' => 3]))
            ->assertSee('Benny')
            ->call('end', $event->id)
            ->assertSee(__('events.ended', ['name' => 'Spieleabend']));

        $this->assertSame(0, GuildEvent::query()->count());
        $this->assertCount(4, $this->requests('DELETE', '/channels/'));
        $this->assertCount(1, $this->requests('DELETE', '/roles/700000000000000002'));
    }

    public function test_the_events_page_has_no_reset_button(): void
    {
        $this->get('/servers/'.self::GUILD.'/events')
            ->assertOk()
            ->assertSee(__('events.title'))
            ->assertDontSee(__('data.section_button'));

        Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => 'events'])
            ->call('resetSection')
            ->assertNotFound();
    }
}
