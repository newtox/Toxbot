@if ($type === \App\Models\ShopItem::FRAME)
    <span class="grid aspect-[5/3] w-full place-items-center bg-[radial-gradient(circle_at_center,var(--color-dc-600),var(--color-dc-900))]">
        <span class="relative grid size-32 place-items-center sm:size-36">
            <span class="absolute size-[67%] rounded-full bg-dc-500"></span>
            <img src="{{ $url }}" alt="" class="relative size-full object-contain" loading="lazy">
        </span>
    </span>
@elseif ($type === \App\Models\ShopItem::BAR)
    <span class="flex aspect-[5/3] w-full flex-col justify-center gap-3 bg-dc-900 px-6">
        <span class="block h-5 w-full overflow-hidden rounded-full bg-dc-600">
            <img src="{{ $url }}" alt="" class="h-full w-full object-fill" loading="lazy">
        </span>
        <span class="block h-5 w-full overflow-hidden rounded-full bg-dc-600">
            <span class="block h-full w-3/5 overflow-hidden rounded-full"><img src="{{ $url }}" alt="" class="h-full w-[166.7%] max-w-none object-fill" loading="lazy"></span>
        </span>
    </span>
@else
    <img src="{{ $url }}" alt="" class="aspect-[5/3] w-full object-cover" loading="lazy">
@endif
