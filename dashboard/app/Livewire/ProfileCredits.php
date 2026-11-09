<?php

namespace App\Livewire;

use App\Livewire\Concerns\PicksMember;
use App\Models\BotUser;
use App\Services\Discord;
use App\Services\Economy;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class ProfileCredits extends Component
{
    use PicksMember;

    public string $amount = '';

    #[Locked]
    public ?string $message = null;

    #[Locked]
    public bool $success = false;

    public function claim(Economy $economy): void
    {
        $result = $economy->claimDaily((string) Auth::id());

        $this->success = $result['claimed'];
        $this->message = $result['claimed']
            ? __('credits.daily_claimed', ['amount' => self::credits($result['amount']), 'streak' => $result['streak']])
            : __('credits.daily_wait', ['time' => self::duration($result['wait'])]);

        if ($result['claimed']) {
            $this->dispatch('profile-changed');
        }
    }

    public function pay(Economy $economy, Discord $discord): void
    {
        $this->message = null;
        $this->validate(
            ['amount' => ['required', 'integer', 'min:1', 'max:1000000000']],
            ['amount.*' => __('credits.amount_invalid')],
        );

        $targetId = $this->pickedMemberId();
        if ($targetId === null) {
            $this->addError('picked', __('credits.pick_recipient'));

            return;
        }

        $amount = (int) $this->amount;
        try {
            $economy->transferCredits((string) Auth::id(), $targetId, $amount);
        } catch (RuntimeException $e) {
            $this->addError('amount', __($e->getMessage()));

            return;
        }

        $sender = Auth::user();
        $locale = self::localeOf($targetId);
        rescue(fn () => $discord->sendDirectMessage($targetId, [
            'allowed_mentions' => ['parse' => []],
            'embeds' => [[
                'color' => 0x6DBE33,
                'title' => __('credits.dm_title', [], $locale),
                'description' => __('credits.dm_text', ['user' => $sender->display_name, 'amount' => self::credits($amount)], $locale),
            ]],
        ]), report: false);

        $this->success = true;
        $this->message = __('credits.paid', ['amount' => self::credits($amount), 'name' => $this->picked['name']]);
        $this->amount = '';
        $this->resetPick();
        $this->dispatch('profile-changed');
    }

    public static function credits(int $amount): string
    {
        return number_format($amount, 0, '', "\u{00A0}")."\u{00A0}".config('toxbot.currency');
    }

    private static function duration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = max(1, intdiv($seconds % 3600, 60));

        return $hours > 0
            ? __('credits.hours_minutes', ['hours' => $hours, 'minutes' => $minutes])
            : __('credits.minutes', ['minutes' => $minutes]);
    }

    private static function localeOf(string $userId): string
    {
        $language = BotUser::query()->find($userId)?->language ?? BotUser::DEFAULT_LANGUAGE;

        return (string) (collect(config('app.locales'))->search(fn (array $locale) => $locale['bot'] === $language) ?: 'en');
    }

    public function render(Economy $economy)
    {
        $overview = $economy->creditsOverview((string) Auth::id());

        return view('livewire.profile-credits', [
            ...$this->pickerData(),
            'overview' => $overview,
            'nextDaily' => $overview['next_daily'] ? self::duration((int) now()->diffInSeconds($overview['next_daily'], true)) : null,
        ]);
    }
}
