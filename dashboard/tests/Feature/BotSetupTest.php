<?php

namespace Tests\Feature;

use App\Services\AutoSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BotSetupTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'time' => time(),
            'guild' => '111111111111111111',
            'guild_name' => 'Toxic Lounge',
            'areas' => ['tickets', 'moderation'],
            'team_roles' => ['222222222222222222'],
            'user_name' => 'newtox',
            'locale' => 'de_de',
        ], $overrides);
    }

    private function send(array $payload, ?string $secret = 'bot-token')
    {
        $body = json_encode($payload);
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        if ($secret !== null) {
            $headers['X-Toxbot-Signature'] = hash_hmac('sha256', $body, $secret);
        }

        return $this->call('POST', '/internal/setup', [], [], [], $this->transformHeadersToServerVars($headers), $body);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.discord.bot_token' => 'bot-token']);
    }

    public function test_the_bot_can_run_the_auto_setup(): void
    {
        $setup = Mockery::mock(AutoSetup::class);
        $setup->shouldReceive('run')
            ->once()
            ->with('111111111111111111', 'Toxic Lounge', ['tickets', 'moderation'], ['222222222222222222'], 'newtox')
            ->andReturnUsing(fn () => [['ok' => true, 'text' => app()->getLocale()]]);
        $this->app->instance(AutoSetup::class, $setup);

        $this->send($this->payload())
            ->assertOk()
            ->assertExactJson(['steps' => [['ok' => true, 'text' => 'de']]]);
    }

    public function test_requests_without_a_valid_signature_are_refused(): void
    {
        $setup = Mockery::mock(AutoSetup::class);
        $setup->shouldNotReceive('run');
        $this->app->instance(AutoSetup::class, $setup);

        $this->send($this->payload(), null)->assertUnauthorized();
        $this->send($this->payload(), 'wrong')->assertUnauthorized();
        $this->send($this->payload(['time' => time() - 600]))->assertUnauthorized();
        $this->send($this->payload(['areas' => ['everything']]))->assertUnprocessable();
        $this->send($this->payload(['team_roles' => []]))->assertUnprocessable();

        config(['services.discord.bot_token' => '']);
        $this->send($this->payload(), '')->assertUnauthorized();
    }
}
