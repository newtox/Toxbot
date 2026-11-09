<?php

namespace Tests\Feature;

use App\Livewire\Leaderboard;
use App\Livewire\MyTags;
use App\Livewire\ProfileCredits;
use App\Models\BotUser;
use App\Models\CreditTransaction;
use App\Models\DashboardUser;
use App\Models\MemberLevel;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CreditsTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const USER = '123456789012345678';

    private const ANNA = '800000000000000001';

    private array $dms = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client', 'toxbot.currency' => 'Credits']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            if ($isBot && $request->method() === 'POST' && str_contains($url, '/channels/dm-')) {
                $this->dms[] = $request->data();
            }

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => '0'],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me/channels') => Http::response(['id' => 'dm-'.$request['recipient_id']]),
                str_contains($url, '/channels/dm-') => Http::response(['id' => 'msg']),
                str_contains($url, '/guilds/'.self::GUILD.'/members') => Http::response([
                    ['user' => ['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Newtox'], 'roles' => []],
                    ['user' => ['id' => self::ANNA, 'username' => 'anna', 'global_name' => 'Anna', 'avatar' => 'abc'], 'roles' => []],
                    ['user' => ['id' => '800000000000000009', 'username' => 'somebot', 'bot' => true], 'roles' => []],
                ]),
                str_contains($url, '/users/'.self::ANNA) => Http::response(['id' => self::ANNA, 'username' => 'anna', 'global_name' => 'Anna', 'avatar' => 'abc']),
                str_contains($url, '/users/900000000000000001') => Http::response(['id' => '900000000000000001', 'username' => 'stranger', 'global_name' => 'Fremder']),
                default => Http::response([], 404),
            };
        });

        $user = DashboardUser::create(['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    public function test_daily_reward_can_be_collected_once_a_day_and_builds_a_streak(): void
    {
        config(['toxbot.daily.reward' => 100, 'toxbot.daily.streak_bonus' => 10]);
        BotUser::forDiscordId(self::USER)->forceFill(['daily_at' => now()->subHours(30), 'daily_streak' => 3, 'credits' => 5])->save();

        Livewire::test(ProfileCredits::class)
            ->call('claim')
            ->assertSee(__('credits.daily_claimed', ['amount' => "130\u{00A0}Credits", 'streak' => 4]))
            ->assertDispatched('profile-changed')
            ->call('claim')
            ->assertSee(__('credits.hours_minutes', ['hours' => 23, 'minutes' => 59]));

        $this->assertSame(135, BotUser::query()->find(self::USER)->credits);
        $this->assertSame(1, CreditTransaction::query()->where('reason', 'daily')->count());
    }

    public function test_credits_can_be_sent_to_a_server_member(): void
    {
        BotUser::forDiscordId(self::USER)->forceFill(['credits' => 500])->save();
        BotUser::forDiscordId(self::ANNA)->forceFill(['credits' => 10, 'language' => 'de_de'])->save();

        Livewire::test(ProfileCredits::class)
            ->assertSet('pickGuild', self::GUILD)
            ->set('pickSearch', 'ann')
            ->assertSee('Anna')
            ->set('pickSearch', 'newt')
            ->assertDontSee('@newtox')
            ->set('pickSearch', 'some')
            ->assertDontSee('somebot')
            ->call('pickMember', self::ANNA)
            ->set('amount', '600')
            ->call('pay')
            ->assertHasErrors(['amount'])
            ->set('amount', '200')
            ->call('pay')
            ->assertHasNoErrors()
            ->assertSee(__('credits.paid', ['amount' => "200\u{00A0}Credits", 'name' => 'Anna']))
            ->assertSet('picked', []);

        $this->assertSame(300, BotUser::query()->find(self::USER)->credits);
        $this->assertSame(210, BotUser::query()->find(self::ANNA)->credits);
        $this->assertSame(['transfer_out' => -200, 'transfer_in' => 200], CreditTransaction::query()->pluck('amount', 'reason')->all());
        $this->assertSame(__('credits.dm_text', ['user' => 'Newtox', 'amount' => "200\u{00A0}Credits"], 'de'), $this->dms[0]['embeds'][0]['description']);
    }

    public function test_only_members_of_shared_servers_can_be_picked(): void
    {
        BotUser::forDiscordId(self::USER)->forceFill(['credits' => 500])->save();

        Livewire::test(ProfileCredits::class)
            ->call('pickMember', '900000000000000001')
            ->assertSet('picked', [])
            ->call('pickMember', self::USER)
            ->assertSet('picked', [])
            ->call('pickMember', '800000000000000009')
            ->assertSet('picked', [])
            ->set('picked', ['id' => '900000000000000001', 'name' => 'Fremder', 'username' => 'x', 'avatar' => '', 'guild' => self::GUILD])
            ->set('amount', '10')
            ->call('pay')
            ->assertHasErrors(['picked']);

        $this->assertSame(500, BotUser::query()->find(self::USER)->credits);
    }

    public function test_tags_can_be_handed_over(): void
    {
        $tag = Tag::create(['name' => 'faq', 'owner' => self::USER, 'content' => 'Hallo']);

        Livewire::test(MyTags::class)
            ->call('startTransfer', $tag->id)
            ->set('pickSearch', 'anna')
            ->call('pickMember', self::ANNA)
            ->call('transfer')
            ->assertHasNoErrors()
            ->assertSee(__('tags.transferred', ['name' => 'faq', 'user' => 'Anna']));

        $this->assertSame(self::ANNA, $tag->fresh()->owner);
    }

    public function test_tags_are_not_handed_to_someone_without_free_slots(): void
    {
        config(['toxbot.tags.free_limit' => 1]);
        Tag::create(['name' => 'hers', 'owner' => self::ANNA, 'content' => 'x']);
        $tag = Tag::create(['name' => 'faq', 'owner' => self::USER, 'content' => 'Hallo']);

        Livewire::test(MyTags::class)
            ->call('startTransfer', $tag->id)
            ->call('pickMember', self::ANNA)
            ->call('transfer')
            ->assertHasErrors(['picked']);

        $this->assertSame(self::USER, $tag->fresh()->owner);
    }

    public function test_leaderboard_shows_names_avatars_and_the_own_rank(): void
    {
        BotUser::forDiscordId(self::ANNA)->forceFill(['xp' => 900])->save();
        BotUser::forDiscordId('900000000000000001')->forceFill(['xp' => 500])->save();
        BotUser::forDiscordId(self::USER)->forceFill(['xp' => 100])->save();
        MemberLevel::query()->insert([
            ['guild' => self::GUILD, 'user' => self::USER, 'xp' => 50],
            ['guild' => '222222222222222222', 'user' => self::ANNA, 'xp' => 70],
        ]);

        $this->get(route('leaderboard'))->assertOk()->assertSee(__('leaderboard.title'));

        Livewire::test(Leaderboard::class)
            ->assertSeeInOrder(['Anna', 'Fremder', 'Newtox'])
            ->assertSee('https://cdn.discordapp.com/avatars/'.self::ANNA.'/abc.png?size=64', false)
            ->set('scope', self::GUILD)
            ->assertSee('Newtox')
            ->assertDontSee('Anna')
            ->set('scope', '222222222222222222')
            ->assertSet('scope', '');
    }

    public function test_guests_from_the_start_page_return_to_the_shop_after_login(): void
    {
        auth()->logout();

        $this->get(route('home'))->assertSee(route('login', ['to' => 'shop']), false);
        $this->get(route('login', ['to' => 'shop']))->assertRedirectContains('discord.com/oauth2/authorize');
        $this->assertSame(route('shop'), session('url.intended'));

        $this->get(route('login', ['to' => 'https://evil.example']));
        $this->assertSame(route('shop'), session('url.intended'));
    }

    public function test_logging_out_keeps_other_devices_signed_in(): void
    {
        $user = DashboardUser::query()->find(self::USER);
        $token = $user->remember_token;

        $this->post(route('logout'))->assertRedirect(route('home'));

        $user->refresh();
        $this->assertSame('token', $user->discord_access_token);
        $this->assertSame($token, $user->remember_token);
        $this->assertGuest();
    }
}
