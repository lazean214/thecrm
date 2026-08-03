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

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white tracking-tight">Internal Companies</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Manage payroll companies used across the system.</p>
        </div>
        <button wire:click="openCreate"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add Company
        </button>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Slug</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $company->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 font-mono">{{ $company->slug }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <button wire:click="openEdit({{ $company->id }})"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                    Edit
                                </button>
                                <button wire:click="delete({{ $company->id }})" wire:confirm="Delete {{ $company->name }}?"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                            No internal companies found. Add one from the env or use the button above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $companies->links() }}
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" wire:click.self="cancel">
            <div class="w-full max-w-lg bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6" wire:click.self="">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    {{ $editingId ? 'Edit' : 'Add' }} Internal Company
                </h2>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Name</label>
                        <input type="text" wire:model="name" autofocus
                            class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">
                            {{ $editingId ? 'Update' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
