<div class="p-6 space-y-6" x-data="{
    showToast: false,
    toastMessage: '',
    toastType: 'success'
}"
    x-on:notify.window="showToast = true; toastMessage = $event.detail.message; toastType = $event.detail.type; setTimeout(() => showToast = false, 3000);">
    <div x-show="showToast" x-transition
        class="fixed top-5 right-5 z-50 px-5 py-3 rounded-xl shadow-xl text-sm font-medium"
        :class="{ 'bg-emerald-500 text-white': toastType === 'success', 'bg-red-500 text-white': toastType === 'error' }">
        <span x-text="toastMessage"></span>
    </div>

    @if ($message)
        <div class="px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-sm font-medium text-emerald-700 dark:text-emerald-400">
            {{ $message }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('companies') }}"
                class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </a>
        </div>
        @if (! $showEdit)
            <div class="flex items-center gap-2">
                <button wire:click="edit"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">
                    Edit
                </button>
                <button wire:click="delete" wire:confirm="Delete this company?"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 shadow-sm transition">
                    Delete
                </button>
            </div>
        @endif
    </div>

    @if ($showEdit)
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Edit Company</h2>
            <form wire:submit="save" class="space-y-4 max-w-lg">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Name</label>
                    <input type="text" wire:model="name"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Email</label>
                    <input type="email" wire:model="email"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Domain</label>
                    <input type="text" wire:model="domain"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Phone</label>
                    <input type="text" wire:model="phone"
                        class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="cancel"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">
                        Save
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">{{ $company->name }}</h2>
                <dl class="space-y-4">
                    <div class="flex items-center gap-4">
                        <dt class="w-24 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Name</dt>
                        <dd class="text-sm text-slate-900 dark:text-white">{{ $company->name }}</dd>
                    </div>
                    <div class="flex items-center gap-4">
                        <dt class="w-24 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Email</dt>
                        <dd class="text-sm text-slate-900 dark:text-white">{{ $company->email ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center gap-4">
                        <dt class="w-24 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Domain</dt>
                        <dd class="text-sm text-slate-900 dark:text-white">{{ $company->domain ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center gap-4">
                        <dt class="w-24 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Phone</dt>
                        <dd class="text-sm text-slate-900 dark:text-white">{{ $company->phone ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
            <div class="p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-4">Associated Deals</h3>
                @forelse ($deals as $deal)
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700/50 last:border-0">
                        <div>
                            <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $deal->name }}</span>
                            <span class="text-xs text-slate-400 ml-2">Deal Owner: {{ $deal->user?->name ?? 'N/A' }}</span>
                        </div>
                        <a href="{{ route('deals.show', $deal) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View</a>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No associated deals.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
            <div class="p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-4">Associated Contacts</h3>
                @forelse ($contacts as $contact)
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700/50 last:border-0">
                        <span class="text-sm text-slate-900 dark:text-white">{{ $contact->first_name }} {{ $contact->last_name }}</span>
                        <span class="text-xs text-slate-400">{{ $contact->email }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No associated contacts.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
