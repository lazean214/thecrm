{{-- MDA Details - uses parent component's internalCompanies and wire:model binding --}}
<section x-data="{ expanded: true }"
    class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm mt-4">
    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex justify-between items-center">
        MDA
        <span class="flex items-center gap-2">
            <button wire:click="syncToMDA" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-70 disabled:cursor-not-allowed transition shadow-sm">
                <span wire:loading.remove wire:target="syncToMDA">Sync to MDA</span>
                <span wire:loading.flex wire:target="syncToMDA" class="items-center gap-1">
                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                    </svg>
                    Syncing...
                </span>
            </button>
            <button @click="expanded = !expanded"
                class="group inline-flex items-center justify-center rounded-lg p-1.5 transition hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 text-slate-400 transition-all duration-300 ease-in-out group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300"
                    :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7-7-7 7" />
                </svg>
            </button>
        </span>
    </h2>

    <div x-show="expanded" x-collapse.duration.300ms class="mt-4 space-y-4">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">MDA Setup</label>
            <select wire:model="mda_setup"
                class="capitalize block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="" disabled selected>Select MDA Setup</option>
                @foreach ($internalCompanies as $company)
                    <option value="{{ $company['name'] }}">{{ $company['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">MDA Reference Number</label>
            <input type="text" wire:model="mda_reference_number"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Date Set Up</label>
            <input type="date" wire:model="date_set_up"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Remittance Received</label>
            <select wire:model="remittance_received"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="" disabled>Select Option</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Date Logged</label>
            <input type="date" wire:model="date_logged"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
        </div>
    </div>
</section>
