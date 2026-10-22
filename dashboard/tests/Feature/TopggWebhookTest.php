<?php

namespace Tests\Feature;

use App\Models\BotUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopggWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '123456789012345678';

    protected function setUp(): void
    {
        parent::setUp();
        config(['toxbot.topgg.webhook_secret' => 'whs_secret', 'toxbot.topgg.vote_reward' => 200]);
    }

    public function test_legacy_webhook_pays_credits_once(): void
    {
        $payload = ['bot' => '1', 'user' => self::USER, 'type' => 'upvote', 'isWeekend' => true];

        $this->postJson('/webhooks/topgg', $payload, ['Authorization' => 'wrong'])->assertUnauthorized();
        $this->postJson('/webhooks/topgg', $payload, ['Authorization' => 'whs_secret'])->assertNoContent();
        $this->postJson('/webhooks/topgg', $payload, ['Authorization' => 'whs_secret'])->assertNoContent();

        $this->assertSame(400, BotUser::find(self::USER)->credits);
    }

    public function test_legacy_test_votes_are_ignored(): void
    {
        $this->postJson('/webhooks/topgg', ['user' => self::USER, 'type' => 'test'], ['Authorization' => 'whs_secret'])->assertNoContent();
        $this->assertNull(BotUser::find(self::USER));
    }

    public function test_signed_v1_webhook(): void
    {
        $body = json_encode(['type' => 'vote.create', 'data' => [
            'id' => '808', 'weight' => 1,
            'user' => ['id' => 'x', 'platform_id' => self::USER],
        ]]);
        $time = time();
        $signature = hash_hmac('sha256', $time.'.'.$body, 'whs_secret');

        $send = fn (string $sig) => $this->call('POST', '/webhooks/topgg', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_TOPGG_SIGNATURE' => $sig,
        ], $body);

        $send("t={$time},v1=deadbeef")->assertUnauthorized();
        $send("t={$time},v1={$signature}")->assertNoContent();
        $send("t={$time},v1={$signature}")->assertNoContent();

        $this->assertSame(200, BotUser::find(self::USER)->credits);
        $this->assertDatabaseCount('credit_transactions', 1);
    }

    public function test_disabled_without_secret(): void
    {
        config(['toxbot.topgg.webhook_secret' => null]);
        $this->postJson('/webhooks/topgg', ['user' => self::USER, 'type' => 'upvote'])->assertNotFound();
    }
}
