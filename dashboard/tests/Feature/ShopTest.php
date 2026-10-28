<?php

namespace Tests\Feature;

use App\Livewire\Shop;
use App\Models\BotUser;
use App\Models\DashboardUser;
use App\Models\Entitlement;
use App\Models\ShopItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '123456789012345678';

    protected function setUp(): void
    {
        parent::setUp();
        config(['toxbot.premium_user_skus' => ['900']]);

        foreach ([
            ['Network', 0, false], ['Ocean', 500, false], ['Forest', 500, false],
            ['Synthwave', 1500, false], ['Galaxy', 0, true],
        ] as $i => [$name, $price, $premium]) {
            ShopItem::create(['name' => $name, 'file' => strtolower($name).'.jpg', 'price' => $price, 'premium_only' => $premium, 'sort' => $i]);
        }

        $this->actingAs(DashboardUser::create(['id' => (int) self::USER, 'username' => 'newtox']));
    }

    private function credits(int $amount): void
    {
        $user = BotUser::forDiscordId(self::USER);
        $user->credits = $amount;
        $user->save();
    }

    public function test_shop_page_lists_the_backgrounds(): void
    {
        $this->get('/shop')->assertOk()->assertSee('Ocean')->assertSee('Synthwave');
    }

    public function test_buying_takes_credits_and_equips(): void
    {
        $this->credits(800);
        $ocean = ShopItem::where('name', 'Ocean')->first();

        Livewire::test(Shop::class)->call('buy', $ocean->id)->assertSet('success', true);

        $user = BotUser::find(self::USER);
        $this->assertSame(300, $user->credits);
        $this->assertSame($ocean->id, $user->background_id);
        $this->assertTrue($user->items()->whereKey($ocean->id)->exists());
        $this->assertDatabaseHas('credit_transactions', ['user' => self::USER, 'amount' => -500, 'reason' => 'purchase']);
    }

    public function test_cannot_buy_without_enough_credits_or_twice(): void
    {
        $this->credits(100);
        $synth = ShopItem::where('name', 'Synthwave')->first();

        Livewire::test(Shop::class)->call('buy', $synth->id)->assertSet('success', false);
        $this->assertSame(100, BotUser::find(self::USER)->credits);

        $this->credits(5000);
        Livewire::test(Shop::class)->call('buy', $synth->id)->assertSet('success', true);
        Livewire::test(Shop::class)->call('buy', $synth->id)->assertSet('success', false);
        $this->assertSame(3500, BotUser::find(self::USER)->credits);
    }

    public function test_premium_backgrounds_need_premium(): void
    {
        $galaxy = ShopItem::where('name', 'Galaxy')->first();

        Livewire::test(Shop::class)->call('buy', $galaxy->id)->assertSet('success', false);

        Entitlement::create(['id' => '1', 'sku_id' => '900', 'user_id' => self::USER, 'type' => 8, 'deleted' => false, 'consumed' => false]);

        Livewire::test(Shop::class)->call('buy', $galaxy->id)->assertSet('success', true);
        $this->assertSame($galaxy->id, BotUser::find(self::USER)->background_id);
    }

    public function test_free_background_can_be_equipped_without_buying(): void
    {
        $this->credits(800);
        $ocean = ShopItem::where('name', 'Ocean')->first();
        Livewire::test(Shop::class)->call('buy', $ocean->id);

        $network = ShopItem::where('name', 'Network')->first();
        Livewire::test(Shop::class)->call('equip', $network->id)->assertSet('success', true);
        $this->assertSame($network->id, BotUser::find(self::USER)->background_id);

        $forest = ShopItem::where('name', 'Forest')->first();
        Livewire::test(Shop::class)->call('equip', $forest->id)->assertSet('success', false);
    }

    public function test_manual_premium_users_get_premium_without_sku(): void
    {
        config(['toxbot.premium_user_skus' => [], 'toxbot.premium_users' => [self::USER]]);
        $galaxy = ShopItem::where('name', 'Galaxy')->first();

        Livewire::test(Shop::class)->call('buy', $galaxy->id)->assertSet('success', true);
    }

    public function test_can_go_back_to_the_default_background(): void
    {
        $this->credits(800);
        $ocean = ShopItem::where('name', 'Ocean')->first();
        Livewire::test(Shop::class)->call('buy', $ocean->id);

        Livewire::test(Shop::class)->call('useDefault')->assertSet('success', true);
        $this->assertNull(BotUser::find(self::USER)->background_id);
    }

    public function test_shop_without_backgrounds_shows_a_hint(): void
    {
        ShopItem::query()->delete();
        $this->get('/shop')->assertOk()->assertSee(__('shop.empty'));
    }

    public function test_frames_and_bars_are_bought_and_equipped_separately(): void
    {
        $this->credits(3000);
        $ocean = ShopItem::where('name', 'Ocean')->first();
        $frame = ShopItem::create(['name' => 'Gold Ring', 'type' => ShopItem::FRAME, 'file' => 'gold.png', 'price' => 1000]);
        $bar = ShopItem::create(['name' => 'Candy Bar', 'type' => ShopItem::BAR, 'file' => 'candy.png', 'price' => 400]);

        Livewire::test(Shop::class)
            ->call('buy', $ocean->id)
            ->call('buy', $frame->id)
            ->call('buy', $bar->id)
            ->assertSet('success', true);

        $user = BotUser::find(self::USER);
        $this->assertSame([$ocean->id, $frame->id, $bar->id], [$user->background_id, $user->frame_id, $user->bar_id]);
        $this->assertSame(1100, $user->credits);

        Livewire::test(Shop::class)->set('tab', ShopItem::FRAME)->call('useDefault')->assertSet('success', true);
        $user->refresh();
        $this->assertNull($user->frame_id);
        $this->assertSame($ocean->id, $user->background_id);
        $this->assertSame($bar->id, $user->bar_id);
    }

    public function test_tabs_list_only_items_of_their_kind(): void
    {
        ShopItem::create(['name' => 'Gold Ring', 'type' => ShopItem::FRAME, 'file' => 'gold.png', 'price' => 1000, 'animated' => true]);

        Livewire::test(Shop::class)
            ->assertSee('Ocean')->assertDontSee('Gold Ring')
            ->call('selectTab', ShopItem::FRAME)
            ->assertSee('Gold Ring')->assertDontSee('Ocean')->assertSee(__('shop.animated'))
            ->call('selectTab', 'nonsense')
            ->assertSet('tab', ShopItem::BACKGROUND);
    }

    public function test_preview_url_carries_each_kind(): void
    {
        $frame = ShopItem::create(['name' => 'Gold Ring', 'type' => ShopItem::FRAME, 'file' => 'gold.png', 'price' => 1000]);
        $ocean = ShopItem::where('name', 'Ocean')->first();

        Livewire::test(Shop::class)
            ->call('preview', $ocean->id)
            ->call('preview', $frame->id)
            ->assertSet('previews', [ShopItem::BACKGROUND => $ocean->id, ShopItem::FRAME => $frame->id])
            ->assertSee('bg='.$ocean->id.'&amp;frame='.$frame->id, false);
    }
}
