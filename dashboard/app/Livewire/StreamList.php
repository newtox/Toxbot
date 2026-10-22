<?php

namespace App\Livewire;

use App\Models\StreamSource;
use App\Models\StreamSubscription;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Services\Premium;
use App\Services\Streams\SourceManager;
use App\Services\Streams\Twitch;
use App\Services\Streams\YouTube;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class StreamList extends Component
{
    #[Locked]
    public string $guildId;

    public string $platform = StreamSource::TWITCH;

    public string $input = '';

    #[Locked]
    public ?int $editingId = null;

    public string $customMessage = '';

    public string $videoMessage = '';

    public string $pingRole = '';

    public string $filterGames = '';

    public string $filterTitle = '';

    public string $endAction = 'keep';

    public bool $notifyVideos = true;

    public bool $notifyLive = true;

    #[Locked]
    public ?string $error = null;

    public function mount(string $guildId): void
    {
        $this->guildId = $guildId;
    }

    public function add(SourceManager $manager): void
    {
        $this->authorizeGuild();
        $this->error = null;

        $this->validate([
            'platform' => ['required', Rule::in([StreamSource::TWITCH, StreamSource::YOUTUBE])],
            'input' => ['required', 'string', 'max:200'],
        ], ['input.required' => __('streams.input_required')]);

        try {
            $manager->add($this->guildId, $this->platform, $this->input);
            $this->input = '';
        } catch (RuntimeException $e) {
            $this->error = str_starts_with($e->getMessage(), 'streams.')
                ? __($e->getMessage(), ['limit' => $manager->limit($this->guildId)])
                : __('streams.platform_error');
        }
    }

    public function remove(int $id, SourceManager $manager): void
    {
        $this->authorizeGuild();
        $manager->remove($this->subscription($id));

        if ($this->editingId === $id) {
            $this->editingId = null;
        }
    }

    public function edit(int $id): void
    {
        $subscription = $this->subscription($id);

        $this->resetValidation();
        $this->editingId = $id;
        $this->customMessage = (string) $subscription->message;
        $this->videoMessage = (string) $subscription->video_message;
        $this->pingRole = (string) $subscription->role;
        $this->filterGames = (string) $subscription->filter_games;
        $this->filterTitle = (string) $subscription->filter_title;
        $this->endAction = $subscription->end_action;
        $this->notifyVideos = $subscription->notify_videos;
        $this->notifyLive = $subscription->notify_live;
    }

    public function cancel(): void
    {
        $this->editingId = null;
        $this->resetValidation();
    }

    public function saveSubscription(Premium $premium, Discord $discord): void
    {
        $this->authorizeGuild();
        $subscription = $this->subscription($this->editingId);

        $this->validate([
            'customMessage' => ['nullable', 'string', 'max:2000'],
            'videoMessage' => ['nullable', 'string', 'max:2000'],
            'pingRole' => ['nullable', Rule::in(array_column($this->roles($discord), 'id'))],
            'filterGames' => ['nullable', 'string', 'max:500'],
            'filterTitle' => ['nullable', 'string', 'max:200'],
            'endAction' => ['required', Rule::in(StreamSubscription::END_ACTIONS)],
        ], ['pingRole.in' => __('guild.role_invalid')]);

        $fields = ['notify_videos' => $this->notifyVideos, 'notify_live' => $this->notifyLive];

        if ($premium->isGuildPremium($this->guildId)) {
            $fields += [
                'message' => $this->customMessage !== '' ? $this->customMessage : null,
                'video_message' => $this->videoMessage !== '' ? $this->videoMessage : null,
                'role' => $this->pingRole !== '' ? $this->pingRole : null,
                'filter_games' => $this->filterGames !== '' ? $this->filterGames : null,
                'filter_title' => $this->filterTitle !== '' ? $this->filterTitle : null,
                'end_action' => $this->endAction,
            ];
        }

        $subscription->update($fields);
        $this->editingId = null;
        $this->dispatch('saved');
    }

    private function subscription(?int $id): StreamSubscription
    {
        return StreamSubscription::query()->where('guild', $this->guildId)->findOrFail($id);
    }

    private function roles(Discord $discord): array
    {
        $roles = rescue(fn () => $discord->guildRoles($this->guildId), [], report: false);

        return collect($roles)
            ->reject(fn ($r) => $r['id'] === $this->guildId || ($r['managed'] ?? false))
            ->sortByDesc('position')
            ->map(fn ($r) => ['id' => $r['id'], 'name' => $r['name']])
            ->values()
            ->all();
    }

    private function authorizeGuild(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);
    }

    public function render(Premium $premium, SourceManager $manager, Twitch $twitch, YouTube $youtube, Discord $discord)
    {
        return view('livewire.stream-list', [
            'subscriptions' => StreamSubscription::query()->with('source')->where('guild', $this->guildId)->oldest()->get(),
            'premium' => $isPremium = $premium->isGuildPremium($this->guildId),
            'activeIds' => $isPremium ? null : StreamSubscription::freeIds($this->guildId),
            'limit' => $manager->limit($this->guildId),
            'roles' => $this->editingId ? $this->roles($discord) : [],
            'twitchReady' => $twitch->configured() && strlen((string) config('toxbot.streams.webhook_secret')) >= 10,
            'youtubeReady' => strlen((string) config('toxbot.streams.webhook_secret')) >= 10,
            'youtubeHandles' => $youtube->hasApiKey(),
        ]);
    }
}
