<?php

namespace Tests\Feature;

use App\Livewire\GuildSettings;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const BOT = '999999999999999999';

    private const USER = '123456789012345678';

    private array $memberRoles = ['30'];

    private bool $isMember = true;

    private bool $captcha = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                str_contains($url, 'challenges.cloudflare.com') => Http::response(['success' => $this->captcha]),
                str_contains($url, 'Discord-AntiScam') => Http::response("free-nitro.example\nsteam-gift.example\n"),
                str_contains($url, 'discord-phishing-links') => Http::response("old-scam.example\n"),
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) 0x20],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => self::BOT, 'username' => 'Toxbot', 'avatar' => null]),
                str_contains($url, '/roles/') => Http::response(null, 204),
                str_contains($url, '/messages/') => Http::response(['id' => '700']),
                str_contains($url, '/messages') => Http::response(['id' => '700']),
                str_contains($url, '/channels') => Http::response([
                    ['id' => '11', 'type' => 0, 'name' => 'verify', 'position' => 0],
                ]),
                str_contains($url, '/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0, 'managed' => false],
                    ['id' => '30', 'name' => 'Unverifiziert', 'position' => 1, 'color' => 0, 'managed' => false],
                    ['id' => '31', 'name' => 'Mitglied', 'position' => 2, 'color' => 0, 'managed' => false],
                    ['id' => '21', 'name' => 'Toxbot', 'position' => 3, 'color' => 0, 'managed' => true],
                ]),
                str_contains($url, '/members/'.self::BOT) => Http::response(['roles' => ['21']]),
                str_contains($url, '/members/'.self::USER) => $this->isMember
                    ? Http::response(['roles' => $this->memberRoles])
                    : Http::response(['message' => 'Unknown Member'], 404),
                str_ends_with($url, '/guilds/'.self::GUILD) || str_contains($url, '/guilds/'.self::GUILD.'?') => Http::response(['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null]),
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

    private function settings()
    {
        return Livewire::test(GuildSettings::class, [
            'guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null],
            'section' => 'protection',
        ]);
    }

    public function test_section_page_and_overview(): void
    {
        $this->login();

        $this->get('/servers/'.self::GUILD.'/protection')
            ->assertOk()
            ->assertSee(__('protection.verify_title'))
            ->assertSee(__('protection.links_title'))
            ->assertSee(__('protection.raid_title'));

        $this->get('/servers/'.self::GUILD)
            ->assertOk()
            ->assertSee(__('protection.links_title'));
    }

    public function test_saves_protection_settings_and_normalizes_domains(): void
    {
        $this->login();

        $this->settings()
            ->set('verifyEnabled', true)
            ->set('verifyRole', '30')
            ->set('verifyChannel', '11')
            ->set('linkInvites', true)
            ->set('linkScam', true)
            ->set('linkBlocklist', "https://www.Example.com/path\n*.evil.ru, example.com")
            ->set('linkAllowlistEnabled', true)
            ->set('linkAllowlist', 'youtube.com twitch.tv')
            ->set('linkAction', 'timeout')
            ->set('raidEnabled', true)
            ->set('raidJoins', 8)
            ->set('raidSeconds', 15)
            ->set('raidMinutes', 30)
            ->set('raidKick', false)
            ->set('minAccountAge', 7)
            ->call('save')
            ->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertTrue($row->verify_enabled);
        $this->assertSame('30', $row->verify_role);
        $this->assertSame("example.com\nevil.ru", $row->link_blocklist);
        $this->assertSame("youtube.com\ntwitch.tv", $row->link_allowlist);
        $this->assertSame('timeout', $row->link_action);
        $this->assertSame(8, $row->raid_joins);
        $this->assertFalse($row->raid_kick);
        $this->assertSame(7, $row->min_account_age);
    }

    public function test_saves_roles_without_link_filter(): void
    {
        $this->login();

        $this->settings()
            ->set('linkExemptRoles', ['31', '30'])
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('31,30', Guild::find(self::GUILD)->link_exempt_roles);

        $this->settings()
            ->assertSet('linkExemptRoles', ['31', '30'])
            ->set('linkExemptRoles', ['999'])
            ->call('save')
            ->assertHasErrors(['linkExemptRoles.0']);
    }

    public function test_checks_a_domain_against_both_scam_lists(): void
    {
        $this->login();

        $this->settings()
            ->set('scamCheck', 'https://login.free-nitro.example/claim')
            ->call('checkScamDomain')
            ->assertSet('scamCheckResult', __('protection.scam_check_listed', ['domain' => 'free-nitro.example']))
            ->set('scamCheck', 'old-scam.example')
            ->call('checkScamDomain')
            ->assertSet('scamCheckResult', __('protection.scam_check_listed', ['domain' => 'old-scam.example']))
            ->set('scamCheck', 'youtube.com')
            ->call('checkScamDomain')
            ->assertSet('scamCheckResult', __('protection.scam_check_clean', ['domain' => 'youtube.com']))
            ->set('scamCheck', 'nope')
            ->call('checkScamDomain')
            ->assertSet('scamCheckResult', __('protection.scam_check_invalid'));
    }

    public function test_rejects_missing_role_bad_domains_and_numbers(): void
    {
        $this->login();

        $this->settings()
            ->set('verifyEnabled', true)
            ->set('verifyRole', null)
            ->set('linkBlocklist', 'not_a_domain')
            ->set('linkAction', 'ban')
            ->set('raidJoins', 1)
            ->set('minAccountAge', 999)
            ->call('save')
            ->assertHasErrors(['verifyRole', 'linkBlocklist', 'linkAction', 'raidJoins', 'minAccountAge']);

        $this->assertNull(Guild::find(self::GUILD));
    }

    public function test_posts_and_then_updates_the_verification_message(): void
    {
        $this->login();
        Guild::create(['id' => self::GUILD, 'verify_enabled' => true, 'verify_role' => '30', 'verify_channel' => '11']);

        $this->settings()->call('postVerifyMessage')->assertSet('protectionNotice', __('protection.post_done'));
        $this->assertSame('700', Guild::find(self::GUILD)->verify_message_id);

        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && str_ends_with($r->url(), '/channels/11/messages')
            && $r['components'][0]['components'][0]['url'] === route('verify.show', self::GUILD));

        $this->settings()->call('postVerifyMessage');
        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && str_ends_with($r->url(), '/channels/11/messages/700'));
    }

    public function test_post_needs_saved_settings(): void
    {
        $this->login();

        $this->settings()->call('postVerifyMessage')->assertSet('protectionNotice', __('protection.post_needs_settings'));
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/messages'));
    }

    public function test_ending_a_raid(): void
    {
        $this->login();
        Guild::create(['id' => self::GUILD, 'raid_enabled' => true, 'raid_until' => now()->addMinutes(5)]);

        $this->settings()
            ->assertSet('raidUntil', fn ($value) => $value !== null)
            ->call('endRaid')
            ->assertSet('raidUntil', null);

        $this->assertFalse(Guild::find(self::GUILD)->raidActive());
    }

    public function test_guests_are_sent_to_the_discord_login(): void
    {
        $this->get('/verify/'.self::GUILD)->assertRedirect(route('login'));
    }

    public function test_verification_page_states(): void
    {
        $this->login();

        $this->get('/verify/'.self::GUILD)->assertOk()->assertSee(__('protection.verify_disabled'));

        Guild::create(['id' => self::GUILD, 'verify_enabled' => true, 'verify_role' => '30']);
        $this->get('/verify/'.self::GUILD)->assertOk()->assertSee(__('protection.verify_button'))->assertSee('Toxic Lounge');

        $this->memberRoles = ['31'];
        $this->get('/verify/'.self::GUILD)->assertOk()->assertSee(__('protection.verify_already', ['server' => 'Toxic Lounge']));

        $this->isMember = false;
        $this->get('/verify/'.self::GUILD)->assertOk()->assertSee(__('protection.verify_not_member', ['server' => 'Toxic Lounge']));
    }

    public function test_verifying_removes_the_role_and_gives_the_autorole(): void
    {
        config(['toxbot.turnstile.site_key' => 'site', 'toxbot.turnstile.secret_key' => 'secret']);
        $this->login();
        Guild::create(['id' => self::GUILD, 'verify_enabled' => true, 'verify_role' => '30', 'autorole' => '31']);

        $this->get('/verify/'.self::GUILD)->assertSee('data-sitekey="site"', false);

        $this->post('/verify/'.self::GUILD, ['cf-turnstile-response' => 'token'])
            ->assertOk()
            ->assertSee(__('protection.verify_done', ['server' => 'Toxic Lounge']));

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/members/'.self::USER.'/roles/30'));
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), '/members/'.self::USER.'/roles/31'));
        $this->assertNotNull(Verification::query()->where('guild', self::GUILD)->where('user_id', self::USER)->first()?->verified_at);
    }

    public function test_failed_captcha_keeps_the_role(): void
    {
        config(['toxbot.turnstile.site_key' => 'site', 'toxbot.turnstile.secret_key' => 'secret']);
        $this->captcha = false;
        $this->login();
        Guild::create(['id' => self::GUILD, 'verify_enabled' => true, 'verify_role' => '30']);

        $this->post('/verify/'.self::GUILD, ['cf-turnstile-response' => 'token'])
            ->assertOk()
            ->assertSee(__('protection.verify_captcha_failed'));

        Http::assertNotSent(fn (Request $r) => $r->method() === 'DELETE');
        $this->assertSame(0, Verification::query()->count());
    }
}
