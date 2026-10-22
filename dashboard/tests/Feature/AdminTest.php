<?php

namespace Tests\Feature;

use App\Livewire\AdminBackgrounds;
use App\Livewire\AdminReports;
use App\Models\Background;
use App\Models\BlacklistEntry;
use App\Models\BotUser;
use App\Models\DashboardUser;
use App\Models\Tag;
use App\Models\TagReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER = '402483602094555138';

    protected function setUp(): void
    {
        parent::setUp();
        config(['toxbot.owner_id' => self::OWNER]);
    }

    public function test_only_the_owner_sees_the_admin_page(): void
    {
        $this->actingAs(DashboardUser::create(['id' => 1234567890123456, 'username' => 'someone']));
        $this->get('/admin')->assertForbidden();
        $this->get('/profile')->assertDontSee(route('admin'));

        $this->actingAs(DashboardUser::create(['id' => (int) self::OWNER, 'username' => 'newtox']));
        $this->get('/admin')->assertOk();
        $this->get('/profile')->assertSee(route('admin'));
    }

    public function test_delete_reported_tag_and_blacklist_owner(): void
    {
        $this->actingAs(DashboardUser::create(['id' => (int) self::OWNER, 'username' => 'newtox']));
        $tag = Tag::create(['name' => 'spam', 'owner' => '77', 'content' => 'buy stuff']);
        $report = TagReport::create(['tag_id' => $tag->id, 'reporter' => '88', 'reason' => 'spam']);

        Livewire::test(AdminReports::class)->assertSee('buy stuff')->call('deleteTag', $report->id, true);

        $this->assertNull($tag->fresh());
        $this->assertNull($report->fresh());
        $this->assertTrue(BlacklistEntry::where('user', '77')->exists());
    }

    public function test_upload_and_delete_background(): void
    {
        Storage::fake('local');
        $this->actingAs(DashboardUser::create(['id' => (int) self::OWNER, 'username' => 'newtox']));

        Livewire::test(AdminBackgrounds::class)
            ->set('upload', UploadedFile::fake()->image('bg.png', 1000, 600))
            ->set('newName', 'Mine')
            ->set('newPrice', 42)
            ->call('add')
            ->assertHasNoErrors();

        $bg = Background::where('name', 'Mine')->first();
        $this->assertSame(42, $bg->price);
        Storage::disk('local')->assertExists('backgrounds/'.$bg->file);

        $user = BotUser::forDiscordId('5');
        $user->background_id = $bg->id;
        $user->save();

        Livewire::test(AdminBackgrounds::class)->call('delete', $bg->id);
        $this->assertNull($bg->fresh());
        $this->assertNull(BotUser::find('5')->background_id);
        Storage::disk('local')->assertMissing('backgrounds/'.$bg->file);
    }

    public function test_non_owner_cannot_use_admin_components(): void
    {
        $this->actingAs(DashboardUser::create(['id' => 1234567890123456, 'username' => 'someone']));
        Livewire::test(AdminReports::class)->assertForbidden();
    }
}
