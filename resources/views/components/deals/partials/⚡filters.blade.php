{{-- ── Filter Bar ── --}}
<div x-data="{ open: false }" class="space-y-3 mb-6">

    {{-- Primary bar --}}
    <div class="flex items-center gap-3 bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 transition-all duration-200 focus-within:ring-2 focus-within:ring-indigo-500/10 focus-within:border-indigo-500">

        {{-- Deal name search --}}
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.250ms="filterDealName"
                placeholder="Search deals by name..."
                class="block w-full pl-9 pr-3 py-1.5 text-sm bg-transparent border-0 focus:ring-0 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none"
            >
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center gap-2 border-l border-slate-200 dark:border-slate-800 pl-3 shrink-0">
            @if($this->hasActiveFilters())
                <button
                    wire:click="resetFilters"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-950/40 transition"
                >
                    Clear
                </button>
            @endif
            <button
                @click="open = true; $wire.loadLookups()"
                type="button"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition relative"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                <span>Filters</span>
                @if($this->hasActiveFilters())
                    <span class="inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full bg-indigo-600 text-white text-[10px] font-bold">
                        {{ collect([$filterDealName,$filterOwner,$filterContact,$filterCompanyName,$filterStage,($minAmount!==null&&$minAmount!=='')?'1':'',$maxAmount?'1':'',$dateFrom,$dateTo])->filter()->count() }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    {{-- Default month scope indicator --}}
    @if($isDefaultDateRange)
        <div class="flex items-center gap-2 px-1">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Showing {{ now()->format('F Y') }}
            </div>
            <button
                wire:click="showAllTime"
                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
            >
                View all time →
            </button>
        </div>
    @endif

    {{-- Active filter chips --}}
    @if($this->hasActiveFilters())
        <div class="flex flex-wrap gap-1.5 items-center px-1">
            <span class="text-xs text-slate-400 mr-1">Active:</span>
            @if($filterDealName)
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-md text-xs font-medium bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                    Name: <strong>{{ $filterDealName }}</strong>
                    <button wire:click="$set('filterDealName', '')" class="hover:bg-indigo-100 dark:hover:bg-indigo-900 p-0.5 rounded transition"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </span>
            @endif
            @if($filterOwner)
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-md text-xs font-medium bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60">
                    Owner: <strong>{{ $filterOwner }}</strong>
                    <button wire:click="$set('filterOwner', '')" class="hover:bg-violet-100 p-0.5 rounded transition"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </span>
            @endif
            @if($filterContact)
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-md text-xs font-medium bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">
                    Contact: <strong>{{ $filterContact }}</strong>
                    <button wire:click="$set('filterContact', '')" class="hover:bg-sky-100 p-0.5 rounded transition"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </span>
            @endif
            @if($filterCompanyName)
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-md text-xs font-medium bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800/60">
                    Company: <strong>{{ $filterCompanyName }}</strong>
                    <button wire:click="$set('filterCompanyName', '')" class="hover:bg-teal-100 p-0.5 rounded transition"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </span>
            @endif
            @if($filterStage)
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-md text-xs font-medium bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800/60">
                    Stage: <strong>{{ ucwords($filterStage) }}</strong>
                    <button wire:click="$set('filterStage', '')" class="hover:bg-orange-100 p-0.5 rounded transition"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </span>
            @endif
            @if(($minAmount !== null && $minAmount !== '') || ($maxAmount !== null && $maxAmount !== ''))
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-md text-xs font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                    Amount: <strong>£{{ $minAmount ?? '0' }} – {{ $maxAmount ? '£'.$maxAmount : '∞' }}</strong>
                    <button wire:click="$set('minAmount', null); $wire.set('maxAmount', null)" class="hover:bg-emerald-100 p-0.5 rounded transition"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </span>
            @endif
            @if($dateFrom || $dateTo)
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-md text-xs font-medium bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60">
                    Date: <strong>{{ $dateFrom ?? '…' }} – {{ $dateTo ?? '…' }}</strong>
                    <button wire:click="$set('dateFrom', null); $wire.set('dateTo', null)" class="hover:bg-rose-100 p-0.5 rounded transition"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </span>
            @endif
        </div>
    @endif

    {{-- Advanced filter modal backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-start justify-center p-4 sm:p-6 pt-20"
        style="display: none;"
    >
        {{-- Modal panel --}}
        <div
            @click.stop
            x-show="open"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 -translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
            class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800/80">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Advanced Filters</h3>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 space-y-5 max-h-[calc(100vh-16rem)] overflow-y-auto">

                {{-- Row 1: Owner & Contact --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Owner autocomplete --}}
                    <div class="space-y-1.5"
                        x-data="{
                            open: false,
                            items: @js($allUsers),
                            get filtered() {
                                if (!this.$wire.filterOwner) return this.items.slice(0, 8);
                                const q = this.$wire.filterOwner.toLowerCase();
                                return this.items.filter(i => i.name.toLowerCase().includes(q)).slice(0, 8);
                            },
                            select(name) {
                                this.open = false;
                                this.$wire.filterOwner = name;
                            }
                        }"
                        @keydown.escape="open = false"
                        @click.away="open = false"
                    >
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Deal Owner</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="filterOwner"
                                @focus="open = true"
                                @input="open = true"
                                placeholder="Search owner..."
                                autocomplete="off"
                                class="block w-full pl-9 pr-8 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition"
                            >
                            <button x-show="$wire.filterOwner" @click="select('')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <ul x-show="open && filtered.length > 0" x-cloak class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden">
                                <template x-for="item in filtered" :key="item.id">
                                    <li @click="select(item.name)" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer">
                                        <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-xs flex items-center justify-center font-semibold shrink-0" x-text="item.name.charAt(0).toUpperCase()"></span>
                                        <span x-text="item.name"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    {{-- Contact autocomplete --}}
                    <div class="space-y-1.5"
                        x-data="{
                            open: false,
                            items: @js($allContacts),
                            get filtered() {
                                if (!this.$wire.filterContact) return this.items.slice(0, 8);
                                const q = this.$wire.filterContact.toLowerCase();
                                return this.items.filter(i => i.name.toLowerCase().includes(q)).slice(0, 8);
                            },
                            select(name) {
                                this.open = false;
                                this.$wire.filterContact = name;
                            }
                        }"
                        @keydown.escape="open = false"
                        @click.away="open = false"
                    >
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Contact Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="filterContact"
                                @focus="open = true"
                                @input="open = true"
                                placeholder="Search contact..."
                                autocomplete="off"
                                class="block w-full pl-9 pr-8 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition"
                            >
                            <button x-show="$wire.filterContact" @click="select('')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <ul x-show="open && filtered.length > 0" x-cloak class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden">
                                <template x-for="item in filtered" :key="item.id">
                                    <li @click="select(item.name)" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-sky-900/20 cursor-pointer">
                                        <span class="w-6 h-6 rounded-full bg-sky-100 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 text-xs flex items-center justify-center font-semibold shrink-0" x-text="item.first_name.charAt(0).toUpperCase()"></span>
                                        <span x-text="item.name"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Company & Stage --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Company autocomplete --}}
                    <div class="space-y-1.5"
                        x-data="{
                            open: false,
                            items: @js($allCompanies),
                            get filtered() {
                                if (!this.$wire.filterCompanyName) return this.items.slice(0, 8);
                                const q = this.$wire.filterCompanyName.toLowerCase();
                                return this.items.filter(i => i.name.toLowerCase().includes(q)).slice(0, 8);
                            },
                            select(name) {
                                this.open = false;
                                this.$wire.filterCompanyName = name;
                            }
                        }"
                        @keydown.escape="open = false"
                        @click.away="open = false"
                    >
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Company Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="filterCompanyName"
                                @focus="open = true"
                                @input="open = true"
                                placeholder="Search company..."
                                autocomplete="off"
                                class="block w-full pl-9 pr-8 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition"
                            >
                            <button x-show="$wire.filterCompanyName" @click="select('')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <ul x-show="open && filtered.length > 0" x-cloak class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                                <template x-for="item in filtered" :key="item.id">
                                    <li @click="select(item.name)" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-teal-50 dark:hover:bg-teal-900/20 cursor-pointer">
                                        <span class="w-6 h-6 rounded-full bg-teal-100 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 text-xs flex items-center justify-center font-semibold shrink-0" x-text="item.name.charAt(0).toUpperCase()"></span>
                                        <span x-text="item.name"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    {{-- Stage select --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Deal Stage</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <select wire:model.live="filterStage" class="block w-full pl-9 pr-8 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition appearance-none">
                                <option value="">All stages</option>
                                @foreach($stages as $s)
                                    <option value="{{ $s }}">{{ ucwords($s) }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800/60"></div>

                {{-- Row 3: Amount & Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Amount Range (£)</label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">£</div>
                                <input type="number" wire:model.live.debounce.300ms="minAmount" placeholder="Min" min="0" class="block w-full pl-7 pr-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition">
                            </div>
                            <span class="text-slate-400 text-xs shrink-0">to</span>
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">£</div>
                                <input type="number" wire:model.live.debounce.300ms="maxAmount" placeholder="Max" min="0" class="block w-full pl-7 pr-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition">
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Created Date Range</label>
                        <div class="relative" x-data="{
                            picker: null,
                            init() {
                                this.$nextTick(() => {
                                    if (!this.picker) {
                                        const input = this.$refs.dateRangeInput;
                                        this.picker = flatpickr(input, {
                                            mode: 'range',
                                            dateFormat: 'Y-m-d',
                                            defaultDate: [
                                                @if($dateFrom) new Date('{{ $dateFrom }}') @endif,
                                                @if($dateTo) new Date('{{ $dateTo }}') @endif
                                            ].filter(d => d),
                                            onChange: (selectedDates, dateStr, instance) => {
                                                if (selectedDates.length === 2) {
                                                    this.$wire.dateFrom = instance.formatDate(selectedDates[0], 'Y-m-d');
                                                    this.$wire.dateTo = instance.formatDate(selectedDates[1], 'Y-m-d');
                                                } else if (selectedDates.length === 1) {
                                                    this.$wire.dateFrom = instance.formatDate(selectedDates[0], 'Y-m-d');
                                                    this.$wire.dateTo = '';
                                                }
                                            }
                                        });
                                    }
                                });
                            },
                            clearDateRange() {
                                if (this.picker) {
                                    this.picker.clear();
                                }
                                this.$wire.dateFrom = '';
                                this.$wire.dateTo = '';
                            }
                        }">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <input
                                type="text"
                                x-ref="dateRangeInput"
                                placeholder="Select date range..."
                                readonly
                                class="block w-full pl-9 pr-10 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 transition cursor-pointer"
                            >
                            <button
                                x-show="{{ $dateFrom || $dateTo ? 'true' : 'false' }}"
                                @click="clearDateRange()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="bg-slate-50 dark:bg-slate-800/40 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <span class="text-xs text-slate-400">Select filters then click View Results</span>
                <button @click="open = false; $wire.applyFilters()" wire:loading.attr="disabled" wire:target="applyFilters" type="button" class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 shadow-sm transition flex items-center gap-2">
                    <svg wire:loading wire:target="applyFilters" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                    </svg>
                    <span wire:loading.remove wire:target="applyFilters">View Results</span>
                    <span wire:loading wire:target="applyFilters">Loading...</span>
                </button>
            </div>
        </div>
    </div>
</div>
