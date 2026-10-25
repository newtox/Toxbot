<?php

namespace Tests\Feature;

use App\Livewire\AppealList;
use App\Livewire\GuildSettings;
use App\Livewire\TicketTypes;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\ModAction;
use App\Models\ModCase;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketType;
use App\Models\UnbanAppeal;
use App\Support\Timezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TicketsTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const OTHER_GUILD = '222222222222222222';

    private const BOT = '999999999999999999';

    private const USER = '123456789012345678';

    private bool $banned = true;

    private string $banReason = 'Spam';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => false, 'permissions' => (string) 0x20],
                ]),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me/channels') => Http::response(['id' => '800']),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => self::BOT, 'username' => 'Toxbot', 'avatar' => null]),
                str_contains($url, '/bans/') => $this->banned ? Http::response(['reason' => $this->banReason]) : Http::response([], 404),
                str_contains($url, '/messages/') => Http::response(['id' => '700']),
                str_contains($url, '/messages') => Http::response(['id' => '700']),
                str_contains($url, '/channels') => Http::response([
                    ['id' => '10', 'type' => 4, 'name' => 'Support', 'position' => 0],
                    ['id' => '11', 'type' => 0, 'name' => 'tickets', 'position' => 0, 'parent_id' => '10'],
                    ['id' => '12', 'type' => 0, 'name' => 'ticket-log', 'position' => 1, 'parent_id' => '10'],
                ]),
                str_contains($url, '/roles') => Http::response([
                    ['id' => self::GUILD, 'name' => '@everyone', 'position' => 0, 'color' => 0, 'managed' => false],
                    ['id' => '30', 'name' => 'Support-Team', 'position' => 1, 'color' => 0, 'managed' => false],
                    ['id' => '21', 'name' => 'Toxbot', 'position' => 3, 'color' => 0, 'managed' => true],
                ]),
                str_contains($url, '/members/') => Http::response(['roles' => ['21']]),
                str_contains($url, '/guilds/'.self::GUILD) => Http::response(['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null]),
                default => Http::response([], 404),
            };
        });
    }

    private function login(): DashboardUser
    {
        $user = DashboardUser::create(['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'user-token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);

        return $user;
    }

    private function settings()
    {
        return Livewire::test(GuildSettings::class, [
            'guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null],
            'section' => 'tickets',
        ]);
    }

    private function ticket(array $attributes = []): Ticket
    {
        $ticket = new Ticket;
        $ticket->forceFill($attributes + [
            'guild' => self::GUILD,
            'number' => 1,
            'type_name' => 'Support',
            'user_id' => '555',
            'user_name' => 'kira',
            'channel_id' => '900',
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by_name' => 'newtox',
        ])->save();

        return $ticket;
    }

    public function test_saves_ticket_settings(): void
    {
        $this->login();

        $this->settings()
            ->set('ticketCategory', '10')
            ->set('ticketLogChannel', '12')
            ->set('ticketPanelChannel', '11')
            ->set('ticketLimit', 2)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(AppealList::class, ['guildId' => self::GUILD])
            ->assertDontSee(route('appeals.show', self::GUILD))
            ->call('toggleEnabled')
            ->assertSee(route('appeals.show', self::GUILD));

        $row = Guild::find(self::GUILD);
        $this->assertSame('10', $row->ticket_category);
        $this->assertSame('12', $row->ticket_log_channel);
        $this->assertSame(2, $row->ticket_limit);
        $this->assertTrue($row->unban_enabled);

        $this->settings()
            ->set('ticketCategory', '11')
            ->set('ticketLimit', 20)
            ->call('save')
            ->assertHasErrors(['ticketCategory', 'ticketLimit']);
    }

    public function test_forgets_channels_that_were_deleted_in_discord(): void
    {
        $this->login();
        Guild::create(['id' => self::GUILD, 'ticket_category' => '98', 'ticket_panel_channel' => '99', 'ticket_log_channel' => '12', 'ticket_panel_message_id' => '700']);

        $this->settings()->call('save')->assertHasNoErrors();

        $row = Guild::find(self::GUILD);
        $this->assertNull($row->ticket_category);
        $this->assertNull($row->ticket_panel_channel);
        $this->assertSame('12', $row->ticket_log_channel);

        $this->settings()
            ->set('ticketCategory', '10')
            ->set('ticketPanelChannel', '11')
            ->call('postTicketPanel')
            ->assertHasNoErrors()
            ->assertSet('ticketNotice', __('tickets.panel_needs_type'));
    }

    public function test_manages_ticket_types(): void
    {
        $this->login();
        $roles = [['id' => '30', 'name' => 'Support-Team', 'color' => null]];

        $component = Livewire::test(TicketTypes::class, ['guildId' => self::GUILD, 'roles' => $roles])
            ->set('name', 'Support')
            ->set('emoji', '🎫')
            ->set('description', 'Beschreib dein Problem.')
            ->set('teamRoles', ['30'])
            ->call('save')
            ->assertHasNoErrors();

        $type = TicketType::first();
        $this->assertSame(['30'], $type->roleIds());
        $this->assertSame('🎫', $type->emoji);

        $component->call('edit', $type->id)->set('name', 'Hilfe')->set('emoji', '<:ticket:123456789012345678>')->call('save')->assertHasNoErrors();
        $this->assertSame('Hilfe', $type->fresh()->name);

        $component->set('name', 'X')->set('emoji', 'not an emoji')->call('save')->assertHasErrors(['emoji']);
        $component->set('emoji', ':ticket:')->call('save')->assertHasErrors(['emoji']);
        $component->set('emoji', '')->set('teamRoles', ['999'])->call('save')->assertHasErrors(['teamRoles.0']);

        foreach (range(2, 5) as $i) {
            TicketType::create(['guild' => self::GUILD, 'name' => "Typ {$i}"]);
        }
        $component->set('teamRoles', [])->set('name', 'Zu viel')->call('save')->assertHasErrors(['name']);

        $component->call('remove', $type->id);
        $this->assertSame(4, TicketType::count());
    }

    public function test_posts_the_ticket_panel_with_one_button_per_type(): void
    {
        $this->login();

        $this->settings()->call('postTicketPanel')->assertSet('ticketNotice', __('tickets.panel_needs_category'));
        $this->settings()->set('ticketCategory', '10')->call('postTicketPanel')->assertSet('ticketNotice', __('tickets.panel_needs_channel'));
        $this->settings()->set('ticketPanelChannel', '11')->call('postTicketPanel')->assertSet('ticketNotice', __('tickets.panel_needs_type'));
        $this->assertSame('11', Guild::find(self::GUILD)->ticket_panel_channel);

        $support = TicketType::create(['guild' => self::GUILD, 'name' => 'Support', 'emoji' => '🎫']);
        $report = TicketType::create(['guild' => self::GUILD, 'name' => 'Meldung', 'emoji' => '<a:alarm:123456789012345678>']);

        $this->settings()->call('postTicketPanel')->assertSet('ticketNotice', __('tickets.panel_done'));
        $this->assertSame('700', Guild::find(self::GUILD)->ticket_panel_message_id);

        Http::assertSent(function (Request $r) use ($support, $report) {
            if ($r->method() !== 'POST' || ! str_ends_with($r->url(), '/channels/11/messages')) {
                return false;
            }
            $buttons = $r['components'][0]['components'];

            return $buttons[0]['custom_id'] === 'ticket_open:'.$support->id
                && $buttons[0]['emoji'] === ['name' => '🎫']
                && $buttons[1]['custom_id'] === 'ticket_open:'.$report->id
                && $buttons[1]['emoji'] === ['id' => '123456789012345678', 'name' => 'alarm', 'animated' => true];
        });
    }

    public function test_posts_in_the_server_language_and_saves_it(): void
    {
        $this->login();
        app()->setLocale('de');

        Livewire::test(GuildSettings::class, [
            'guild' => ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon_url' => null],
            'section' => 'language',
        ])->assertSet('language', 'de_de')->set('language', 'fr_fr')->call('save')->assertHasNoErrors();
        $this->assertSame('fr_fr', Guild::find(self::GUILD)->language);

        Guild::query()->whereKey(self::GUILD)->update(['ticket_category' => '10', 'ticket_panel_channel' => '11']);
        TicketType::create(['guild' => self::GUILD, 'name' => 'Support']);
        $this->settings()->call('postTicketPanel')->assertSet('ticketNotice', __('tickets.panel_done'));

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/channels/11/messages')
            && $r['embeds'][0]['title'] === __('tickets.panel_title', [], 'fr'));
        $this->assertNotSame(__('tickets.panel_title', [], 'fr'), __('tickets.panel_title', [], 'de'));
    }

    public function test_first_save_remembers_the_dashboard_language(): void
    {
        $this->login();
        app()->setLocale('ja');

        $this->settings()->set('ticketLimit', 2)->call('save')->assertHasNoErrors();

        $this->assertSame('ja_jp', Guild::find(self::GUILD)->language);
    }

    public function test_ticket_history_and_transcript(): void
    {
        $this->login();
        $ticket = $this->ticket();
        $message = new TicketMessage;
        $message->forceFill([
            'ticket_id' => $ticket->id,
            'author_id' => '555',
            'author_name' => 'kira',
            'content' => 'Hallo, ich brauche Hilfe.',
            'attachments' => [['name' => 'bild.png', 'url' => 'https://cdn.discordapp.com/x/bild.png']],
            'sent_at' => now(),
        ])->save();
        $foreign = $this->ticket(['guild' => self::OTHER_GUILD, 'number' => 2]);

        $this->get('/servers/'.self::GUILD.'/ticket-history')->assertOk()->assertSee('kira')->assertSee('#0001');
        $this->get('/servers/'.self::GUILD.'/ticket-history?status=open')->assertOk()->assertSee(__('tickets.history_empty'));
        $this->get('/servers/'.self::GUILD.'/ticket-history/'.$ticket->id)
            ->assertOk()
            ->assertSee('Hallo, ich brauche Hilfe.')
            ->assertSee('bild.png');
        $this->get('/servers/'.self::GUILD.'/ticket-history/'.$foreign->id)->assertNotFound();
    }

    public function test_appeal_page_states_and_submission(): void
    {
        $this->get('/appeal/'.self::GUILD)->assertRedirect(route('login'));

        $this->login();
        $this->get('/appeal/'.self::GUILD)->assertOk()->assertSee(__('tickets.appeal_disabled'));

        Guild::create(['id' => self::GUILD, 'unban_enabled' => true, 'ticket_log_channel' => '12']);
        $this->get('/appeal/'.self::GUILD)->assertOk()->assertSee(__('tickets.appeal_send'))->assertSee('Spam');

        $this->post('/appeal/'.self::GUILD, ['reason' => 'zu kurz'])->assertSessionHasErrors('reason');

        $this->post('/appeal/'.self::GUILD, ['reason' => 'Ich habe meinen Account zurück und werde mich an die Regeln halten.'])
            ->assertOk()
            ->assertSee(__('tickets.appeal_sent'));
        $this->assertSame('pending', UnbanAppeal::first()->status);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/channels/12/messages')
            && $r['embeds'][0]['url'] === route('servers.appeals', self::GUILD));

        $this->get('/appeal/'.self::GUILD)->assertSee(__('tickets.appeal_pending', ['date' => Timezone::format(UnbanAppeal::first()->created_at, 'll')]));

        UnbanAppeal::first()->update(['status' => 'rejected', 'handled_at' => now(), 'response' => 'Nein.']);
        $this->get('/appeal/'.self::GUILD)->assertSee('Nein.');

        $this->banned = false;
        $this->get('/appeal/'.self::GUILD)->assertSee(__('tickets.appeal_not_banned', ['server' => 'Toxic Lounge']));
    }

    public function test_appeal_page_shows_the_ban_reason_without_the_moderator(): void
    {
        $this->login();
        Guild::create(['id' => self::GUILD, 'unban_enabled' => true]);
        $this->banReason = 'newtox: Testban';

        $this->get('/appeal/'.self::GUILD)->assertSee('Testban')->assertDontSee('newtox: Testban');

        $case = new ModCase;
        $case->forceFill(['guild' => self::GUILD, 'number' => 1, 'type' => 'ban', 'user_id' => self::USER, 'moderator_id' => '1', 'reason' => 'Werbung im Chat', 'active' => true])->save();
        $this->get('/appeal/'.self::GUILD)->assertSee('Werbung im Chat')->assertDontSee('Testban');
    }

    public function test_accepting_an_appeal_queues_an_unban_and_sends_a_dm(): void
    {
        $this->login();
        Guild::create(['id' => self::GUILD, 'unban_enabled' => true]);
        $appeal = UnbanAppeal::create(['guild' => self::GUILD, 'user_id' => '555', 'user_name' => 'kira', 'reason' => 'Bitte entbannen, es tut mir leid.']);
        $other = UnbanAppeal::create(['guild' => self::GUILD, 'user_id' => '556', 'user_name' => 'jay', 'reason' => 'Ich war es nicht, bitte prüfen.']);

        Livewire::test(AppealList::class, ['guildId' => self::GUILD])
            ->assertSee('kira')
            ->set("responses.{$appeal->id}", 'Willkommen zurück.')
            ->call('accept', $appeal->id)
            ->call('reject', $other->id);

        $this->assertSame('accepted', $appeal->fresh()->status);
        $this->assertSame('Willkommen zurück.', $appeal->fresh()->response);
        $this->assertSame('rejected', $other->fresh()->status);

        $action = ModAction::sole();
        $this->assertSame('unban', $action->type);
        $this->assertSame('555', $action->user_id);
        $this->assertSame(self::USER, $action->moderator_id);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/users/@me/channels') && $r['recipient_id'] === '555');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/channels/800/messages')
            && str_contains($r['embeds'][0]['description'], 'Toxic Lounge'));
    }
}
