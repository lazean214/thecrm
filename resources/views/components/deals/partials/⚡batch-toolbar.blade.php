{{-- components/deals/partials/⚡batch-toolbar.blade.php --}}

@if ($this->getSelectedCount() > 0)
    <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-xl z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="text-sm font-medium text-slate-900 dark:text-white">
                {{ $this->getSelectedCount() }} deal(s) selected
            </div>
            <div class="flex items-center gap-3">
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors">
                        Batch Operations
                        <svg class="w-4 h-4 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 bottom-full mb-2 w-48 rounded-lg shadow-lg bg-white dark:bg-slate-800 ring-1 ring-black ring-opacity-5 divide-y divide-slate-100 dark:divide-slate-700 z-50">
                        <div class="py-1">
                            <button wire:click="openBatchModal('owner')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">👤 Update Owner</button>
                            <button wire:click="openBatchModal('stage')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">📊 Update Stage</button>
                        </div>
                        <div class="py-1">
                            <button wire:click="openBatchModal('delete')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">🗑️ Delete Records</button>
                        </div>
                    </div>
                </div>
                <button wire:click="resetBatchState" class="px-4 py-2.5 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white text-sm font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">Clear Selection</button>
            </div>
        </div>
    </div>
@endif
