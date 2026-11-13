<?php

namespace App\Livewire;

use App\Models\Guild;
use App\Services\BotProfile as Profiles;
use App\Services\GuildAccess;
use App\Services\Premium;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class BotProfile extends Component
{
    use WithFileUploads;

    private const IMAGE_RULES = ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:8192'];

    #[Locked]
    public string $guildId;

    public $avatar;

    public $banner;

    public string $bio = '';

    public ?array $avatarCrop = null;

    public ?array $bannerCrop = null;

    public int $nameFont = 11;

    public int $nameEffect = 1;

    public array $nameColors = ['#ff6b33', '#ff3366', '#ffd166', '#06d6a0', '#5865f2'];

    public int $nameColorCount = 3;

    public function mount(string $guildId): void
    {
        $this->guildId = $guildId;
        $guild = Guild::forDiscordId($guildId);
        $this->bio = (string) $guild->bot_bio;
        if ($guild->bot_name_style !== null) {
            $this->nameFont = (int) $guild->bot_name_style['font'];
            $this->nameEffect = (int) $guild->bot_name_style['effect'];
            $colors = array_slice($guild->bot_name_style['colors'], 0, 5);
            $this->nameColors = array_replace($this->nameColors, $colors);
            $this->nameColorCount = max(2, count($colors));
        }
    }

    public function save(Profiles $profiles): void
    {
        $this->authorizePremium();

        $this->bio = trim($this->bio);
        $this->validate([
            'avatar' => self::IMAGE_RULES,
            'banner' => self::IMAGE_RULES,
            'bio' => ['string', 'max:'.Profiles::BIO_MAX],
            'avatarCrop' => ['nullable', 'array', 'size:4'],
            'avatarCrop.*' => ['numeric', 'between:0,1'],
            'bannerCrop' => ['nullable', 'array', 'size:4'],
            'bannerCrop.*' => ['numeric', 'between:0,1'],
            'nameFont' => ['required', 'integer', Rule::in(Profiles::FONTS)],
            'nameEffect' => ['required', 'integer', Rule::in(array_keys(Profiles::EFFECTS))],
            'nameColorCount' => ['required', 'integer', 'between:2,5'],
            'nameColors' => ['array', 'size:5'],
            'nameColors.*' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $changes = ['bio' => $this->bio];
        $style = $this->nameStyle();
        if ($style !== Guild::forDiscordId($this->guildId)->bot_name_style) {
            $changes['style'] = $style;
        }
        foreach (['avatar', 'banner'] as $key) {
            if ($this->{$key}) {
                $changes[$key] = ['file' => $this->{$key}, 'crop' => $this->{$key.'Crop'} !== null ? array_map('floatval', array_values($this->{$key.'Crop'})) : null];
            }
        }

        if ($this->apply($profiles, $changes)) {
            $this->reset('avatar', 'banner', 'avatarCrop', 'bannerCrop');
            $this->dispatch('bot-profile-saved');
        }
    }

    public function remove(string $key, Profiles $profiles): void
    {
        $this->authorizePremium();
        abort_unless(in_array($key, ['avatar', 'banner'], true), 404);

        $this->apply($profiles, [$key => null]);
    }

    public function render(Profiles $profiles)
    {
        return view('livewire.bot-profile', [
            'premium' => app(Premium::class)->isGuildPremium($this->guildId),
            'current' => $profiles->current($this->guildId),
            'bioMax' => Profiles::BIO_MAX,
            'fonts' => Profiles::FONTS,
            'effects' => Profiles::EFFECTS,
        ]);
    }

    private function nameStyle(): ?array
    {
        if ($this->nameFont === Profiles::DEFAULT_STYLE['font'] && $this->nameEffect === Profiles::DEFAULT_STYLE['effect']) {
            return null;
        }

        return [
            'font' => $this->nameFont,
            'effect' => $this->nameEffect,
            'colors' => array_map('strtolower', array_slice($this->nameColors, 0, $this->colorCount())),
        ];
    }

    private function colorCount(): int
    {
        [$min, $max] = Profiles::COLORS[$this->nameEffect];

        return max($min, min($max, $this->nameColorCount));
    }

    private function apply(Profiles $profiles, array $changes): bool
    {
        try {
            $profiles->update(Guild::forDiscordId($this->guildId), $changes);
        } catch (RequestException $e) {
            $status = $e->response->status();
            $this->addError('profile', match (true) {
                $status === 429 => __('botprofile.rate_limited'),
                $status === 403 => __('botprofile.forbidden'),
                default => __('botprofile.failed', ['message' => (string) ($e->response->json('message') ?? $status)]),
            });

            return false;
        }

        $this->dispatch('saved');

        return true;
    }

    private function authorizePremium(): void
    {
        abort_if(app(GuildAccess::class)->find(Auth::user(), $this->guildId) === null, 403);
        abort_unless(app(Premium::class)->isGuildPremium($this->guildId), 403);
    }
}
