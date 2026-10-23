<?php

namespace App\Livewire;

use App\Services\DataDeletion;
use App\Services\GuildAccess;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ServerData extends Component
{
    #[Locked]
    public string $guildId;

    #[Locked]
    public string $guildName;

    public string $confirmName = '';

    public function mount(string $guildId, string $guildName): void
    {
        $this->guildId = $guildId;
        $this->guildName = $guildName;
    }

    public function resetSettings(DataDeletion $deletion): void
    {
        $this->guild();

        $deletion->resetGuild($this->guildId);

        session()->flash('status', __('data.reset_done'));
        $this->redirectRoute('servers.show', $this->guildId);
    }

    public function deleteServer(DataDeletion $deletion): void
    {
        abort_unless($this->guild()['owner'], 403);

        if (trim($this->confirmName) !== trim($this->guildName)) {
            $this->addError('confirmName', __('data.confirm_mismatch'));

            return;
        }

        try {
            $deletion->deleteGuild($this->guildId);
        } catch (RequestException $e) {
            Log::warning('Leaving a deleted server failed', ['guild' => $this->guildId, 'status' => $e->response->status()]);
            session()->flash('status', __('data.leave_failed', ['server' => $this->guildName]));
            $this->redirectRoute('servers.index');

            return;
        }

        session()->flash('status', __('data.delete_done', ['server' => $this->guildName]));
        $this->redirectRoute('servers.index');
    }

    public function render()
    {
        return view('livewire.server-data', ['owner' => (bool) ($this->guild()['owner'] ?? false)]);
    }

    private function guild(): array
    {
        $guild = app(GuildAccess::class)->find(auth()->user(), $this->guildId);
        abort_if($guild === null, 403);

        return $guild;
    }
}
