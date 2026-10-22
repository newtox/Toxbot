<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureBotOwner;
use App\Models\Background;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class AdminBackgrounds extends Component
{
    use WithFileUploads;

    public $upload;

    public string $newName = '';

    public int $newPrice = 500;

    public bool $newPremium = false;

    public array $rows = [];

    public function mount(): void
    {
        $this->authorizeOwner();
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $this->rows = Background::query()->orderBy('sort')->orderBy('id')->get()
            ->mapWithKeys(fn (Background $bg) => [$bg->id => [
                'name' => $bg->name,
                'price' => $bg->price,
                'premium_only' => $bg->premium_only,
                'active' => $bg->active,
                'sort' => $bg->sort,
            ]])->all();
    }

    public function add(): void
    {
        $this->authorizeOwner();

        $this->validate([
            'upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'newName' => ['required', 'string', 'max:64'],
            'newPrice' => ['required', 'integer', 'min:0', 'max:100000000'],
        ]);

        $file = Str::random(24).'.'.$this->upload->extension();
        Storage::disk('local')->putFileAs('backgrounds', $this->upload, $file);

        Background::create([
            'name' => $this->newName,
            'file' => $file,
            'price' => $this->newPrice,
            'premium_only' => $this->newPremium,
            'active' => true,
            'sort' => (int) Background::max('sort') + 1,
        ]);

        $this->reset(['upload', 'newName', 'newPrice', 'newPremium']);
        $this->loadRows();
        $this->dispatch('saved');
    }

    public function saveRow(int $id): void
    {
        $this->authorizeOwner();

        $this->validate([
            "rows.$id.name" => ['required', 'string', 'max:64'],
            "rows.$id.price" => ['required', 'integer', 'min:0', 'max:100000000'],
            "rows.$id.sort" => ['required', 'integer'],
        ]);

        Background::findOrFail($id)->update([
            'name' => $this->rows[$id]['name'],
            'price' => (int) $this->rows[$id]['price'],
            'premium_only' => (bool) $this->rows[$id]['premium_only'],
            'active' => (bool) $this->rows[$id]['active'],
            'sort' => (int) $this->rows[$id]['sort'],
        ]);

        $this->dispatch('saved');
    }

    public function delete(int $id): void
    {
        $this->authorizeOwner();

        $background = Background::findOrFail($id);
        $disk = Storage::disk('local');
        if ($disk->exists('backgrounds/'.$background->file)) {
            $disk->delete('backgrounds/'.$background->file);
        }
        \App\Models\BotUser::query()->where('background_id', $id)->update(['background_id' => null]);
        $background->delete();

        $this->loadRows();
    }

    private function authorizeOwner(): void
    {
        abort_unless(EnsureBotOwner::check(auth()->id()), 403);
    }

    public function render()
    {
        return view('livewire.admin-backgrounds', [
            'backgrounds' => Background::query()->orderBy('sort')->orderBy('id')->get()->keyBy('id'),
            'owners' => \Illuminate\Support\Facades\DB::table('user_backgrounds')
                ->selectRaw('background_id, count(*) as total')->groupBy('background_id')->pluck('total', 'background_id'),
        ]);
    }
}
