@props(['exportUrl'])

<form action="{{ $exportUrl }}" method="POST" class="inline-block"
    x-data="{ loading: false }" @submit="loading = true">
    @csrf
    <button type="submit"
        x-data="{ loading: false }" @click="loading = true; setTimeout(() => loading = false, 3000)"
        class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition"
    >
        <template x-if="!loading">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
        </template>
        <template x-if="loading">
            <svg class="w-3.5 h-3.5 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </template>
        <span x-text="loading ? 'Exporting…' : 'Export Excel'"></span>
    </button>
</form>
