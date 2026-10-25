<?php

namespace Tests\Feature;

use App\Livewire\GuildSettings;
use App\Livewire\MemberList;
use App\Livewire\MemberProfile;
use App\Livewire\StarboardList;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\ModAction;
use App\Models\ModCase;
use App\Models\StarboardPost;
use App\Services\DataDeletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class StarboardTest extends TestCase
{
    use RefreshDatabase;

    private const MANAGED = '111111111111111111';

    private const JOINED = '222222222222222222';

    private const BOT = '999999999999999999';

    private const USER = '123456789012345678';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');
            $member = fn (string $id, string $name) => ['user' => ['id' => $id, 'username' => strtolower($name), 'global_name' => $name, 'avatar' => null], 'roles' => ['20'], 'joined_at' => '2025-01-01T10:00:00+00:00', 'communication_disabled_until' => now()->addHour()->toIso8601String()];

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::MANAGED, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) 0x20],
                    ['id' => self::JOINED, 'name' => 'Valheim Crew', 'icon' => null, 'owner' => false, 'permissions' => '0'],
                ]),
                $isBot && $request->method() === 'POST' && str_contains($url, '/attachments/refresh-urls') => Http::response(['refreshed_urls' => [
                    ['original' => 'https://cdn.discordapp.com/attachments/1/2/cat.png?ex=1&is=1&hm=1', 'refreshed' => 'https://cdn.discordapp.com/attachments/1/2/cat.png?ex=ffffffff&is=1&hm=2'],
                ]]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::MANAGED], ['id' => self::JOINED]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => self::BOT, 'username' => 'Toxbot', 'avatar' => null]),
                $request->method() === 'DELETE' && str_contains($url, '/messages/') => Http::response(null, 204),
                str_contains($url, '/emojis') => Http::response([['id' => '777777777777777777', 'name' => 'pog', 'animated' => false, 'available' => true]]),
                str_contains($url, '/channels') => Http::response([
                    ['id' => '11', 'type' => 0, 'name' => 'allgemein', 'position' => 0],
                    ['id' => '15', 'type' => 0, 'name' => 'starboard', 'position' => 1],
                ]),
                str_contains($url, '/roles') => Http::response([
                    ['id' => '20', 'name' => 'Mitglied', 'position' => 1, 'color' => 0, 'managed' => false],
                ]),
                str_contains($url, '/members/'.self::BOT) => Http::response(['roles' => []]),
                str_contains($url, '/members/333') => Http::response($member('333', 'Kevin')),
                str_contains($url, '/members/') => Http::response([], 404),
                str_contains($url, '/bans/') => Http::response(['reason' => 'Spam'], 200),
                str_contains($url, '/users/444') => Http::response(['id' => '444', 'username' => 'gone', 'avatar' => null]),
                str_contains($url, '/members') => Http::response([$member('333', 'Kevin')]),
                default => Http::response([], 404),
            };
        });
    }

    private function login(): DashboardUser
    {
        $user = DashboardUser::create(['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'user-token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);

        return $user;
    }

    private function starPost(string $guild, string $message, int $stars, string $content, ?string $author = '333', ?string $image = null, int $daysAgo = 0): StarboardPost
    {
        return StarboardPost::create([
            'guild' => $guild,
            'channel_id' => '11',
            'message_id' => $message,
            'author_id' => $author,
            'author_name' => 'Kevin',
            'content' => $content,
            'image_url' => $image,
            'stars' => $stars,
            'starboard_channel_id' => '15',
            'starboard_message_id' => '9'.$message,
            'message_created_at' => now()->subDays($daysAgo),
        ]);
    }

    private function settings(string $section = 'starboard')
    {
        return Livewire::test(GuildSettings::class, ['guild' => ['id' => self::MANAGED, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => $section]);
    }

    public function test_saves_starboard_settings(): void
    {
        $this->login();

        $this->settings()
            ->assertSet('starboardEmojis', ['⭐'])
            ->assertSee('cdn.discordapp.com/emojis/777777777777777777.webp', false)
            ->set('starboardChannel', '15')
            ->set('starboardEmojis', ['⭐', 'nope'])
            ->set('starboardThreshold', 0)
            ->call('save')
            ->assertHasErrors(['starboardEmojis.1', 'starboardThreshold'])
            ->set('starboardEmojis', [])
            ->call('save')
            ->assertHasErrors(['starboardEmojis'])
            ->set('starboardEmojis', ['<:pog:777777777777777777>', '🔥', '🔥'])
            ->set('starboardThreshold', 5)
            ->call('save')
            ->assertHasNoErrors();

        $row = Guild::find(self::MANAGED);
        $this->assertSame('15', $row->starboard_channel);
        $this->assertSame('<:pog:777777777777777777> 🔥', $row->starboard_emoji);
        $this->assertSame(['<:pog:777777777777777777>', '🔥'], $row->starboardEmojis());
        $this->assertSame(5, $row->starboard_threshold);

        $this->settings()->set('starboardEmojis', ['⭐'])->call('save')->assertHasNoErrors();
        $this->assertNull(Guild::find(self::MANAGED)->starboard_emoji);

        $this->settings()->call('resetSection');
        $row = Guild::find(self::MANAGED);
        $this->assertNull($row->starboard_channel);
        $this->assertSame(3, $row->starboard_threshold);
    }

    public function test_managers_see_settings_and_posts(): void
    {
        $this->login();
        Guild::create(['id' => self::MANAGED, 'starboard_channel' => '15']);
        $this->starPost(self::MANAGED, '501', 4, 'Bester Witz ever');

        $this->get('/servers/'.self::MANAGED.'/starboard')
            ->assertOk()
            ->assertSee(__('starboard.threshold'))
            ->assertSee('Bester Witz ever');

        $this->get('/servers/'.self::MANAGED)->assertOk()->assertSee(__('starboard.settings_title'));
    }

    public function test_members_see_starboard_posts_sorted(): void
    {
        $this->login();
        Guild::create(['id' => self::JOINED, 'starboard_channel' => '15', 'starboard_threshold' => 2, 'starboard_emoji' => '⭐ 🔥']);
        $this->starPost(self::JOINED, '601', 3, 'Neu, wenige Sterne');
        $this->starPost(self::JOINED, '602', 12, 'Alt, viele Sterne', daysAgo: 5);
        $this->starPost(self::MANAGED, '603', 50, 'Anderer Server');

        $this->get('/servers/'.self::JOINED.'/starboard')
            ->assertOk()
            ->assertSee(__('starboard.member_description', ['count' => 2, 'emoji' => Blade::render('<x-emoji :code="$code" />', ['code' => '⭐']).' '.Blade::render('<x-emoji :code="$code" />', ['code' => '🔥'])]), false)
            ->assertSeeInOrder(['Neu, wenige Sterne', 'Alt, viele Sterne'])
            ->assertDontSee('Anderer Server')
            ->assertDontSee(__('starboard.threshold'));

        Livewire::test(StarboardList::class, ['guildId' => self::JOINED])
            ->set('sort', 'top')
            ->assertSeeInOrder(['Alt, viele Sterne', 'Neu, wenige Sterne'])
            ->assertSee('https://discord.com/channels/'.self::JOINED.'/11/602');
    }

    public function test_expired_images_are_refreshed(): void
    {
        $this->login();
        $post = $this->starPost(self::JOINED, '701', 3, 'Katze', image: 'https://cdn.discordapp.com/attachments/1/2/cat.png?ex=1&is=1&hm=1');

        Livewire::test(StarboardList::class, ['guildId' => self::JOINED])
            ->assertSee('cat.png?ex=ffffffff', false);

        $this->assertStringContainsString('ex=ffffffff', $post->fresh()->image_url);
    }

    public function test_members_see_members_without_moderation(): void
    {
        $this->login();
        $case = new ModCase;
        $case->forceFill(['guild' => self::JOINED, 'number' => 1, 'type' => 'warn', 'user_id' => '333', 'moderator_id' => '1', 'reason' => 'Geheimer Grund', 'active' => true])->save();

        $this->get('/servers/'.self::JOINED)->assertRedirect(route('servers.members', self::JOINED));

        Livewire::test(MemberList::class, ['guildId' => self::JOINED])
            ->assertSee('Kevin')
            ->assertDontSee(__('moderation.filter_cases'))
            ->assertDontSee(__('moderation.timed_out'))
            ->assertDontSee(trans_choice('moderation.cases_count', 1))
            ->set('filter', 'cases')
            ->assertSet('filter', 'all');

        Livewire::test(MemberProfile::class, ['guildId' => self::JOINED, 'userId' => '333'])
            ->assertSee('Kevin')
            ->assertSee('Mitglied')
            ->assertDontSee('Geheimer Grund')
            ->assertDontSee(__('moderation.action_title'))
            ->call('submit')
            ->assertForbidden();

        $this->assertSame(0, ModAction::count());

        $this->get('/servers/'.self::JOINED.'/members/444')->assertNotFound();

        Livewire::test(MemberList::class, ['guildId' => self::MANAGED])
            ->assertSee(__('moderation.filter_cases'))
            ->assertSee(__('moderation.timed_out'));
    }

    public function test_deleting_data_removes_starboard_posts(): void
    {
        $user = $this->login();
        $this->starPost(self::MANAGED, '801', 5, 'Von mir', author: self::USER);
        $this->starPost(self::MANAGED, '802', 5, 'Von Kevin');
        $this->starPost(self::JOINED, '803', 5, 'Anderswo');

        app(DataDeletion::class)->deleteUser($user);
        $this->assertSame(['802', '803'], StarboardPost::query()->orderBy('message_id')->pluck('message_id')->all());
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/channels/15/messages/9801'));

        app(DataDeletion::class)->deleteGuild(self::MANAGED);
        $this->assertSame(['803'], StarboardPost::query()->pluck('message_id')->all());
    }
}
