<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureBotOwner;
use App\Models\BlacklistEntry;
use App\Models\TagReport;
use Livewire\Component;

class AdminReports extends Component
{
    public bool $showResolved = false;

    public function mount(): void
    {
        abort_unless(EnsureBotOwner::check(auth()->id()), 403);
    }

    public function dismiss(int $reportId): void
    {
        $this->authorizeOwner();
        TagReport::query()->whereKey($reportId)->update(['resolved' => true]);
    }

    public function deleteTag(int $reportId, bool $blacklistOwner = false): void
    {
        $this->authorizeOwner();

        $report = TagReport::with('tag')->findOrFail($reportId);
        $tag = $report->tag;

        if ($blacklistOwner && ! BlacklistEntry::query()->where('user', $tag->owner)->exists()) {
            BlacklistEntry::create(['user' => $tag->owner]);
        }

        $tag->delete();
        $this->dispatch('saved');
    }

    private function authorizeOwner(): void
    {
        abort_unless(EnsureBotOwner::check(auth()->id()), 403);
    }

    public function render()
    {
        return view('livewire.admin-reports', [
            'reports' => TagReport::with('tag')
                ->when(! $this->showResolved, fn ($q) => $q->where('resolved', false))
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }
}
