<?php

namespace App\Livewire;

use App\Services\DataDeletion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteAccount extends Component
{
    public string $confirmName = '';

    public function delete(DataDeletion $deletion): void
    {
        $user = auth()->user();

        if (trim($this->confirmName) !== $user->username) {
            $this->addError('confirmName', __('data.confirm_mismatch'));

            return;
        }

        $deletion->deleteUser($user);

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('status', __('data.account_deleted'));

        $this->redirectRoute('home');
    }

    public function render()
    {
        return view('livewire.delete-account');
    }
}
