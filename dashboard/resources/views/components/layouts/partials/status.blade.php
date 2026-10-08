@if (session('status'))
    <div class="mx-auto mt-4 w-full max-w-4xl px-4 md:px-8">
        <div class="rounded-lg border-l-4 border-dc-yellow bg-dc-800 px-4 py-3 text-sm">{{ session('status') }}</div>
    </div>
@endif
