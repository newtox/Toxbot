<?php

namespace Tests\Feature;

use App\Livewire\GuildSettings;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Services\DataDeletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AutoModTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private array $rules = [];

    private int $nextId = 500;

    private bool $forbidden = false;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            if (str_contains($url, '/auto-moderation/rules')) {
                return $this->rule($request);
            }

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) 0x20],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => '999', 'username' => 'Toxbot', 'avatar' => null]),
                str_contains($url, '/channels') => Http::response([
                    ['id' => '11', 'type' => 0, 'name' => 'mod-log', 'position' => 0],
                ]),
                str_contains($url, '/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0, 'managed' => false],
                    ['id' => '20', 'name' => 'Team', 'position' => 1, 'color' => 0, 'managed' => false],
                    ['id' => '21', 'name' => 'Toxbot', 'position' => 2, 'color' => 0, 'managed' => true],
                ]),
                str_contains($url, '/members/') => Http::response(['roles' => ['21']]),
                default => Http::response([], 404),
            };
        });

        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox']);
        $user->forceFill(['discord_access_token' => 'user-token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    private function rule(Request $request)
    {
        if ($this->forbidden) {
            return Http::response(['message' => 'Missing Permissions', 'code' => 50013], 403);
        }

        $id = last(explode('/', parse_url($request->url(), PHP_URL_PATH)));

        return match ($request->method()) {
            'GET' => Http::response(array_values(array_map(fn ($rule, $id) => ['id' => (string) $id] + $rule, $this->rules, array_keys($this->rules)))),
            'POST' => (function () use ($request) {
                $id = (string) $this->nextId++;
                $this->rules[$id] = $request->data() + ['creator_id' => 'client'];

                return Http::response(['id' => $id] + $request->data());
            })(),
            'PATCH' => isset($this->rules[$id])
                ? (function () use ($id, $request) {
                    $this->rules[$id] = $request->data() + $this->rules[$id];

                    return Http::response(['id' => $id] + $this->rules[$id]);
                })()
                : Http::response(['message' => 'Unknown Auto Moderation Rule'], 404),
            'DELETE' => (function () use ($id) {
                unset($this->rules[$id]);

                return Http::response(null, 204);
            })(),
            default => Http::response([], 404),
        };
    }

    private function settings(string $section)
    {
        return Livewire::test(GuildSettings::class, ['guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null], 'section' => $section]);
    }

    public function test_rules_are_created_updated_and_removed_in_discord(): void
    {
        Guild::forDiscordId(self::GUILD)->fill(['modlog_channel' => '11'])->save();

        $this->settings('automod')
            ->set('automodWords', "Badword\n*spam*\nbadword")
            ->set('automodMentions', 5)
            ->set('automodExemptRoles', ['20'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('automodNotice', null);

        $row = Guild::find(self::GUILD);
        $this->assertCount(2, $this->rules);
        $words = $this->rules[$row->automod_rule_words];
        $this->assertSame(1, $words['trigger_type']);
        $this->assertSame(1, $words['event_type']);
        $this->assertSame(['badword', '*spam*'], $words['trigger_metadata']['keyword_filter']);
        $this->assertSame(['20'], $words['exempt_roles']);
        $this->assertSame([1, 2], array_column($words['actions'], 'type'));
        $this->assertSame('11', $words['actions'][1]['metadata']['channel_id']);
        $this->assertSame(5, $this->rules[$row->automod_rule_mentions]['trigger_metadata']['mention_total_limit']);

        $this->settings('links')->set('linkBlocklist', "example.com\nbad.example")->call('save')->assertHasNoErrors();
        $row->refresh();
        $this->assertSame(['*example.com*', '*bad.example*'], $this->rules[$row->automod_rule_domains]['trigger_metadata']['keyword_filter']);

        $wordsId = $row->automod_rule_words;
        $this->settings('automod')->set('automodWords', 'other')->set('automodMentions', 0)->call('save');
        $row->refresh();
        $this->assertSame($wordsId, $row->automod_rule_words);
        $this->assertSame(['other'], $this->rules[$wordsId]['trigger_metadata']['keyword_filter']);
        $this->assertNull($row->automod_rule_mentions);
        $this->assertCount(2, $this->rules);

        app(DataDeletion::class)->resetSection(self::GUILD, 'automod');
        $this->assertNull(Guild::find(self::GUILD)->automod_rule_words);
        $this->assertCount(1, $this->rules);

        app(DataDeletion::class)->resetGuild(self::GUILD);
        $this->assertSame([], $this->rules);
    }

    public function test_rules_deleted_in_discord_are_created_again(): void
    {
        $this->settings('automod')->set('automodWords', 'one')->call('save');
        $this->rules = [];

        $this->settings('automod')->set('automodWords', 'two')->call('save');

        $this->assertCount(1, $this->rules);
        $this->assertSame(['two'], $this->rules[Guild::find(self::GUILD)->automod_rule_words]['trigger_metadata']['keyword_filter']);
    }

    public function test_input_is_validated(): void
    {
        $this->settings('automod')
            ->set('automodWords', str_repeat('a', 61))->call('save')->assertHasErrors('automodWords')
            ->set('automodWords', 'ok')->set('automodMentions', 2)->call('save')->assertHasErrors('automodMentions')
            ->set('automodMentions', 51)->call('save')->assertHasErrors('automodMentions')
            ->set('automodMentions', 3)->set('automodExemptRoles', ['nope'])->call('save')->assertHasErrors('automodExemptRoles.0');

        $this->assertSame([], $this->rules);
    }

    public function test_missing_permission_is_explained(): void
    {
        $this->forbidden = true;

        $this->settings('automod')->set('automodWords', 'bad')->call('save')
            ->assertHasNoErrors()
            ->assertSet('automodNotice', __('automod.failed_permission', ['rules' => __('automod.rule_words')]));

        $row = Guild::find(self::GUILD);
        $this->assertSame('bad', $row->automod_words);
        $this->assertNull($row->automod_rule_words);
    }

    private function existing(string $name, int $trigger, string $creator = 'client'): string
    {
        $id = (string) $this->nextId++;
        $this->rules[$id] = ['name' => $name, 'trigger_type' => $trigger, 'creator_id' => $creator, 'trigger_metadata' => []];

        return $id;
    }

    public function test_existing_toxbot_rules_are_taken_over(): void
    {
        $words = $this->existing(__('automod.rule_words', [], 'en'), 1);
        $duplicate = $this->existing(__('automod.rule_words', [], 'de'), 1);
        $mentions = $this->existing('Toxbot Mention Spam', 5);
        $this->existing('Toxbot old filter', 1);
        $foreign = $this->existing('Their own filter', 1, '42');

        $this->settings('automod')->set('automodWords', 'bad')->set('automodMentions', 10)->call('save')
            ->assertSet('automodNotice', null);

        $row = Guild::find(self::GUILD);
        $this->assertContains($row->automod_rule_words, [$words, $duplicate]);
        $this->assertSame($mentions, $row->automod_rule_mentions);
        $this->assertCount(3, $this->rules);
        $this->assertArrayHasKey($foreign, $this->rules);
        $this->assertSame(['bad'], $this->rules[$row->automod_rule_words]['trigger_metadata']['keyword_filter']);
        $this->assertSame(10, $this->rules[$mentions]['trigger_metadata']['mention_total_limit']);
    }

    public function test_a_foreign_mention_rule_is_explained(): void
    {
        $this->existing('Server mention filter', 5, '42');

        $this->settings('automod')->set('automodMentions', 10)->call('save')
            ->assertSet('automodNotice', __('automod.failed_mention_taken', ['rules' => __('automod.rule_mentions')]));

        $this->assertNull(Guild::find(self::GUILD)->automod_rule_mentions);
        $this->assertCount(1, $this->rules);
    }

    public function test_reset_also_removes_rules_the_database_lost(): void
    {
        $this->existing(__('automod.rule_words', [], 'en'), 1);
        $foreign = $this->existing('Their own filter', 1, '42');
        Guild::forDiscordId(self::GUILD)->fill(['automod_words' => 'x'])->save();

        app(DataDeletion::class)->resetGuild(self::GUILD);

        $this->assertSame([$foreign], array_map('strval', array_keys($this->rules)));
    }
}
