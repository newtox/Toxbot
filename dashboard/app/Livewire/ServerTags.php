<?php

namespace App\Livewire;

use App\Models\ServerTag;
use App\Models\Tag;
use App\Services\GuildAccess;
use App\Services\Premium;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ServerTags extends Component
{
    #[Locked]
    public string $guildId;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $content = '';

    public function mount(string $guildId): void
    {
        $this->guildId = $guildId;
    }

    public function save(): void
    {
        $this->authorizeGuild();

        $this->name = strtolower(trim($this->name));

        $this->validate([
            'name' => [
                'required',
                'regex:'.Tag::NAME_REGEX,
                Rule::unique('server_tags', 'name')->where('guild', $this->guildId)->ignore($this->editingId),
            ],
            'content' => ['required', 'string', 'max:'.Tag::MAX_LENGTH],
        ], [
            'name.regex' => __('tags.name_invalid'),
            'name.unique' => __('guild.server_tag_name_taken'),
            'content.required' => __('tags.content_empty'),
            'content.max' => __('tags.content_max', ['max' => Tag::MAX_LENGTH]),
        ]);

        $fields = ['name' => $this->name, 'content' => trim($this->content)];

        if ($this->editingId !== null) {
            ServerTag::query()->where('guild', $this->guildId)->whereKey($this->editingId)->update($fields);
        } else {
            $limit = $this->limit();
            if (ServerTag::query()->where('guild', $this->guildId)->count() >= $limit) {
                $this->addError('name', __('guild.server_tags_limit', ['max' => $limit]));

                return;
            }
            ServerTag::create(['guild' => $this->guildId, 'created_by' => (string) auth()->id()] + $fields);
        }

        $this->cancel();
        $this->dispatch('saved');
    }

    public function edit(int $id): void
    {
        $this->authorizeGuild();
        $tag = ServerTag::query()->where('guild', $this->guildId)->findOrFail($id);

        $this->editingId = $tag->id;
        $this->name = $tag->name;
        $this->content = $tag->content;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'content');
        $this->resetValidation();
    }

    public function remove(int $id): void
    {
        $this->authorizeGuild();
        ServerTag::query()->where('guild', $this->guildId)->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->cancel();
        }
    }

    public function render()
    {
        return view('livewire.server-tags', [
            'tags' => ServerTag::query()->where('guild', $this->guildId)->orderBy('name')->get(),
            'limit' => $this->limit(),
            'premium' => app(Premium::class)->isGuildPremium($this->guildId),
            'premiumLimit' => ServerTag::PREMIUM_LIMIT,
            'maxLength' => Tag::MAX_LENGTH,
        ]);
    }

    private function limit(): int
    {
        return app(Premium::class)->isGuildPremium($this->guildId) ? ServerTag::PREMIUM_LIMIT : ServerTag::FREE_LIMIT;
    }

    private function authorizeGuild(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);
    }
}
