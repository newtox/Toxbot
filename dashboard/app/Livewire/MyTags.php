<?php

namespace App\Livewire;

use App\Models\BlacklistEntry;
use App\Models\BotUser;
use App\Models\Tag;
use App\Services\Premium;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MyTags extends Component
{
    #[Locked]
    public ?int $editingId = null;

    public bool $formOpen = false;

    public string $name = '';

    public string $content = '';

    public bool $embed = false;

    public string $embedColor = '';

    public string $embedImage = '';

    public function create(): void
    {
        $this->resetForm();
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        $tag = $this->ownTag($id);

        $this->resetValidation();
        $this->editingId = $tag->id;
        $this->name = $tag->name;
        $this->content = $tag->content;
        $this->embed = $tag->embed;
        $this->embedColor = (string) $tag->embed_color;
        $this->embedImage = (string) $tag->embed_image;
        $this->formOpen = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(Premium $premium): void
    {
        $userId = (string) auth()->id();
        $isPremium = $premium->isPremium($userId);
        $creating = $this->editingId === null;

        if ($creating) {
            $this->name = strtolower(trim($this->name));
        }

        $this->validate([
            'name' => $creating
                ? ['required', 'regex:'.Tag::NAME_REGEX, Rule::unique('tags', 'name')]
                : ['nullable'],
            'content' => ['required', 'string', 'max:'.Tag::MAX_LENGTH],
            'embedColor' => ['nullable', 'regex:'.BotUser::COLOR_REGEX],
            'embedImage' => ['nullable', 'url:https', 'max:512'],
        ], [
            'name.regex' => __('tags.name_invalid'),
            'name.unique' => __('tags.name_taken'),
            'content.required' => __('tags.content_empty'),
            'content.max' => __('tags.content_max', ['max' => Tag::MAX_LENGTH]),
            'embedColor.regex' => __('profile.color_invalid'),
            'embedImage.url' => __('tags.image_invalid', ['hosts' => implode(', ', config('toxbot.tags.image_hosts'))]),
        ]);

        if ($problem = Tag::contentProblem($this->content)) {
            throw ValidationException::withMessages(['content' => __($problem)]);
        }

        if ($this->embedImage !== '' && ! in_array(strtolower((string) parse_url($this->embedImage, PHP_URL_HOST)), config('toxbot.tags.image_hosts'), true)) {
            throw ValidationException::withMessages(['embedImage' => __('tags.image_invalid', ['hosts' => implode(', ', config('toxbot.tags.image_hosts'))])]);
        }

        $embedFields = $isPremium ? [
            'embed' => $this->embed,
            'embed_color' => $this->embedColor !== '' ? strtolower($this->embedColor) : null,
            'embed_image' => $this->embedImage !== '' ? $this->embedImage : null,
        ] : [];

        if ($creating) {
            if (BlacklistEntry::query()->where('user', $userId)->exists()) {
                throw ValidationException::withMessages(['name' => __('tags.blacklisted')]);
            }

            $limit = $this->limit($isPremium);
            if (Tag::query()->where('owner', $userId)->count() >= $limit) {
                throw ValidationException::withMessages(['name' => __('tags.limit', ['limit' => $limit])]);
            }

            Tag::create(['name' => $this->name, 'owner' => $userId, 'content' => $this->content] + $embedFields);
        } else {
            $this->ownTag($this->editingId)->update(['content' => $this->content] + $embedFields);
        }

        $this->resetForm();
        $this->dispatch('saved');
    }

    public function delete(int $id): void
    {
        $this->ownTag($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    private function ownTag(int $id): Tag
    {
        return Tag::query()->where('owner', (string) auth()->id())->findOrFail($id);
    }

    private function limit(bool $premium): int
    {
        return $premium ? config('toxbot.tags.premium_limit') : config('toxbot.tags.free_limit');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'formOpen', 'name', 'content', 'embed', 'embedColor', 'embedImage']);
        $this->resetValidation();
    }

    public function render(Premium $premium)
    {
        $isPremium = $premium->isPremium((string) auth()->id());

        return view('livewire.my-tags', [
            'tags' => Tag::query()->where('owner', (string) auth()->id())->orderBy('name')->get(),
            'limit' => $this->limit($isPremium),
            'premium' => $isPremium,
        ]);
    }
}
