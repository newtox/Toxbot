<?php

namespace Tests\Feature;

use App\Livewire\GuildSettings;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Services\ReadOnlyChannels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ReadOnlyChannelsTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const USER = '123456789012345678';

    private const SEND = 0x800;

    private const REACT = 0x40;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                $request->method() === 'PUT' && str_contains($url, '/permissions/') => str_contains($url, '/channels/44/')
                    ? Http::response(['message' => 'Missing Permissions'], 403)
                    : Http::response(null, 204),
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) 0x20],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => '999', 'username' => 'Toxbot', 'avatar' => null]),
                str_contains($url, '/members/999') => Http::response(['roles' => []]),
                str_contains($url, '/channels') => Http::response($this->channels()),
                str_contains($url, '/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0, 'managed' => false],
                ]),
                str_ends_with($url, '/guilds/'.self::GUILD) || str_contains($url, '/guilds/'.self::GUILD.'?') => Http::response(['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null]),
                default => Http::response([], 404),
            };
        });
    }

    private function channels(): array
    {
        $everyone = fn (int $allow, int $deny) => [['id' => self::GUILD, 'type' => 0, 'allow' => (string) $allow, 'deny' => (string) $deny]];

        return [
            ['id' => '40', 'type' => 4, 'name' => 'Info', 'position' => 0, 'permission_overwrites' => $everyone(0, self::SEND)],
            ['id' => '41', 'type' => 0, 'name' => 'regeln', 'position' => 1, 'permission_overwrites' => $everyone(0x400, self::SEND | self::REACT)],
            ['id' => '42', 'type' => 0, 'name' => 'allgemein', 'position' => 2, 'permission_overwrites' => []],
            ['id' => '43', 'type' => 5, 'name' => 'news', 'position' => 3, 'permission_overwrites' => $everyone(1 << 35, self::SEND)],
            ['id' => '45', 'type' => 0, 'name' => 'tickets', 'position' => 4, 'permission_overwrites' => $everyone(0, self::SEND | ReadOnlyChannels::THREADS)],
            ['id' => '44', 'type' => 0, 'name' => 'kaputt', 'position' => 5, 'permission_overwrites' => $everyone(0, self::SEND)],
            ['id' => '46', 'type' => 2, 'name' => 'Voice', 'position' => 6, 'permission_overwrites' => $everyone(0, self::SEND)],
        ];
    }

    private function login(): void
    {
        $user = DashboardUser::create(['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'user-token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    private function puts(): array
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => $pair[0]->method() === 'PUT')
            ->mapWithKeys(fn ($pair) => [explode('/', explode('/channels/', $pair[0]->url())[1])[0] => $pair[0]->data()])
            ->all();
    }

    public function test_lists_read_only_channels_with_their_thread_state(): void
    {
        $list = collect(app(ReadOnlyChannels::class)->list(self::GUILD))->pluck('state', 'name')->all();

        $this->assertSame(['Info' => 'open', 'regeln' => 'open', 'news' => 'allowed', 'tickets' => 'locked', 'kaputt' => 'open'], $list);
    }

    public function test_saving_blocks_threads_and_keeps_the_other_permissions(): void
    {
        $this->login();

        Livewire::test(GuildSettings::class, [
            'guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null],
            'section' => 'readonly',
        ])
            ->assertSee('regeln')
            ->assertSee(__('protection.readonly_state_open'))
            ->set('readonlyThreads', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('readonlyNotice', trans_choice('protection.readonly_failed', 1, ['count' => 1]));

        $this->assertTrue(Guild::query()->find(self::GUILD)->readonly_threads);

        $puts = $this->puts();
        $this->assertSame([40, 41, 44], array_keys($puts));
        $this->assertSame((string) 0x400, $puts['41']['allow']);
        $this->assertSame((string) (self::SEND | self::REACT | ReadOnlyChannels::THREADS), $puts['41']['deny']);
        $this->assertSame((string) (self::SEND | ReadOnlyChannels::THREADS), $puts['40']['deny']);
    }

    public function test_saving_while_off_changes_nothing(): void
    {
        $this->login();

        Livewire::test(GuildSettings::class, [
            'guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null],
            'section' => 'readonly',
        ])->call('save')->assertSet('readonlyNotice', null);

        $this->assertSame([], $this->puts());
    }

    public function test_page_and_overview_card(): void
    {
        $this->login();

        $this->get('/servers/'.self::GUILD.'/readonly')->assertOk()->assertSee(__('protection.readonly_title'))->assertSee(__('protection.readonly_hint'));
        $this->get('/servers/'.self::GUILD)->assertOk()->assertSee(__('protection.readonly_title'));
    }
}
