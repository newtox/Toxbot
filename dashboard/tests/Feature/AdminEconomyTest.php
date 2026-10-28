<?php

namespace Tests\Feature;

use App\Livewire\AdminEconomy;
use App\Models\BotUser;
use App\Models\CreditTransaction;
use App\Models\DashboardUser;
use App\Models\ShopItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEconomyTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER = '402483602094555138';

    private const USER = '123456789012345678';

    protected function setUp(): void
    {
        parent::setUp();
        config(['toxbot.owner_id' => self::OWNER, 'services.discord.bot_token' => 'test']);
        Http::fake(['*' => Http::response(['id' => self::USER, 'username' => 'someone', 'global_name' => 'Someone'])]);
        $this->actingAs(DashboardUser::create(['id' => (int) self::OWNER, 'username' => 'newtox']));
    }

    public function test_only_the_owner_can_open_it(): void
    {
        $this->get(route('admin.users'))->assertOk()->assertSee(__('admin.users_title'));

        $this->actingAs(DashboardUser::create(['id' => (int) self::USER, 'username' => 'someone']));
        $this->get(route('admin.users'))->assertForbidden();
    }

    public function test_credits_can_be_added_and_removed_without_going_negative(): void
    {
        BotUser::forDiscordId(self::USER)->forceFill(['credits' => 300])->save();

        Livewire::test(AdminEconomy::class)
            ->set('lookup', self::USER)
            ->call('load')
            ->assertSee('Someone')
            ->set('amount', '200')
            ->call('adjust')
            ->set('amount', '-1000')
            ->call('adjust')
            ->assertHasNoErrors();

        $this->assertSame(0, BotUser::query()->find(self::USER)->credits);
        $this->assertSame([200, -500], CreditTransaction::query()->where('user', self::USER)->orderBy('id')->pluck('amount')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(['admin'], CreditTransaction::query()->distinct()->pluck('reason')->all());
    }

    public function test_invalid_input_is_rejected(): void
    {
        Livewire::test(AdminEconomy::class)
            ->set('lookup', 'abc')
            ->call('load')
            ->assertHasErrors('lookup')
            ->set('lookup', self::USER)
            ->call('load')
            ->set('amount', '0')
            ->call('adjust')
            ->assertHasErrors('amount');
    }

    public function test_items_can_be_given_and_taken_away(): void
    {
        $retired = ShopItem::create(['name' => 'Old Sky', 'file' => 'old.jpg', 'price' => 500, 'active' => false]);
        $frame = ShopItem::create(['name' => 'Gold Ring', 'type' => ShopItem::FRAME, 'file' => 'gold.png', 'price' => 1000, 'premium_only' => true]);

        $component = Livewire::test(AdminEconomy::class)
            ->set('lookup', self::USER)
            ->call('load')
            ->set('giveId', (string) $retired->id)
            ->call('give')
            ->set('giveId', (string) $frame->id)
            ->call('give')
            ->assertSee('Old Sky')
            ->assertSee('Gold Ring');

        $user = BotUser::query()->find(self::USER);
        $this->assertEqualsCanonicalizing([$retired->id, $frame->id], $user->items()->pluck('backgrounds.id')->all());

        $user->update(['frame_id' => $frame->id]);
        $component->call('take', $frame->id);

        $user->refresh();
        $this->assertNull($user->frame_id);
        $this->assertSame([$retired->id], $user->items()->pluck('backgrounds.id')->all());
    }
}
