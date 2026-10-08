<?php

namespace App\Livewire;

use App\Models\BotUser;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProfileSettings extends Component
{
    public string $language = BotUser::DEFAULT_LANGUAGE;

    public string $color = BotUser::DEFAULT_COLOR;

    public function mount(): void
    {
        $this->loadFromDatabase();
    }

    public function loadFromDatabase(): void
    {
        $settings = auth()->user()->botSettings();

        $this->language = array_key_exists((string) $settings->language, BotUser::languages())
            ? $settings->language
            : BotUser::DEFAULT_LANGUAGE;
        $this->color = (string) ($settings->color ?: BotUser::DEFAULT_COLOR);

        $this->resetValidation();
    }

    public function discard(): void
    {
        $this->loadFromDatabase();
    }

    public function save(): void
    {
        $this->validate([
            'language' => ['required', Rule::in(array_keys(BotUser::languages()))],
            'color' => ['required', 'regex:'.BotUser::COLOR_REGEX],
        ], [
            'color.regex' => __('profile.color_invalid'),
        ]);

        $settings = auth()->user()->botSettings();
        $languageChanged = $settings->language !== $this->language;

        $color = strtolower($this->color);
        if (strlen($color) === 4) {
            $color = '#'.$color[1].$color[1].$color[2].$color[2].$color[3].$color[3];
        }

        $settings->fill(['language' => $this->language, 'color' => $color])->save();

        if ($languageChanged) {
            session()->flash('saved', true);
            $this->redirectRoute('profile');

            return;
        }

        $this->loadFromDatabase();
        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.profile-settings', [
            'languages' => BotUser::languages(),
            'user' => auth()->user(),
        ]);
    }
}
