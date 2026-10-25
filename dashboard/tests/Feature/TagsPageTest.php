<?php

namespace Tests\Feature;

use App\Livewire\MyTags;
use App\Models\DashboardUser;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TagsPageTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '123456789012345678';

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(DashboardUser::create(['id' => (int) self::USER, 'username' => 'newtox']));
    }

    public function test_creates_a_tag(): void
    {
        Livewire::test(MyTags::class)
            ->call('create')
            ->set('name', 'NewTox')
            ->set('content', 'owo')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tags', ['name' => 'newtox', 'owner' => self::USER, 'content' => 'owo']);
        $this->get('/tags')->assertOk()->assertSee('newtox');
    }

    public function test_rejects_taken_names_invites_and_mass_mentions(): void
    {
        Tag::create(['name' => 'taken', 'owner' => '1', 'content' => 'x']);

        Livewire::test(MyTags::class)->call('create')->set('name', 'taken')->set('content', 'x')
            ->call('save')->assertHasErrors('name');
        Livewire::test(MyTags::class)->call('create')->set('name', 'invite')->set('content', 'join discord.gg/abc')
            ->call('save')->assertHasErrors('content');
        Livewire::test(MyTags::class)->call('create')->set('name', 'ping')->set('content', 'hey @everyone')
            ->call('save')->assertHasErrors('content');
        foreach (['hi &mention', 'hi <@123456789012345678>', 'hi <@&123456789012345678>', 'hi <@!123456789012345678>'] as $content) {
            Livewire::test(MyTags::class)->call('create')->set('name', 'ping')->set('content', $content)
                ->call('save')->assertHasErrors('content');
        }
        Livewire::test(MyTags::class)->call('create')->set('name', 'hello')->set('content', 'Hi &user auf &server in <#123456789012345678>')
            ->call('save')->assertHasNoErrors();
        Livewire::test(MyTags::class)->call('create')->set('name', 'bad name!')->set('content', 'x')
            ->call('save')->assertHasErrors('name');
    }

    public function test_respects_the_free_limit(): void
    {
        config(['toxbot.tags.free_limit' => 1]);
        Tag::create(['name' => 'first', 'owner' => self::USER, 'content' => 'x']);

        Livewire::test(MyTags::class)->call('create')->set('name', 'second')->set('content', 'x')
            ->call('save')->assertHasErrors('name');
        $this->assertDatabaseMissing('tags', ['name' => 'second']);
    }

    public function test_edits_and_deletes_only_own_tags(): void
    {
        $own = Tag::create(['name' => 'mine', 'owner' => self::USER, 'content' => 'old']);
        $other = Tag::create(['name' => 'theirs', 'owner' => '1', 'content' => 'x']);

        Livewire::test(MyTags::class)->call('edit', $own->id)->set('content', 'new')->call('save')->assertHasNoErrors();
        $this->assertSame('new', $own->fresh()->content);

        Livewire::test(MyTags::class)->call('edit', $other->id)->assertNotFound();
        Livewire::test(MyTags::class)->call('delete', $other->id)->assertNotFound();
        $this->assertNotNull($other->fresh());

        Livewire::test(MyTags::class)->call('delete', $own->id);
        $this->assertNull($own->fresh());
    }

    public function test_embed_settings_need_premium(): void
    {
        $tag = Tag::create(['name' => 'mine', 'owner' => self::USER, 'content' => 'x']);

        Livewire::test(MyTags::class)->call('edit', $tag->id)->set('embed', true)->set('embedColor', '#abc123')->call('save');
        $this->assertFalse($tag->fresh()->embed);

        config(['toxbot.premium_user_skus' => ['900']]);
        \App\Models\Entitlement::create(['id' => '1', 'sku_id' => '900', 'user_id' => self::USER, 'type' => 8, 'deleted' => false, 'consumed' => false]);

        Livewire::test(MyTags::class)->call('edit', $tag->id)->set('embed', true)->set('embedColor', '#ABC123')
            ->set('embedImage', 'https://evil.example.com/x.png')->call('save')->assertHasErrors('embedImage');
        Livewire::test(MyTags::class)->call('edit', $tag->id)->set('embed', true)->set('embedColor', '#ABC123')
            ->set('embedImage', 'https://i.imgur.com/x.png')->call('save')->assertHasNoErrors();

        $tag->refresh();
        $this->assertTrue($tag->embed);
        $this->assertSame('#abc123', $tag->embed_color);
        $this->assertSame('https://i.imgur.com/x.png', $tag->embed_image);
    }
}
