<?php

namespace App\Services;

use App\Models\Guild;
use App\Models\TicketType;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SetupMessages
{
    public function __construct(private Discord $discord) {}

    public function ticketTypes(string $guildId): Collection
    {
        return TicketType::query()->where('guild', $guildId)->orderBy('position')->orderBy('id')->get();
    }

    public function postTicketPanel(Guild $row): void
    {
        $types = $this->ticketTypes($row->id);
        $row->rememberLanguage();
        $locale = $row->messageLocale();

        $payload = [
            'embeds' => [[
                'color' => 0x5865F2,
                'title' => __('tickets.panel_title', [], $locale),
                'description' => __('tickets.panel_text', [], $locale),
                'fields' => $types->map(fn (TicketType $type) => [
                    'name' => trim(($type->emoji && ! str_starts_with($type->emoji, '<') ? $type->emoji.' ' : '').$type->name),
                    'value' => Str::limit($type->description ?: "\u{200B}", 1000),
                    'inline' => false,
                ])->all(),
            ]],
            'components' => [[
                'type' => 1,
                'components' => $types->map(fn (TicketType $type) => array_filter([
                    'type' => 2,
                    'style' => 1,
                    'label' => Str::limit($type->name, 80, ''),
                    'custom_id' => 'ticket_open:'.$type->id,
                    'emoji' => $this->buttonEmoji($type->emoji),
                ]))->all(),
            ]],
            'allowed_mentions' => ['parse' => []],
        ];

        $messageId = $this->editOrSend($row->ticket_panel_channel, $row->ticket_panel_message_id, $payload);
        $row->forceFill(['ticket_panel_message_id' => $messageId])->save();
    }

    public function postVerifyMessage(Guild $row, string $guildName): void
    {
        $row->rememberLanguage();
        $locale = $row->messageLocale();
        $payload = [
            'embeds' => [[
                'color' => 0x6DBE33,
                'title' => __('protection.message_title', ['server' => $guildName], $locale),
                'description' => __('protection.message_text', [], $locale),
            ]],
            'components' => [[
                'type' => 1,
                'components' => [[
                    'type' => 2,
                    'style' => 5,
                    'label' => __('protection.message_button', [], $locale),
                    'url' => route('verify.show', $row->id),
                ]],
            ]],
            'allowed_mentions' => ['parse' => []],
        ];

        $messageId = $this->editOrSend($row->verify_channel, $row->verify_message_id, $payload);
        $row->forceFill(['verify_message_id' => $messageId])->save();
    }

    private function editOrSend(string $channelId, ?string $messageId, array $payload): string
    {
        if ($messageId) {
            try {
                $this->discord->editMessage($channelId, $messageId, $payload);

                return $messageId;
            } catch (RequestException $e) {
                if ($e->response->status() !== 404) {
                    throw $e;
                }
            }
        }

        return (string) $this->discord->sendMessage($channelId, $payload);
    }

    private function buttonEmoji(?string $emoji): ?array
    {
        if (! $emoji) {
            return null;
        }
        if (preg_match('/^<(a?):(\w+):(\d+)>$/', $emoji, $m)) {
            return ['id' => $m[3], 'name' => $m[2], 'animated' => $m[1] === 'a'];
        }

        return ['name' => $emoji];
    }
}
