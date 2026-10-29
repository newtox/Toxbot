<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\ShopItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CardTest extends TestCase
{
    use RefreshDatabase;

    public function test_background_images_are_served(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('backgrounds/test.jpg', 'image');
        $bg = ShopItem::create(['name' => 'Test', 'file' => 'test.jpg']);

        $this->get(route('backgrounds.show', $bg))->assertOk();
        $this->get('/backgrounds/99999')->assertNotFound();
    }

    public function test_renders_a_png_card(): void
    {
        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('GD with FreeType is not installed.');
        }

        config(['services.discord.bot_token' => 'bot-token']);
        Http::fake([
            'discord.com/*' => Http::response(['id' => '123456789012345678', 'avatar' => null]),
            'cdn.discordapp.com/*' => Http::response('', 404),
        ]);

        $user = BotUser::forDiscordId('123456789012345678');
        $user->xp = 350;
        $user->save();

        $response = $this->get('/card/123456789012345678.png?guild=222222222222222222');
        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    private function fakeDiscord(): void
    {
        config(['services.discord.bot_token' => 'bot-token']);
        Http::fake([
            'discord.com/*' => Http::response(['id' => '123456789012345678', 'avatar' => null]),
            'cdn.discordapp.com/*' => Http::response('', 404),
        ]);
    }

    private function item(string $name, string $type, string $source): ShopItem
    {
        Storage::disk('local')->put('backgrounds/'.$source, file_get_contents(resource_path('shop/'.$source)));

        return ShopItem::create([
            'name' => $name,
            'type' => $type,
            'file' => $source,
            'animated' => str_ends_with($source, '.gif'),
        ]);
    }

    public function test_static_items_keep_the_card_a_png(): void
    {
        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('GD with FreeType is not installed.');
        }
        Storage::fake('local');
        $this->fakeDiscord();

        $frame = $this->item('Gold Ring', ShopItem::FRAME, 'frame-gold.png');
        $bar = $this->item('Candy Bar', ShopItem::BAR, 'bar-candy.png');
        $user = BotUser::forDiscordId('123456789012345678');
        $user->xp = 350;
        $user->frame_id = $frame->id;
        $user->bar_id = $bar->id;
        $user->save();

        $response = $this->get('/card/123456789012345678.png');
        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());

        $user->frame_id = null;
        $user->bar_id = null;
        $user->save();
        $this->assertNotSame($response->getContent(), $this->get('/card/123456789012345678.png')->getContent());
    }

    public function test_animated_items_turn_the_card_into_a_cached_gif(): void
    {
        if (! function_exists('imagettftext') || ! class_exists(\Imagick::class)) {
            $this->markTestSkipped('GD with FreeType and Imagick are needed.');
        }
        Storage::fake('local');
        $this->fakeDiscord();

        $ring = $this->item('Rainbow Ring', ShopItem::FRAME, 'frame-rainbow.gif');

        $response = $this->get('/card/123456789012345678.png?frame='.$ring->id);
        $response->assertOk()->assertHeader('Content-Type', 'image/gif');
        $this->assertStringStartsWith('GIF89a', $response->getContent());
        $this->assertCount(1, Storage::disk('local')->files('cards'));

        $this->get('/card/123456789012345678.png?frame='.$ring->id)->assertOk();
        $this->assertCount(1, Storage::disk('local')->files('cards'));
    }

    public function test_retired_items_still_show_for_their_owners(): void
    {
        if (! function_exists('imagettftext') || ! class_exists(\Imagick::class)) {
            $this->markTestSkipped('GD with FreeType and Imagick are needed.');
        }
        Storage::fake('local');
        $this->fakeDiscord();

        $retired = $this->item('Gold Ring', ShopItem::FRAME, 'frame-gold.png');
        $retired->update(['active' => false]);
        $owner = BotUser::forDiscordId('123456789012345678');
        $owner->frame_id = $retired->id;
        $owner->save();
        $static = $this->get('/card/123456789012345678.png')->getContent();
        $owner->frame_id = null;
        $owner->save();
        $this->assertNotSame($static, $this->get('/card/123456789012345678.png')->getContent());
    }

    public function test_animated_discord_avatars_make_the_card_a_gif(): void
    {
        if (! function_exists('imagettftext') || ! class_exists(\Imagick::class)) {
            $this->markTestSkipped('GD with FreeType and Imagick are needed.');
        }
        Storage::fake('local');
        config(['services.discord.bot_token' => 'bot-token']);
        $gif = file_get_contents(resource_path('shop/starfield.gif'));
        Http::fake([
            'discord.com/api/v10/users/111111111111111111' => Http::response(['id' => '111111111111111111', 'avatar' => 'a_0123abcd']),
            'discord.com/api/v10/users/222222222222222222' => Http::response(['id' => '222222222222222222', 'avatar' => '0123abcd']),
            'cdn.discordapp.com/avatars/111111111111111111/a_0123abcd.gif*' => Http::response($gif),
            'cdn.discordapp.com/*' => Http::response('', 404),
        ]);

        $this->get('/card/111111111111111111.png')->assertOk()->assertHeader('Content-Type', 'image/gif');
        Storage::disk('local')->assertExists('avatars/111111111111111111-a_0123abcd.gif');

        $this->get('/card/222222222222222222.png')->assertOk()->assertHeader('Content-Type', 'image/png');
    }
}
