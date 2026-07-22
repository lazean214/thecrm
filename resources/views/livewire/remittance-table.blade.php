<div class="space-y-4" x-data="{
    showToast: false,
    toastMessage: '',
    toastType: 'success'
}"
    x-on:notify.window="showToast = true; toastMessage = $event.detail.message; toastType = $event.detail.type; setTimeout(() => showToast = false, 3000);">
    {{-- Toast --}}
    <div x-show="showToast" x-transition
        class="fixed top-5 right-5 z-50 px-5 py-3 rounded-xl shadow-xl text-sm font-medium"
        :class="{ 'bg-emerald-500 text-white': toastType === 'success', 'bg-red-500 text-white': toastType === 'error' }">
        <span x-text="toastMessage"></span>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white tracking-tight">Remittance Tracker</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Editable spreadsheet - select a contact to
                auto-fill deal data.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="addRow"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Row
            </button>
            <button wire:click="saveAll" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition disabled:opacity-50">
                <svg wire:loading.remove wire:target="saveAll" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <svg wire:loading wire:target="saveAll" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                </svg>
                Save All
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
                @if ($this->hasActiveFilters)
                    <span
                        class="ml-2 px-2 py-0.5 text-xs rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300">Active</span>
                @endif
            </h3>
            @if ($this->hasActiveFilters)
                <button wire:click="resetFilters"
                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Clear all</button>
            @endif
        </div>

        {{-- Date Range Presets --}}
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Period:</span>
            <div
                class="flex gap-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 p-0.5">
                @foreach (['week' => 'This Week', 'month' => 'This Month', 'quarter' => 'This Quarter', 'year' => 'This Year', 'all' => 'All Time'] as $value => $label)
                    <button wire:click="$set('dateRange', '{{ $value }}')"
                        class="px-3 py-1 text-xs font-medium rounded-md transition {{ $dateRange === $value ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
                @if (!in_array($dateRange, ['week', 'month', 'quarter', 'year', 'all']))
                    <button
                        class="px-3 py-1 text-xs font-medium rounded-md bg-indigo-600 text-white shadow-sm">Custom</button>
                @endif
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500">|</span>
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="filterWeDateFrom" wire:change="$set('dateRange', 'custom')"
                    class="px-2 py-1 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                <span class="text-xs text-slate-400">to</span>
                <input type="date" wire:model.live="filterWeDateTo" wire:change="$set('dateRange', 'custom')"
                    class="px-2 py-1 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
            </div>
        </div>

        {{-- Other Filters --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            {{-- Week No --}}
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Week</label>
                <select wire:model.live="filterWeekNo"
                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All Weeks</option>
                    @foreach($weekMapping as $week)
                        <option value="{{ $week['week'] }}">Week {{ $week['week'] }} — {{ $week['end'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Company --}}
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Company</label>
                <select wire:model.live="filterCompany"
                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All Companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Deal Owner --}}
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Deal Owner</label>
                <select wire:model.live="filterDealOwner"
                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All Owners</option>
                    @foreach ($users as $user)
                        <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Status</label>
                <select wire:model.live="filterStatus"
                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All Statuses</option>
                    @foreach (['pending', 'approved', 'rejected', 'paid', 'on_hold'] as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Biller Name --}}
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Biller Name</label>
                <input type="text" wire:model.live.debounce.300ms="filterBiller" placeholder="Search name..."
                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
            </div>

            {{-- Shift Date From --}}
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Shift Date From</label>
                <input type="date" wire:model.live="filterShiftDateFrom"
                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
            </div>

            {{-- Shift Date To --}}
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Shift Date To</label>
                <input type="date" wire:model.live="filterShiftDateTo"
                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
            </div>

            {{-- Compliance --}}
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Compliance</label>
                <select wire:model.live="filterCompliance"
                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All</option>
                    <option value="1">Checked</option>
                    <option value="0">Unchecked</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Spreadsheet --}}
    <div
        class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
        <table class="w-full text-sm text-left" style="min-width: 2400px;">
            <thead
                class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs uppercase tracking-wider sticky top-0 z-10">
                <tr>
                    <th class="px-4 py-3 font-semibold w-10">#</th>
                    <th class="px-4 py-3 font-semibold min-w-[90px]">Week #</th>
                    <th class="px-4 py-3 font-semibold min-w-[250px]">Biller's Name *</th>
                    <th class="px-4 py-3 font-semibold min-w-[150px]">NI No</th>
                    <th class="px-4 py-3 font-semibold min-w-[230px]">Company</th>
                    <th class="px-4 py-3 font-semibold min-w-[180px]">Deal Owner</th>
                    <th class="px-4 py-3 font-semibold min-w-[150px]">Status</th>
                    <th class="px-4 py-3 font-semibold min-w-[150px]">TSV (£)</th>
                    <th class="px-4 py-3 font-semibold min-w-[100px]">Hours</th>
                    <th class="px-4 py-3 font-semibold min-w-[100px]">Rate (£)</th>
                    <th class="px-4 py-3 font-semibold min-w-[120px]">Margin (£)</th>
                    <th class="px-4 py-3 font-semibold min-w-[160px]">WE Date</th>
                    <th class="px-4 py-3 font-semibold min-w-[160px]">Shift Date</th>
                    <th class="px-4 py-3 font-semibold min-w-[160px]">Invoice</th>
                    <th class="px-4 py-3 font-semibold min-w-[140px]">Batch</th>
                    <th class="px-4 py-3 font-semibold min-w-[150px]">Agency Funds</th>
                    <th class="px-4 py-3 font-semibold min-w-[160px]">Payment Status</th>
                    <th class="px-4 py-3 font-semibold min-w-[120px]">From</th>
                    <th class="px-4 py-3 font-semibold text-center w-16">Compliance</th>
                    <th class="px-4 py-3 font-semibold min-w-[220px]">Remarks</th>
                    <th class="px-4 py-3 font-semibold min-w-[160px]">Date Added</th>
                    <th class="px-4 py-3 font-semibold w-20">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($rows as $index => $row)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors {{ $row['contact_id'] ? '' : 'bg-amber-50/40 dark:bg-amber-900/10' }}"
                        wire:key="row-{{ $index }}">

                        {{-- Row # --}}
                        <td class="px-4 py-2 text-slate-400 dark:text-slate-500 text-xs font-mono">
                            {{ $index + 1 }}
                        </td>

                        {{-- Week No --}}
                        <td class="px-3 py-1.5">
                            <input type="number"
                                wire:change="updateCell({{ $index }}, 'week_no', $event.target.value)"
                                value="{{ $row['week_no'] }}" placeholder="Wk"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Contact (autocomplete search) --}}
                        <td class="px-3 py-1.5" x-data="{
                            open: false,
                            query: '',
                            highlighted: -1,
                            ddPos: { top: 0, left: 0, width: 0 },
                            get results() {
                                const q = this.query.toLowerCase();
                                const list = $wire.contacts;
                                if (!q) return list;
                                return list.filter(c =>
                                    c.name.toLowerCase().includes(q) ||
                                    (c.email || '')
                                    .toLowerCase().includes(q) ||
                                    (c.phone || '').includes(q)
                                );
                            },
                            reposition() {
                                if (!this.open) return;
                                const el = $el.querySelector('input');
                                if (el) {
                                    const r = el.getBoundingClientRect();
                                    this.ddPos = { top: r.bottom + 4, left: r.left, width: Math.max(r.width, 384) };
                                }
                            },
                            init() {
                                this.$refs.scrollParent = this.$el.closest('.overflow-x-auto');
                                if (this.$refs.scrollParent) {
                                    this.$refs.scrollParent.addEventListener('scroll', () => this.reposition());
                                }
                                window.addEventListener('scroll', () => this.reposition(), true);
                            },
                            positionDropdown(e) {
                                const rect = e.target.getBoundingClientRect();
                                this.ddPos = { top: rect.bottom + 4, left: rect.left, width: Math.max(rect.width, 384) };
                            },
                            selectContact(id) {
                                $wire.onContactSelected({{ $index }}, id);
                                this.query = '';
                                this.open = false;
                                this.highlighted = -1;
                            },
                            handleKeydown(e) {
                                if (e.key === 'ArrowDown') {
                                    e.preventDefault();
                                    this.highlighted = Math.min(this.highlighted + 1, this.results.length - 1);
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    this.highlighted = Math.max(this.highlighted - 1, -1);
                                } else if (e.key === 'Enter' && this.highlighted >= 0) {
                                    e.preventDefault();
                                    this.selectContact(this.results[this.highlighted].id);
                                } else if (e.key === 'Escape') {
                                    this.open = false;
                                }
                            },
                            hl(text) {
                                const q = this.query.toLowerCase();
                                if (!q || !text) return text;
                                const i = text.toLowerCase().indexOf(q);
                                if (i === -1) return text;
                                return text.slice(0, i) + '\x3cmark class=\x22bg-indigo-200 dark:bg-indigo-700 rounded px-0.5\x22\x3e' + text.slice(i, i + q.length) + '\x3c/mark\x3e' + text.slice(i + q.length);
                            }
                        }" @click.outside="open = false; query = ''">
                            <div class="relative flex-1">
                                <input type="text"
                                    :value="query.length > 0 ? query :
                                        '{{ addslashes($this->getContactName($row['contact_id'])) }}'"
                                    @input="query = $event.target.value; highlighted = -1; open = true; positionDropdown($event)"
                                    @focus="open = true; positionDropdown($event)" @keydown="handleKeydown($event)"
                                    placeholder="Type to search contacts..."
                                    class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100 placeholder-slate-400">
                                <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <div x-show="open && query.length > 0 && results.length === 0" x-transition
                                :style="'position:fixed;top:' + ddPos.top + 'px;left:' + ddPos.left + 'px;width:' + ddPos
                                    .width + 'px;z-index:9998'"
                                class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl">
                                <div class="px-3 py-3 text-sm text-slate-500 dark:text-slate-400 text-center">
                                    No contacts matching "<span x-text="query" class="font-medium"></span>"
                                </div>
                            </div>
                            <div x-show="open && results.length > 0" x-transition
                                :style="'position:fixed;top:' + ddPos.top + 'px;left:' + ddPos.left + 'px;width:' + ddPos
                                    .width + 'px;z-index:9999'"
                                class="max-h-72 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl divide-y divide-slate-100 dark:divide-slate-700">
                                <template x-for="(contact, i) in results" :key="contact.id">
                                    <div class="px-3 py-2.5 text-sm cursor-pointer transition"
                                        :class="{
                                            'bg-indigo-50 dark:bg-indigo-900/30': highlighted === i,
                                            'hover:bg-slate-50 dark:hover:bg-slate-700/50': highlighted !== i
                                        }"
                                        @mouseenter="highlighted = i" @click="selectContact(contact.id)">
                                        <div class="font-medium text-slate-900 dark:text-white"
                                            x-html="hl(contact.name)"></div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                                            x-html="hl(contact.email || '')"></div>
                                        <template x-if="contact.phone">
                                            <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"
                                                x-html="hl(contact.phone)"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </td>

                        {{-- NI No --}}
                        <td class="px-3 py-1.5">
                            <input type="text" readonly value="{{ $this->getContactNi($row['contact_id']) }}"
                                class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-600 dark:text-slate-400 cursor-not-allowed">
                        </td>

                        {{-- Company --}}
                        <td class="px-3 py-1.5">
                            <select wire:change="updateCell({{ $index }}, 'company_id', $event.target.value)"
                                wire:model="rows.{{ $index }}.company_id"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                                <option value="">-- Select --</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company['id'] }}"
                                        {{ (int) ($row['company_id'] ?? 0) === $company['id'] ? 'selected' : '' }}>
                                        {{ $company['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Deal Owner --}}
                        <td class="px-3 py-1.5">
                            <select wire:change="updateCell({{ $index }}, 'deal_owner', $event.target.value)"
                                wire:model="rows.{{ $index }}.deal_owner"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                                @foreach ($users as $user)
                                    <option value="{{ $user['id'] }}"
                                        {{ (int) ($row['deal_owner'] ?? 0) === $user['id'] ? 'selected' : '' }}>
                                        {{ $user['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Status --}}
                        <td class="px-3 py-1.5">
                            <select wire:change="updateCell({{ $index }}, 'status', $event.target.value)"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                                <option value="">--</option>
                                @foreach (['pending', 'approved', 'rejected', 'paid', 'on_hold'] as $status)
                                    <option value="{{ $status }}"
                                        {{ ($row['status'] ?? '') === $status ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- TSV --}}
                        <td class="px-3 py-1.5">
                            <div class="relative">
                                <span
                                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-slate-400 pointer-events-none">£</span>
                                <input type="number" step="0.01"
                                    wire:change="updateCell({{ $index }}, 'amount', $event.target.value)"
                                    value="{{ $row['amount'] }}" placeholder="0.00"
                                    class="w-full pl-7 pr-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                            </div>
                        </td>

                        {{-- Hours --}}
                        <td class="px-3 py-1.5">
                            <input type="number" step="0.5"
                                wire:change="updateCell({{ $index }}, 'hours', $event.target.value)"
                                value="{{ $row['hours'] }}" placeholder="0"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Rate --}}
                        <td class="px-3 py-1.5">
                            <div class="relative">
                                <span
                                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-slate-400 pointer-events-none">£</span>
                                <input type="number" step="0.01"
                                    wire:change="updateCell({{ $index }}, 'rate', $event.target.value)"
                                    value="{{ $row['rate'] }}" placeholder="0.00"
                                    class="w-full pl-7 pr-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                            </div>
                        </td>

                        {{-- Margin --}}
                        <td class="px-3 py-1.5">
                            <div class="relative">
                                <span
                                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-slate-400 pointer-events-none">£</span>
                                <input type="number" step="0.01"
                                    wire:change="updateCell({{ $index }}, 'margin_agreed', $event.target.value)"
                                    value="{{ $row['margin_agreed'] }}" placeholder="0.00"
                                    class="w-full pl-7 pr-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                            </div>
                        </td>

                        {{-- WE Date --}}
                        <td class="px-3 py-1.5">
                            <input type="date"
                                wire:change="updateCell({{ $index }}, 'we_date', $event.target.value)"
                                value="{{ $row['we_date'] }}"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Shift Date --}}
                        <td class="px-3 py-1.5">
                            <input type="date"
                                wire:change="updateCell({{ $index }}, 'shirft_date', $event.target.value)"
                                value="{{ $row['shirft_date'] }}"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Invoice --}}
                        <td class="px-3 py-1.5">
                            <input type="text"
                                wire:change="updateCell({{ $index }}, 'invoice', $event.target.value)"
                                value="{{ $row['invoice'] }}" placeholder="INV-001"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Batch --}}
                        <td class="px-3 py-1.5">
                            <input type="text"
                                wire:change="updateCell({{ $index }}, 'batch', $event.target.value)"
                                value="{{ $row['batch'] }}" placeholder="Batch"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Agency Funds --}}
                        <td class="px-3 py-1.5">
                            <input type="text"
                                wire:change="updateCell({{ $index }}, 'agency_funds', $event.target.value)"
                                value="{{ $row['agency_funds'] }}" placeholder="0.00"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Payment Status --}}
                        <td class="px-3 py-1.5">
                            <select
                                wire:change="updateCell({{ $index }}, 'payment_status', $event.target.value)"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                                <option value="">--</option>
                                @foreach (['unpaid', 'paid', 'partial', 'overdue'] as $ps)
                                    <option value="{{ $ps }}"
                                        {{ ($row['payment_status'] ?? '') === $ps ? 'selected' : '' }}>
                                        {{ ucfirst($ps) }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- From --}}
                        <td class="px-3 py-1.5">
                            <input type="text"
                                wire:change="updateCell({{ $index }}, 'from', $event.target.value)"
                                value="{{ $row['from'] }}" placeholder="Source"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Compliance --}}
                        <td class="px-3 py-1.5 text-center">
                            <input type="checkbox"
                                wire:change="updateCell({{ $index }}, 'compliance', $event.target.checked)"
                                {{ $row['compliance'] ? 'checked' : '' }}
                                class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </td>

                        {{-- Remarks --}}
                        <td class="px-3 py-1.5">
                            <input type="text"
                                wire:change="updateCell({{ $index }}, 'remarks', $event.target.value)"
                                value="{{ $row['remarks'] }}" placeholder="Notes..."
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Date Added --}}
                        <td class="px-3 py-1.5">
                            <input type="date"
                                wire:change="updateCell({{ $index }}, 'date_added', $event.target.value)"
                                value="{{ $row['date_added'] }}"
                                class="w-full px-3 py-2 text-sm bg-transparent border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                        </td>

                        {{-- Actions --}}
                        <td class="px-3 py-1.5">
                            <div class="flex items-center gap-1">
                                <button wire:click="duplicateRow({{ $index }})"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                                    title="Duplicate row">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                                <button wire:click="removeRow({{ $index }})"
                                    wire:confirm="Are you sure you want to delete this row?"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition"
                                    title="Delete row">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <p>No remittances yet. Click <strong>Add Row</strong> to start.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer info --}}
    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
        <span>{{ count($rows) }} row(s) &middot; Select a contact to auto-fill deal info</span>
        <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Rows highlighted in amber have no contact selected
        </span>
    </div>
</div>
