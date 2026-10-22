<?php

namespace Tests\Feature;

use App\Models\Background;
use App\Models\BotUser;
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
        $bg = Background::create(['name' => 'Test', 'file' => 'test.jpg']);

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
}
