<?php

namespace Tests\Feature;

use App\Livewire\ServerTags;
use App\Models\DashboardUser;
use App\Models\ServerTag;
use App\Models\Tag;
use App\Services\DataDeletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ServerTagsTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private int $permissions = 0x20;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function ($request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) $this->permissions],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                default => Http::response([], 404),
            };
        });

        $user = DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);
    }

    public function test_admins_create_edit_and_delete_server_tags(): void
    {
        Livewire::test(ServerTags::class, ['guildId' => self::GUILD])
            ->set('name', ' Rules ')
            ->set('content', 'Read #rules first, &user.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('rules');

        $tag = ServerTag::query()->sole();
        $this->assertSame(['rules', 'Read #rules first, &user.', '123456789012345678'], [$tag->name, $tag->content, $tag->created_by]);

        Livewire::test(ServerTags::class, ['guildId' => self::GUILD])
            ->call('edit', $tag->id)
            ->set('content', 'Read the rules.')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('Read the rules.', $tag->fresh()->content);

        Livewire::test(ServerTags::class, ['guildId' => self::GUILD])->call('remove', $tag->id);
        $this->assertSame(0, ServerTag::query()->count());
    }

    public function test_names_are_unique_per_server_but_may_match_global_tags(): void
    {
        Tag::create(['name' => 'faq', 'owner' => '5', 'content' => 'global']);
        ServerTag::create(['guild' => '222222222222222222', 'name' => 'rules', 'content' => 'other server']);
        ServerTag::create(['guild' => self::GUILD, 'name' => 'links', 'content' => 'x']);

        Livewire::test(ServerTags::class, ['guildId' => self::GUILD])
            ->set('name', 'faq')->set('content', 'ours')->call('save')->assertHasNoErrors()
            ->set('name', 'rules')->set('content', 'ours')->call('save')->assertHasNoErrors()
            ->set('name', 'links')->set('content', 'again')->call('save')->assertHasErrors('name')
            ->set('name', 'Not valid!')->set('content', 'x')->call('save')->assertHasErrors('name')
            ->set('name', 'empty')->set('content', '  ')->call('save')->assertHasErrors('content');

        $this->assertSame(3, ServerTag::query()->where('guild', self::GUILD)->count());
    }

    public function test_limit_per_server(): void
    {
        foreach (range(1, ServerTag::FREE_LIMIT) as $i) {
            ServerTag::create(['guild' => self::GUILD, 'name' => "tag{$i}", 'content' => 'x']);
        }

        Livewire::test(ServerTags::class, ['guildId' => self::GUILD])
            ->assertSee(__('guild.server_tags_premium', ['max' => ServerTag::PREMIUM_LIMIT]))
            ->set('name', 'one-more')->set('content', 'x')->call('save')
            ->assertHasErrors('name');

        config(['toxbot.premium_guilds' => [self::GUILD]]);

        Livewire::test(ServerTags::class, ['guildId' => self::GUILD])
            ->assertDontSee(__('guild.server_tags_premium', ['max' => ServerTag::PREMIUM_LIMIT]))
            ->set('name', 'one-more')->set('content', 'x')->call('save')
            ->assertHasNoErrors();
        $this->assertSame(ServerTag::FREE_LIMIT + 1, ServerTag::query()->count());
    }

    public function test_members_without_manage_server_cannot_change_them(): void
    {
        $this->permissions = 0x2;
        $tag = ServerTag::create(['guild' => self::GUILD, 'name' => 'rules', 'content' => 'x']);

        Livewire::test(ServerTags::class, ['guildId' => self::GUILD])
            ->set('name', 'new')->set('content', 'x')->call('save')->assertForbidden();
        Livewire::test(ServerTags::class, ['guildId' => self::GUILD])->call('remove', $tag->id)->assertForbidden();
        $this->assertSame(1, ServerTag::query()->count());
    }

    public function test_settings_page_shows_the_server_tags(): void
    {
        ServerTag::create(['guild' => self::GUILD, 'name' => 'rules', 'content' => 'Be nice.']);

        $this->get(route('servers.settings', [self::GUILD, 'tags']))
            ->assertOk()
            ->assertSee(__('guild.server_tags_title'))
            ->assertSee('Be nice.');
    }

    public function test_resetting_the_tags_page_or_the_server_deletes_them(): void
    {
        ServerTag::create(['guild' => self::GUILD, 'name' => 'rules', 'content' => 'x']);
        ServerTag::create(['guild' => '222222222222222222', 'name' => 'rules', 'content' => 'x']);

        app(DataDeletion::class)->resetSection(self::GUILD, 'tags');
        $this->assertSame(['222222222222222222'], ServerTag::query()->pluck('guild')->all());

        app(DataDeletion::class)->resetGuild('222222222222222222');
        $this->assertSame(0, ServerTag::query()->count());
    }
}
