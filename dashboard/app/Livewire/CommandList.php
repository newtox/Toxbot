<?php

namespace App\Livewire;

use App\Services\Discord;
use App\Support\CommandCatalog;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\Url;
use Livewire\Component;

class CommandList extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function render(Discord $discord)
    {
        $locale = config('app.locales.'.app()->getLocale().'.discord', 'en-US');

        try {
            $catalog = CommandCatalog::build($discord->applicationCommands(), $locale);
            $error = null;
        } catch (RequestException $e) {
            report($e);
            $catalog = [];
            $error = __('info.commands_error');
        }

        $commands = CommandCatalog::search($catalog, $this->search);

        return view('livewire.command-list', [
            'commands' => $commands,
            'total' => count($catalog),
            'error' => $error,
        ]);
    }
}
