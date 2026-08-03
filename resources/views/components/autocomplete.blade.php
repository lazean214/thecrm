@props(['wireModel', 'searchMethod', 'placeholder' => 'Search...', 'label' => ''])

<div class="relative" x-data="{ open: false }" @click.away="open = false" @keydown.escape="open = false">
    @if ($label)
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">{{ $label }}</label>
    @endif
    <input
        type="text"
        wire:model.live.debounce.300ms="{{ $wireModel }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        @focus="open = true"
        @input="open = true"
        {{ $attributes->merge(['class' => 'block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition']) }}
    >
    <div
        x-show="open && $wire.{{ $searchMethod }}().length > 0"
        x-cloak
        class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden max-h-60 overflow-y-auto"
    >
        @foreach ($this->{$searchMethod}() as $item)
            <div
                wire:click="$set('{{ $wireModel }}', {{ Js::from(is_array($item) ? $item['name'] ?? $item : $item->name ?? $item) }})"
                class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer"
            >
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <circle cx="6.5" cy="6.5" r="5.5" stroke="currentColor" stroke-width="1.3"/>
                    <path d="M4.5 6.5l1.5 1.5 2.5-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ is_array($item) ? $item['name'] ?? '' : $item->name ?? $item }}
            </div>
        @endforeach
    </div>
</div>
