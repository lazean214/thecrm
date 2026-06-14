<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<section x-data="{ expanded: true }"
    class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm mt-4">
    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex justify-between items-center">
        Deal Details
        <button @click="expanded = !expanded"
            class="group inline-flex items-center justify-center rounded-lg p-1.5 transition hover:bg-slate-100 dark:hover:bg-slate-700">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-slate-400 transition-all duration-300 ease-in-out group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300"
                :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7-7-7 7" />
            </svg>
        </button>
    </h2>

    <div class="grid grid-cols-2 gap-4 mt-3 mb-4 text-xs">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Created</span>
            <p class="mt-0.5 text-sm text-slate-900 dark:text-white">{{ $created_at ? $created_at->format('d M Y') : 'N/A' }}</p>
        </div>
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Last Modified</span>
            <p class="mt-0.5 text-sm text-slate-900 dark:text-white">{{ $updated_at ? $updated_at->format('d M Y') : 'N/A' }}</p>
        </div>
    </div>

    <div class="border-t border-slate-100 dark:border-slate-700"></div>

    <div x-show="expanded" x-collapse.duration.300ms class="mt-4 space-y-4">
        {{-- Deal Name --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Deal Name</label>
            <input type="text" wire:model="name"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
        </div>

        {{-- Deal Owner --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Deal Owner</label>
            @php
                $users = \App\Models\User::all();
                $dealOwnerOptions = $users->map(fn ($user) => ['id' => $user->id, 'name' => $user->name]);
            @endphp
            <select wire:model="user_id"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="" disabled>Select owner…</option>
                @foreach ($dealOwnerOptions as $option)
                    <option value="{{ $option['id'] }}" @selected($option['id'] == $user_id)>{{ $option['name'] }}</option>
                @endforeach
            </select>
        </div>

        {{-- Amount --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Amount (TSV)</label>
            <div class="flex items-center rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 pl-3 has-[:focus-within]:ring-2 has-[:focus-within]:ring-indigo-500/20 has-[:focus-within]:border-indigo-500 transition">
                <span class="shrink-0 text-sm text-slate-400 select-none">£</span>
                <input type="text" wire:model="amount" placeholder="0.00"
                    class="block min-w-0 grow py-2 pr-3 pl-1.5 text-sm bg-transparent text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none sm:text-sm/6" />
            </div>
        </div>

        {{-- Recruitment Source --}}
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Recruitment Source</label>
            <select wire:model="recruitment_agency"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="" disabled selected>Select source…</option>
                <option value="Inbound">Inbound</option>
                <option value="Referral">Referral</option>
            </select>
        </div>

        {{-- Recruitment Agency (autocomplete) --}}
        <div class="relative">
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Recruitment Agency</label>
            <input wire:model.live="consultant_name" type="text" placeholder="Search or enter agency"
                autocomplete="off"
                class="block w-full px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" />
            @if ($showConsultantDropdown)
                <div class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden">
                    @foreach ($consultantSuggestions as $suggestion)
                        <div wire:click="selectConsultant('{{ addslashes($suggestion) }}')"
                            class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer">
                            <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                                <circle cx="6.5" cy="6.5" r="5.5" stroke="currentColor" stroke-width="1.3" />
                                <path d="M4.5 6.5l1.5 1.5 2.5-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ $suggestion }}
                        </div>
                    @endforeach
                </div>
            @endif
            @error('consultant_name')
                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        {{-- Financial row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Agency Deal Value</label>
                <div class="flex items-center rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 pl-3 has-[:focus-within]:ring-2 has-[:focus-within]:ring-indigo-500/20 has-[:focus-within]:border-indigo-500 transition">
                    <span class="shrink-0 text-sm text-slate-400 select-none">£</span>
                    <input type="text" wire:model="agency_deal_value" placeholder="0.00"
                        class="block min-w-0 grow py-2 pr-3 pl-1.5 text-sm bg-transparent text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none sm:text-sm/6" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Margin Agreed</label>
                <div class="flex items-center rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 pl-3 has-[:focus-within]:ring-2 has-[:focus-within]:ring-indigo-500/20 has-[:focus-within]:border-indigo-500 transition">
                    <span class="shrink-0 text-sm text-slate-400 select-none">£</span>
                    <input type="text" wire:model="margin_agreed" placeholder="0.00"
                        class="block min-w-0 grow py-2 pr-3 pl-1.5 text-sm bg-transparent text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none sm:text-sm/6" />
                </div>
            </div>
        </div>
    </div>
</section>
