<section x-data="{ expanded: true }"
    class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm mt-4">
    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex justify-between items-center">
        Payroll Setup
        <span class="flex items-center gap-2">
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
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Company</label>
            <select wire:model="payroll_company"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="">Select Company</option>
                @foreach ($internalCompanies as $company)
                    <option value="{{ $company['name'] }}">{{ $company['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Source</label>
            <select wire:model="payroll_source"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="">Select Source</option>
                @foreach ($payrollSources as $source)
                    <option value="{{ $source }}">{{ $source }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Reference</label>
            <input type="text" wire:model="payroll_reference"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Start Date</label>
            <input type="date" wire:model="payroll_start_date"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Status</label>
            <select wire:model="payroll_status"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>
</section>
