<?php

namespace Tests\Feature;

use App\Livewire\BotProfile;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Services\DataDeletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BotProfileTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private array $patches = [];

    private int $patchStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => '32'],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/members/@me') && $request->method() === 'PATCH' => $this->recordPatch($request),
                $isBot && str_contains($url, '/guilds/'.self::GUILD.'/members/999') => Http::response([
                    'user' => ['id' => '999', 'username' => 'Toxbot'], 'nick' => null, 'avatar' => 'abc', 'banner' => null,
                ]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => '999', 'username' => 'Toxbot', 'avatar' => 'global']),
                default => Http::response([], 404),
            };
        });

        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    private function recordPatch(Request $request)
    {
        $this->patches[] = json_decode($request->body(), true);

        return Http::response(['user' => ['id' => '999']], $this->patchStatus);
    }

    public function test_only_premium_servers_can_change_the_bot_profile(): void
    {
        Livewire::test(BotProfile::class, ['guildId' => self::GUILD])
            ->assertSee(__('botprofile.locked'))
            ->set('bio', 'Hallo')
            ->call('save')
            ->assertForbidden();

        $this->assertSame([], $this->patches);
    }

    public function test_premium_servers_set_avatar_banner_and_bio(): void
    {
        config(['toxbot.premium_guilds' => [self::GUILD]]);

        Livewire::test(BotProfile::class, ['guildId' => self::GUILD])
            ->assertDontSee(__('botprofile.locked'))
            ->assertSee('/guilds/'.self::GUILD.'/users/999/avatars/abc.png', false)
            ->set('avatar', UploadedFile::fake()->image('avatar.png', 128, 128))
            ->set('banner', UploadedFile::fake()->image('banner.jpg', 680, 240))
            ->set('bio', '  Ich passe auf euch auf.  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('avatar', null);

        $this->assertStringStartsWith('data:image/png;base64,', $this->patches[0]['avatar']);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $this->patches[0]['banner']);
        $this->assertSame('Ich passe auf euch auf.', $this->patches[0]['bio']);

        $guild = Guild::find(self::GUILD);
        $this->assertTrue($guild->bot_profile);
        $this->assertSame('Ich passe auf euch auf.', $guild->bot_bio);

        Livewire::test(BotProfile::class, ['guildId' => self::GUILD])
            ->assertSet('bio', 'Ich passe auf euch auf.')
            ->call('remove', 'avatar');
        $this->assertSame(['avatar' => null], $this->patches[1]);

        Livewire::test(BotProfile::class, ['guildId' => self::GUILD])
            ->set('bio', str_repeat('a', 191))->call('save')->assertHasErrors('bio')
            ->set('bio', '')->set('avatar', UploadedFile::fake()->create('a.txt', 1, 'text/plain'))->call('save')->assertHasErrors('avatar');
        $this->assertCount(2, $this->patches);
    }

    public function test_discord_errors_are_shown(): void
    {
        config(['toxbot.premium_guilds' => [self::GUILD]]);
        $this->patchStatus = 429;

        Livewire::test(BotProfile::class, ['guildId' => self::GUILD])
            ->set('bio', 'Hallo')->call('save')
            ->assertHasErrors('profile')
            ->assertSee(__('botprofile.rate_limited'));

        $this->assertFalse((bool) Guild::find(self::GUILD)?->bot_profile);
    }

    public function test_profile_is_reset_when_premium_ends_or_the_section_is_reset(): void
    {
        Guild::create(['id' => self::GUILD, 'bot_profile' => true, 'bot_bio' => 'Hallo']);
        Guild::create(['id' => '222', 'bot_profile' => false]);

        config(['toxbot.premium_guilds' => [self::GUILD]]);
        Artisan::call('premium:bot-profiles');
        $this->assertSame([], $this->patches);

        config(['toxbot.premium_guilds' => []]);
        Artisan::call('premium:bot-profiles');
        $this->assertSame([['avatar' => null, 'banner' => null, 'bio' => null]], $this->patches);
        $guild = Guild::find(self::GUILD);
        $this->assertFalse($guild->bot_profile);
        $this->assertNull($guild->bot_bio);

        $guild->update(['bot_profile' => true, 'bot_bio' => 'Wieder da']);
        app(DataDeletion::class)->resetSection(self::GUILD, 'botprofile');
        $this->assertCount(2, $this->patches);
        $this->assertFalse(Guild::find(self::GUILD)->bot_profile);
    }

    public function test_settings_page_shows_the_section(): void
    {
        $this->get('/servers/'.self::GUILD.'/botprofile')
            ->assertOk()
            ->assertSee(__('botprofile.title'))
            ->assertDontSee(__('guild.reload'));
    }
}
