<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureBotOwner;
use App\Models\BotUser;
use App\Models\ShopItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Imagick;
use Livewire\Component;
use Livewire\WithFileUploads;

class AdminBackgrounds extends Component
{
    use WithFileUploads;

    public $upload;

    public string $newType = ShopItem::BACKGROUND;

    public string $newName = '';

    public int $newPrice = 500;

    public string $newSku = '';

    public array $rows = [];

    public function mount(): void
    {
        $this->authorizeOwner();
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $this->rows = ShopItem::query()->orderByRaw("CASE type WHEN 'background' THEN 0 WHEN 'frame' THEN 1 ELSE 2 END")->orderBy('sort')->orderBy('id')->get()
            ->mapWithKeys(fn (ShopItem $bg) => [$bg->id => [
                'name' => $bg->name,
                'price' => $bg->price,
                'sku_id' => (string) $bg->sku_id,
                'active' => $bg->active,
                'sort' => $bg->sort,
            ]])->all();
    }

    public function add(): void
    {
        $this->authorizeOwner();

        $this->validate([
            'newType' => ['required', Rule::in(ShopItem::TYPES)],
            'upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'newName' => ['required', 'string', 'max:64', Rule::unique('backgrounds', 'name')],
            'newPrice' => ['required', 'integer', 'min:0', 'max:100000000'],
            'newSku' => ['nullable', 'regex:/^\d{15,22}$/', Rule::unique('backgrounds', 'sku_id')],
        ], ['newSku.regex' => __('admin.sku_invalid'), 'newSku.unique' => __('admin.sku_taken')]);

        $file = Str::random(24).'.'.$this->upload->extension();
        Storage::disk('local')->putFileAs('backgrounds', $this->upload, $file);

        ShopItem::create([
            'name' => $this->newName,
            'type' => $this->newType,
            'file' => $file,
            'animated' => $this->isAnimated(ShopItem::pathFor($file)),
            'price' => $this->newPrice,
            'sku_id' => trim($this->newSku) ?: null,
            'active' => true,
            'sort' => (int) ShopItem::query()->ofType($this->newType)->max('sort') + 1,
        ]);

        $this->reset(['upload', 'newName', 'newPrice', 'newSku']);
        $this->loadRows();
        $this->dispatch('saved');
    }

    private function isAnimated(?string $path): bool
    {
        if ($path === null || ! class_exists(Imagick::class)) {
            return false;
        }

        try {
            $image = new Imagick($path);
            $animated = $image->getNumberImages() > 1;
            $image->clear();

            return $animated;
        } catch (\Throwable) {
            return false;
        }
    }

    public function updatedRows(mixed $value, string $key): void
    {
        $this->authorizeOwner();

        $id = (int) explode('.', $key)[0];
        if (! isset($this->rows[$id])) {
            return;
        }

        $this->resetValidation();
        $this->validate([
            "rows.$id.name" => ['required', 'string', 'max:64', Rule::unique('backgrounds', 'name')->ignore($id)],
            "rows.$id.price" => ['required', 'integer', 'min:0', 'max:100000000'],
            "rows.$id.sort" => ['required', 'integer'],
            "rows.$id.sku_id" => ['nullable', 'regex:/^\d{15,22}$/', Rule::unique('backgrounds', 'sku_id')->ignore($id)],
        ], ["rows.$id.sku_id.regex" => __('admin.sku_invalid'), "rows.$id.sku_id.unique" => __('admin.sku_taken')]);

        ShopItem::findOrFail($id)->update([
            'name' => trim($this->rows[$id]['name']),
            'price' => (int) $this->rows[$id]['price'],
            'sku_id' => trim((string) $this->rows[$id]['sku_id']) ?: null,
            'active' => (bool) $this->rows[$id]['active'],
            'sort' => (int) $this->rows[$id]['sort'],
        ]);

        $this->dispatch('saved');
    }

    public function delete(int $id): void
    {
        $this->authorizeOwner();

        $item = ShopItem::findOrFail($id);
        $disk = Storage::disk('local');
        if ($disk->exists('backgrounds/'.$item->file)) {
            $disk->delete('backgrounds/'.$item->file);
        }
        BotUser::query()->where($item->column(), $id)->update([$item->column() => null]);
        $item->delete();

        $this->loadRows();
    }

    private function authorizeOwner(): void
    {
        abort_unless(EnsureBotOwner::check(auth()->id()), 403);
    }

    public function render()
    {
        return view('livewire.admin-backgrounds', [
            'items' => ShopItem::query()->get()->keyBy('id'),
            'owners' => DB::table('user_backgrounds')
                ->selectRaw('background_id, count(*) as total')->groupBy('background_id')->pluck('total', 'background_id'),
        ]);
    }
}
