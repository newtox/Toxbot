<?php

namespace Tests\Feature;

use App\Livewire\PremiumServers;
use App\Livewire\StreamList;
use App\Models\DashboardUser;
use App\Models\Entitlement;
use App\Models\Guild;
use App\Models\StreamAnnouncement;
use App\Models\StreamSource;
use App\Models\StreamSubscription;
use App\Services\GuildAccess;
use App\Services\Premium;
use App\Services\Streams\Announcer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class StreamsTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const SECRET = 'stream-secret-123';

    private array $discordPosts = [];

    private ?array $liveStreams = null;

    private array $youtubeVideos = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://toxbot.test',
            'services.discord.bot_token' => 'bot-token',
            'toxbot.streams.webhook_secret' => self::SECRET,
            'toxbot.streams.twitch_client_id' => 'twitch-id',
            'toxbot.streams.twitch_client_secret' => 'twitch-secret',
            'toxbot.premium_guild_skus' => ['700'],
        ]);
        url()->forceRootUrl('https://toxbot.test');

        Http::fake(function (Request $request) {
            $url = $request->url();

            return match (true) {
                str_contains($url, 'id.twitch.tv/oauth2/token') => Http::response(['access_token' => 'app-token', 'expires_in' => 3600]),
                str_contains($url, 'api.twitch.tv/helix/users') => str_contains($url, 'login=nobody')
                    ? Http::response(['data' => []])
                    : Http::response(['data' => [['id' => '42', 'login' => 'newtox', 'display_name' => 'Newtox', 'profile_image_url' => 'https://cdn/x.png']]]),
                str_contains($url, 'api.twitch.tv/helix/eventsub/subscriptions') && $request->method() === 'POST' => Http::response(['data' => [['id' => 'sub-'.$request['type']]]], 202),
                str_contains($url, 'api.twitch.tv/helix/eventsub/subscriptions') && $request->method() === 'DELETE' => Http::response(null, 204),
                str_contains($url, 'api.twitch.tv/helix/streams') && $this->liveStreams !== null => Http::response(['data' => $this->liveStreams]),
                str_contains($url, 'googleapis.com/youtube/v3/videos') => Http::response(['items' => $this->youtubeVideos]),
                str_contains($url, 'api.twitch.tv/helix/streams') => Http::response(['data' => [['title' => 'Valheim mit Freunden', 'game_name' => 'Valheim', 'thumbnail_url' => 'https://img/{width}x{height}.jpg']]]),
                str_contains($url, 'api.twitch.tv/helix/videos') => Http::response(['data' => [['url' => 'https://www.twitch.tv/videos/1']]]),
                str_contains($url, 'pubsubhubbub.appspot.com') => Http::response('', 202),
                str_contains($url, 'googleapis.com/youtube/v3/channels') => Http::response(['error' => ['code' => 403, 'message' => 'YouTube Data API v3 has not been used in project 123 before or it is disabled.']], 403),
                str_contains($url, 'youtube.com/feeds/videos.xml') => Http::response('<feed xmlns="http://www.w3.org/2005/Atom"><title>Kanal</title><author><name>Mein Kanal</name></author></feed>'),
                str_contains($url, 'discord.com/api/v10/channels') && $request->method() === 'POST' => $this->recordPost($request),
                str_contains($url, 'discord.com/api/v10/channels') => Http::response(['id' => '900']),
                str_contains($url, 'discord.com/api/v10/guilds/'.self::GUILD.'/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0],
                    ['id' => '888', 'name' => 'Valheim Fans', 'position' => 2, 'color' => 0],
                    ['id' => '999', 'name' => 'Bot', 'position' => 3, 'color' => 0, 'managed' => true],
                ]),
                default => Http::response([], 404),
            };
        });
    }

    private function recordPost(Request $request)
    {
        $this->discordPosts[] = $request->data();

        return Http::response(['id' => '900']);
    }

    private function guild(): Guild
    {
        return Guild::create(['id' => self::GUILD, 'stream' => '555', 'notify' => '777', 'stream_msg' => '&role &channel spielt &game: &link']);
    }

    private function twitchSource(): StreamSource
    {
        return StreamSource::create(['platform' => 'twitch', 'external_id' => '42', 'login' => 'newtox', 'name' => 'Newtox']);
    }

    private function twitchRequest(string $type, array $payload, ?string $subscriptionType = null)
    {
        $body = json_encode($payload);
        $id = 'msg-'.uniqid();
        $timestamp = now()->toRfc3339String();
        $signature = 'sha256='.hash_hmac('sha256', $id.$timestamp.$body, self::SECRET);

        return $this->call('POST', '/webhooks/twitch', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_TWITCH_EVENTSUB_MESSAGE_ID' => $id,
            'HTTP_TWITCH_EVENTSUB_MESSAGE_TIMESTAMP' => $timestamp,
            'HTTP_TWITCH_EVENTSUB_MESSAGE_SIGNATURE' => $signature,
            'HTTP_TWITCH_EVENTSUB_MESSAGE_TYPE' => $type,
        ], $body);
    }

    public function test_twitch_challenge_and_signature(): void
    {
        $this->twitchRequest('webhook_callback_verification', ['challenge' => 'abc123'])
            ->assertOk()->assertSee('abc123');

        $this->call('POST', '/webhooks/twitch', [], [], [], [
            'HTTP_TWITCH_EVENTSUB_MESSAGE_ID' => 'x',
            'HTTP_TWITCH_EVENTSUB_MESSAGE_TIMESTAMP' => now()->toRfc3339String(),
            'HTTP_TWITCH_EVENTSUB_MESSAGE_SIGNATURE' => 'sha256=wrong',
            'HTTP_TWITCH_EVENTSUB_MESSAGE_TYPE' => 'notification',
        ], '{}')->assertForbidden();
    }

    public function test_twitch_online_posts_once_with_role_ping(): void
    {
        $this->guild();
        $source = $this->twitchSource();
        StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);

        $payload = ['subscription' => ['type' => 'stream.online'], 'event' => ['id' => 'stream-1', 'broadcaster_user_id' => '42', 'started_at' => now()->toIso8601String()]];
        $this->twitchRequest('notification', $payload)->assertNoContent();
        $this->twitchRequest('notification', $payload)->assertNoContent();

        $this->assertCount(1, $this->discordPosts);
        $post = $this->discordPosts[0];
        $this->assertSame('<@&777> Newtox spielt Valheim: https://www.twitch.tv/newtox', $post['content']);
        $this->assertSame(['parse' => [], 'roles' => ['777']], $post['allowed_mentions']);
        $this->assertSame('Valheim mit Freunden', $post['embeds'][0]['title']);
        $this->assertSame('stream-1', $source->fresh()->live_item);
        $this->assertSame('900', StreamAnnouncement::first()->message_id);
    }

    public function test_filters_and_end_action_need_server_premium(): void
    {
        $this->guild();
        $source = $this->twitchSource();
        $subscription = StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id, 'filter_games' => 'Palworld', 'end_action' => 'edit']);

        $online = ['subscription' => ['type' => 'stream.online'], 'event' => ['id' => 'stream-1', 'broadcaster_user_id' => '42']];
        $this->twitchRequest('notification', $online);
        $this->assertCount(1, $this->discordPosts, 'filters are ignored without premium');

        Entitlement::create(['id' => '1', 'sku_id' => '700', 'guild_id' => self::GUILD, 'type' => 8, 'deleted' => false, 'consumed' => false]);
        $source->update(['live_item' => null]);
        $this->twitchRequest('notification', ['subscription' => ['type' => 'stream.online'], 'event' => ['id' => 'stream-2', 'broadcaster_user_id' => '42']]);
        $this->assertCount(1, $this->discordPosts, 'Valheim does not match the Palworld filter');

        $subscription->update(['filter_games' => 'valheim']);
        $source->update(['live_item' => null]);
        $this->twitchRequest('notification', ['subscription' => ['type' => 'stream.online'], 'event' => ['id' => 'stream-3', 'broadcaster_user_id' => '42']]);
        $this->assertCount(2, $this->discordPosts);

        $this->twitchRequest('notification', ['subscription' => ['type' => 'stream.offline'], 'event' => ['broadcaster_user_id' => '42']]);
        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && str_contains($r->url(), '/channels/555/messages/900')
            && str_contains(json_encode($r->data(), JSON_UNESCAPED_SLASHES), 'twitch.tv/videos/1'));
        $this->assertNull($source->fresh()->live_item);
    }

    public function test_server_end_action_works_without_premium_and_premium_overrides_it(): void
    {
        $this->guild()->update(['stream_end_action' => 'delete', 'language' => 'de_de']);
        $source = $this->twitchSource();
        $subscription = StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id, 'end_action' => 'edit']);

        $this->twitchRequest('notification', ['subscription' => ['type' => 'stream.online'], 'event' => ['id' => 'stream-1', 'broadcaster_user_id' => '42']]);
        $this->twitchRequest('notification', ['subscription' => ['type' => 'stream.offline'], 'event' => ['broadcaster_user_id' => '42']]);
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_contains($r->url(), '/channels/555/messages/900'));

        config(['toxbot.premium_guilds' => [self::GUILD]]);
        $this->twitchRequest('notification', ['subscription' => ['type' => 'stream.online'], 'event' => ['id' => 'stream-2', 'broadcaster_user_id' => '42']]);
        $this->twitchRequest('notification', ['subscription' => ['type' => 'stream.offline'], 'event' => ['broadcaster_user_id' => '42']]);
        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && str_contains($r->url(), '/channels/555/messages/900')
            && $r['embeds'][0]['title'] === __('streams.ended_title', [], 'de'));

        $this->assertSame('default', (new StreamSubscription)->end_action);
    }

    public function test_youtube_verification_and_new_video(): void
    {
        $this->guild();
        $source = StreamSource::create(['platform' => 'youtube', 'external_id' => 'UC1234567890123456789012', 'name' => 'Mein Kanal']);
        StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);

        $topic = urlencode('https://www.youtube.com/xml/feeds/videos.xml?channel_id=UC1234567890123456789012');
        $this->get("/webhooks/youtube?hub.mode=subscribe&hub.topic={$topic}&hub.challenge=xyz&hub.lease_seconds=864000")
            ->assertOk()->assertSee('xyz');
        $this->assertTrue($source->fresh()->websub_expires_at->greaterThan(now()->addDays(9)));

        $this->get('/webhooks/youtube?hub.mode=subscribe&hub.topic=x&hub.challenge=xyz')->assertNotFound();

        $xml = '<feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns="http://www.w3.org/2005/Atom"><entry>'
            .'<id>yt:video:abc</id><yt:videoId>abc</yt:videoId><yt:channelId>UC1234567890123456789012</yt:channelId>'
            .'<title>Neues Video</title><published>'.now()->toAtomString().'</published></entry></feed>';
        $signature = 'sha1='.hash_hmac('sha1', $xml, self::SECRET);

        $this->call('POST', '/webhooks/youtube', [], [], [], ['HTTP_X_HUB_SIGNATURE' => 'sha1=wrong'], $xml)->assertNoContent();
        $this->assertCount(0, $this->discordPosts);

        $this->call('POST', '/webhooks/youtube', [], [], [], ['HTTP_X_HUB_SIGNATURE' => $signature], $xml)->assertNoContent();
        $this->call('POST', '/webhooks/youtube', [], [], [], ['HTTP_X_HUB_SIGNATURE' => $signature], $xml)->assertNoContent();

        $this->assertCount(1, $this->discordPosts);
        $this->assertSame('https://www.youtube.com/watch?v=abc', $this->discordPosts[0]['embeds'][0]['url']);
        $this->assertSame('<@&777> **Mein Kanal** uploaded a new video! https://www.youtube.com/watch?v=abc', $this->discordPosts[0]['content']);
    }

    public function test_sync_corrects_missed_twitch_events(): void
    {
        $this->guild();
        $source = $this->twitchSource();
        StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);
        $this->liveStreams = [['id' => 'stream-7', 'user_id' => '42', 'title' => 'Valheim mit Freunden', 'game_name' => 'Valheim', 'started_at' => now()->subMinutes(3)->toIso8601String()]];

        $this->artisan('streams:sync')->assertSuccessful();
        $this->artisan('streams:sync')->assertSuccessful();

        $this->assertSame('stream-7', $source->fresh()->live_item);
        $this->assertCount(1, $this->discordPosts);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'helix/streams?user_id=42'));

        $this->liveStreams = [];
        $this->artisan('streams:sync')->assertSuccessful();

        $this->assertNull($source->fresh()->live_item);
        $this->assertNotNull(StreamAnnouncement::first()->ended_at);
    }

    public function test_adding_a_live_streamer_marks_it_live_without_announcing(): void
    {
        $this->login();
        $this->guild();
        $this->liveStreams = [['id' => 'stream-9', 'user_id' => '42', 'started_at' => now()->subHour()->toIso8601String()]];

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->set('platform', 'twitch')->set('input', 'newtox')->call('add')
            ->assertSee('LIVE');

        $this->assertSame('stream-9', StreamSource::first()->live_item);
        $this->artisan('streams:sync')->assertSuccessful();
        $this->assertCount(0, $this->discordPosts);
    }

    public function test_youtube_live_status_is_shown_until_the_stream_ends(): void
    {
        config(['toxbot.streams.youtube_api_key' => 'yt-key']);
        $this->guild();
        $source = StreamSource::create(['platform' => 'youtube', 'external_id' => 'UC1234567890123456789012', 'name' => 'Mein Kanal']);
        StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);

        app(Announcer::class)->publishYoutube($source, 'live1', 'Live!', 'live', null);
        $this->assertSame('live1', $source->fresh()->live_item);

        $this->youtubeVideos = [['id' => 'live1', 'snippet' => ['liveBroadcastContent' => 'live', 'title' => 'Live!'], 'liveStreamingDetails' => ['actualStartTime' => now()->toIso8601String()]]];
        $this->artisan('streams:sync')->assertSuccessful();
        $this->assertSame('live1', $source->fresh()->live_item);

        $this->youtubeVideos = [['id' => 'live1', 'snippet' => ['liveBroadcastContent' => 'none', 'title' => 'Live!'], 'liveStreamingDetails' => ['actualEndTime' => now()->toIso8601String()]]];
        $this->artisan('streams:sync')->assertSuccessful();
        $this->assertNull($source->fresh()->live_item);
    }

    private function login(): void
    {
        Http::fake(['discord.com/api/v10/users/@me/guilds*' => Http::response([['id' => self::GUILD, 'name' => 'Toxic', 'permissions' => (string) 0x20]])]);
        $this->actingAs(DashboardUser::create(['id' => 123456789012345678, 'username' => 'newtox']));
        $this->mock(GuildAccess::class, fn ($mock) => $mock->shouldReceive('find')->andReturn(['id' => self::GUILD]));
    }

    public function test_add_and_remove_twitch_streamer_in_dashboard(): void
    {
        $this->login();

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->set('platform', 'twitch')
            ->set('input', 'https://twitch.tv/newtox')
            ->call('add')
            ->assertSet('error', null)
            ->assertSee('Newtox');

        $source = StreamSource::first();
        $this->assertSame('42', $source->external_id);
        $this->assertSame('sub-stream.online', $source->twitch_online_sub);
        $this->assertSame('sub-stream.offline', $source->twitch_offline_sub);

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->set('input', 'newtox')->call('add')->assertSet('error', __('streams.already_added'));
        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->set('input', 'nobody')->call('add')->assertSet('error', __('streams.not_found'));

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])->call('remove', StreamSubscription::first()->id);
        $this->assertSame(0, StreamSource::count());
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_contains($r->url(), 'id=sub-stream.online'));
    }

    public function test_free_limit_and_youtube_channel_id(): void
    {
        $this->login();
        config(['toxbot.streams.free_limit' => 1]);

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->set('platform', 'youtube')->set('input', 'https://www.youtube.com/channel/UC1234567890123456789012')
            ->call('add')->assertSet('error', null);
        $this->assertSame('Mein Kanal', StreamSource::first()->name);

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->set('platform', 'youtube')->set('input', '@someone')
            ->call('add')->assertSet('error', __('streams.limit', ['limit' => 1]));

        config(['toxbot.streams.free_limit' => 5]);
        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->set('platform', 'youtube')->set('input', '@someone')
            ->call('add')->assertSet('error', __('streams.youtube_need_id'));
    }

    public function test_placeholders_escape_markdown(): void
    {
        $this->guild()->update(['stream_video_msg' => '[&title](&link)']);
        $source = StreamSource::create(['platform' => 'youtube', 'external_id' => 'UC1234567890123456789012', 'name' => 'Mein_Kanal']);
        StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);

        $xml = '<feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns="http://www.w3.org/2005/Atom"><entry>'
            .'<id>yt:video:abc</id><yt:videoId>abc</yt:videoId><yt:channelId>UC1234567890123456789012</yt:channelId>'
            .'<title>GTA V - [4K] #07 - *Fliegen*</title><published>'.now()->toAtomString().'</published></entry></feed>';
        $this->call('POST', '/webhooks/youtube', [], [], [], ['HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', $xml, self::SECRET)], $xml)->assertNoContent();

        $this->assertSame('[GTA V - \\[4K\\] #07 - \\*Fliegen\\*](https://www.youtube.com/watch?v=abc)', $this->discordPosts[0]['content']);
    }

    public function test_youtube_api_errors_show_the_reason(): void
    {
        $this->login();
        config(['toxbot.streams.youtube_api_key' => 'key']);

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->set('platform', 'youtube')->set('input', '@someone')
            ->call('add')
            ->assertSet('error', __('streams.youtube_api_error', ['reason' => 'YouTube Data API v3 has not been used in project 123 before or it is disabled.']));
        $this->assertSame(0, StreamSource::count());
    }

    public function test_premium_servers_can_ping_an_own_role_per_streamer(): void
    {
        $this->login();
        $this->guild();
        $source = $this->twitchSource();
        $subscription = StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->call('edit', $subscription->id)->set('pingRole', '888')->call('saveSubscription');
        $this->assertNull($subscription->fresh()->role);

        config(['toxbot.premium_guilds' => [self::GUILD]]);
        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->call('edit', $subscription->id)->set('pingRole', '999')->call('saveSubscription')
            ->assertHasErrors('pingRole')
            ->set('pingRole', '888')->call('saveSubscription')->assertHasNoErrors();
        $this->assertSame('888', $subscription->fresh()->role);

        $payload = ['subscription' => ['type' => 'stream.online'], 'event' => ['id' => 'stream-2', 'broadcaster_user_id' => '42', 'started_at' => now()->toIso8601String()]];
        $this->twitchRequest('notification', $payload)->assertNoContent();

        $this->assertStringStartsWith('<@&888>', $this->discordPosts[0]['content']);
        $this->assertSame(['parse' => [], 'roles' => ['888']], $this->discordPosts[0]['allowed_mentions']);
    }

    public function test_server_subscription_unlocks_server_premium(): void
    {
        $this->login();
        $this->mock(GuildAccess::class, fn ($mock) => $mock
            ->shouldReceive('manageableGuilds')->andReturn([['id' => self::GUILD, 'name' => 'Toxic', 'icon_url' => null, 'has_bot' => true]])
            ->shouldReceive('find')->andReturn(['id' => self::GUILD]));

        config(['toxbot.premium_guild_skus' => ['900']]);
        $this->assertFalse(app(Premium::class)->isGuildPremium(self::GUILD));
        Livewire::test(PremiumServers::class)->assertSee('Toxic')->assertSee(__('profile.premium_inactive'));

        Entitlement::create(['id' => '1', 'sku_id' => '900', 'guild_id' => self::GUILD, 'type' => 8, 'deleted' => false, 'consumed' => false]);
        $this->assertTrue((new Premium)->isGuildPremium(self::GUILD));
        Livewire::test(PremiumServers::class)->assertSee(__('profile.premium_active'));
    }

    public function test_only_free_limit_streamers_are_announced_without_premium(): void
    {
        config(['toxbot.streams.free_limit' => 1]);
        $this->guild();
        $other = StreamSource::create(['platform' => 'twitch', 'external_id' => '1', 'login' => 'first', 'name' => 'First']);
        StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $other->id]);
        $source = $this->twitchSource();
        StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);

        $payload = ['subscription' => ['type' => 'stream.online'], 'event' => ['id' => 'stream-3', 'broadcaster_user_id' => '42', 'started_at' => now()->toIso8601String()]];
        $this->twitchRequest('notification', $payload)->assertNoContent();
        $this->assertCount(0, $this->discordPosts);

        config(['toxbot.premium_guilds' => [self::GUILD]]);
        $source->update(['live_item' => null]);
        $this->twitchRequest('notification', array_replace_recursive($payload, ['event' => ['id' => 'stream-4']]))->assertNoContent();
        $this->assertCount(1, $this->discordPosts);
    }

    public function test_premium_settings_are_only_saved_for_premium_servers(): void
    {
        $this->login();
        $source = $this->twitchSource();
        $subscription = StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);

        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->call('edit', $subscription->id)->set('filterGames', 'Valheim')->set('endAction', 'delete')->call('saveSubscription');
        $this->assertNull($subscription->fresh()->filter_games);

        config(['toxbot.premium_guilds' => [self::GUILD]]);
        Livewire::test(StreamList::class, ['guildId' => self::GUILD])
            ->call('edit', $subscription->id)->set('filterGames', 'Valheim')->set('endAction', 'delete')->call('saveSubscription');
        $this->assertSame('Valheim', $subscription->fresh()->filter_games);
        $this->assertSame('delete', $subscription->fresh()->end_action);
    }
}
