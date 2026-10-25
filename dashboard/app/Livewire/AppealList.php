<?php

namespace App\Livewire;

use App\Models\Guild;
use App\Models\ModAction;
use App\Models\UnbanAppeal;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Support\BotLocale;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class AppealList extends Component
{
    #[Locked]
    public string $guildId;

    #[Url(except: 'pending')]
    public string $filter = 'pending';

    public array $responses = [];

    public function mount(string $guildId): void
    {
        $this->guildId = $guildId;
    }

    public function toggleEnabled(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);

        $row = Guild::forDiscordId($this->guildId);
        $row->unban_enabled = ! $row->unban_enabled;
        $row->rememberLanguage();
        $row->save();
    }

    public function accept(int $id): void
    {
        $this->handle($id, 'accepted');
    }

    public function reject(int $id): void
    {
        $this->handle($id, 'rejected');
    }

    public function render()
    {
        return view('livewire.appeal-list', [
            'appeals' => UnbanAppeal::query()->forGuild($this->guildId)
                ->when($this->filter === 'pending', fn ($query) => $query->where('status', 'pending'))
                ->latest('id')
                ->limit(50)
                ->get(),
            'enabled' => (bool) Guild::query()->find($this->guildId)?->unban_enabled,
        ]);
    }

    private function handle(int $id, string $status): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);

        $this->validate(["responses.{$id}" => ['nullable', 'string', 'max:1000']]);

        $appeal = UnbanAppeal::query()->forGuild($this->guildId)->where('status', 'pending')->findOrFail($id);
        $response = trim((string) ($this->responses[$id] ?? '')) ?: null;
        $user = auth()->user();

        $appeal->update([
            'status' => $status,
            'handled_by' => (string) $user->id,
            'handled_by_name' => $user->username,
            'response' => $response,
            'handled_at' => now(),
        ]);

        if ($status === 'accepted') {
            ModAction::create([
                'guild' => $this->guildId,
                'type' => 'unban',
                'user_id' => $appeal->user_id,
                'moderator_id' => (string) $user->id,
                'moderator_name' => $user->username,
                'reason' => trim(__('tickets.appeal_case_reason', [], Guild::forDiscordId($this->guildId)->messageLocale()).' '.($response ?? '')),
            ]);
        }

        $this->notifyUser($appeal);
        unset($this->responses[$id]);
    }

    private function notifyUser(UnbanAppeal $appeal): void
    {
        $guildName = rescue(fn () => app(Discord::class)->guildInfo($this->guildId)['name'] ?? null, null, report: false);
        $locale = App::getLocale();
        App::setLocale(BotLocale::forUser($appeal->user_id));

        $payload = [
            'embeds' => [[
                'color' => $appeal->status === 'accepted' ? 0x57F287 : 0xED4245,
                'description' => __('tickets.appeal_dm_'.$appeal->status, ['server' => $guildName ?? $this->guildId]),
                'fields' => $appeal->response
                    ? [['name' => __('tickets.appeal_response'), 'value' => $appeal->response, 'inline' => false]]
                    : [],
            ]],
            'allowed_mentions' => ['parse' => []],
        ];
        App::setLocale($locale);

        rescue(fn () => app(Discord::class)->sendDirectMessage($appeal->user_id, $payload), report: false);
    }
}
