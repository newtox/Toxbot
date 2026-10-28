<?php

namespace Tests\Feature;

use App\Livewire\ProfileOverview;
use App\Models\BotUser;
use App\Models\DashboardUser;
use App\Models\ShopItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileEquipTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '123456789012345678';

    protected function setUp(): void
    {
        parent::setUp();
        config(['toxbot.premium_user_skus' => [], 'toxbot.premium_users' => []]);
        $this->actingAs(DashboardUser::create(['id' => (int) self::USER, 'username' => 'newtox']));
    }

    public function test_only_usable_items_are_offered(): void
    {
        $owned = ShopItem::create(['name' => 'Ocean', 'file' => 'ocean.jpg', 'price' => 500]);
        ShopItem::create(['name' => 'Network', 'file' => 'network.jpg', 'price' => 0]);
        ShopItem::create(['name' => 'Forest', 'file' => 'forest.jpg', 'price' => 500]);
        ShopItem::create(['name' => 'Galaxy', 'file' => 'galaxy.jpg', 'price' => 0, 'premium_only' => true]);
        ShopItem::create(['name' => 'Gold Ring', 'type' => ShopItem::FRAME, 'file' => 'gold.png', 'price' => 1000]);

        $user = BotUser::forDiscordId(self::USER);
        $user->save();
        $user->items()->attach($owned->id, ['created_at' => now()]);

        Livewire::test(ProfileOverview::class)
            ->assertSee('Ocean')
            ->assertSee('Network')
            ->assertDontSee('Forest')
            ->assertDontSee('Galaxy')
            ->assertDontSee('Gold Ring')
            ->assertSee(__('levels.equip_no_frame'));
    }

    public function test_items_are_equipped_and_reset_per_kind(): void
    {
        $background = ShopItem::create(['name' => 'Ocean', 'file' => 'ocean.jpg', 'price' => 500]);
        $frame = ShopItem::create(['name' => 'Gold Ring', 'type' => ShopItem::FRAME, 'file' => 'gold.png', 'price' => 1000]);

        $user = BotUser::forDiscordId(self::USER);
        $user->save();
        $user->items()->attach([$background->id, $frame->id], ['created_at' => now()]);

        $component = Livewire::test(ProfileOverview::class)
            ->set('equipped.background', (string) $background->id)
            ->set('equipped.frame', (string) $frame->id)
            ->assertSet('equipped.background', (string) $background->id);

        $user->refresh();
        $this->assertSame($background->id, $user->background_id);
        $this->assertSame($frame->id, $user->frame_id);

        $component->set('equipped.frame', '');
        $user->refresh();
        $this->assertNull($user->frame_id);
        $this->assertSame($background->id, $user->background_id);
    }

    public function test_items_not_owned_cannot_be_equipped(): void
    {
        $item = ShopItem::create(['name' => 'Forest', 'file' => 'forest.jpg', 'price' => 500]);
        $frame = ShopItem::create(['name' => 'Gold Ring', 'type' => ShopItem::FRAME, 'file' => 'gold.png', 'price' => 1000]);
        BotUser::forDiscordId(self::USER)->save();

        Livewire::test(ProfileOverview::class)
            ->set('equipped.background', (string) $item->id)
            ->assertSet('equipped.background', '')
            ->assertSee(__('shop.not_owned'))
            ->set('equipped.bar', (string) $frame->id)
            ->assertSet('equipped.bar', '');

        $this->assertNull(BotUser::query()->find(self::USER)->background_id);
        $this->assertNull(BotUser::query()->find(self::USER)->frame_id);
    }
}
