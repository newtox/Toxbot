<?php

namespace Tests\Feature;

use App\Livewire\AdminPremium;
use App\Models\DashboardUser;
use App\Models\PremiumGrant;
use App\Services\Premium;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPremiumTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER = '402483602094555138';

    private const USER = '123456789012345678';

    private const GUILD = '111111111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        config(['toxbot.owner_id' => self::OWNER, 'toxbot.premium_guilds' => []]);
        $this->actingAs(DashboardUser::create(['id' => (int) self::OWNER, 'username' => 'newtox']));
    }

    public function test_admin_pages_have_their_own_sidebar(): void
    {
        foreach (['admin', 'admin.backgrounds', 'admin.users', 'admin.blacklist', 'admin.premium'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee(route('admin.backgrounds'))
                ->assertSee(route('admin.users'))
                ->assertSee(route('admin.blacklist'))
                ->assertSee(route('admin.premium'));
        }

        $this->actingAs(DashboardUser::create(['id' => (int) self::USER, 'username' => 'someone']));
        foreach (['admin', 'admin.backgrounds', 'admin.users', 'admin.blacklist', 'admin.premium'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_grant_without_end_date_lasts(): void
    {
        $this->assertFalse(app(Premium::class)->isGuildPremium(self::GUILD));

        Livewire::test(AdminPremium::class)
            ->set('target', self::GUILD)
            ->set('note', 'Tester')
            ->call('grant')
            ->assertHasNoErrors()
            ->assertSee(__('admin.premium_forever'));

        $this->travel(5)->years();
        $this->assertTrue((new Premium)->isGuildPremium(self::GUILD));
    }

    public function test_grant_with_end_date_runs_out_after_that_day(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(12, 0));

        Livewire::test(AdminPremium::class)
            ->set('target', self::GUILD)
            ->set('until', '2026-11-30')
            ->call('grant')
            ->assertHasNoErrors();

        $this->travelTo(now()->setDate(2026, 11, 30)->setTime(20, 0));
        $this->assertTrue((new Premium)->isGuildPremium(self::GUILD));

        $this->travelTo(now()->setDate(2026, 12, 1)->setTime(1, 0));
        $this->assertFalse((new Premium)->isGuildPremium(self::GUILD));
    }

    public function test_past_dates_and_bad_ids_are_rejected(): void
    {
        Livewire::test(AdminPremium::class)
            ->set('target', self::USER)->set('until', '2020-01-01')->call('grant')->assertHasErrors('until')
            ->set('target', 'abc')->set('until', '')->call('grant')->assertHasErrors('target');

        $this->assertSame(0, PremiumGrant::query()->count());
    }

    public function test_granting_again_replaces_the_end_date_and_revoking_removes_it(): void
    {
        $component = Livewire::test(AdminPremium::class)
            ->set('target', self::GUILD)->set('until', now()->addMonth()->toDateString())->call('grant')
            ->set('target', self::GUILD)->set('until', '')->call('grant');

        $grant = PremiumGrant::query()->sole();
        $this->assertNull($grant->ends_at);

        $component->call('revoke', $grant->id);
        $this->assertSame(0, PremiumGrant::query()->count());
        $this->assertFalse((new Premium)->isGuildPremium(self::GUILD));
    }

    public function test_others_cannot_grant(): void
    {
        $this->actingAs(DashboardUser::create(['id' => (int) self::USER, 'username' => 'someone']));

        Livewire::test(AdminPremium::class)->assertForbidden();
    }
}
