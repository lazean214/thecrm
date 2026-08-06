<div class="space-y-6" x-data="remittanceDashboard($wire.entangle('billerByOwner'), $wire.entangle('workersByCompany'))" x-init="init()">

    {{-- ── Header ─────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Remittance Report</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Worker timesheet summary and breakdown by deal owner.</p>
        </div>
        @if($loaded)
            <div class="flex items-center gap-2">
                <button wire:click="exportSummary"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Summary
                </button>
                <button wire:click="exportBreakdown"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Breakdown
                </button>
                <button wire:click="exportUnified"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Unified Report
                </button>
            </div>
        @endif
    </div>

    {{-- ── Filters ────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Filters</span>
        </div>

        {{-- Date Range Presets --}}
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium whitespace-nowrap">Period:</span>
            <div class="flex gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-0.5">
                @foreach(['all' => 'All Time', '30days' => 'Last 30 Days', '90days' => 'Last 90 Days'] as $value => $label)
                    <button wire:click="$set('dateRange', '{{ $value }}')"
                        class="px-3 py-1 text-xs font-medium rounded-md transition {{ $dateRange === $value ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1 font-medium">Week From</label>
                <select wire:model.live="filterWeekFrom"
                    class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All Weeks</option>
                    @foreach($weekMapping as $week)
                        <option value="{{ $week['week'] }}">Week {{ $week['week'] }} — {{ $week['end'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1 font-medium">Week To</label>
                <select wire:model.live="filterWeekTo"
                    class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All Weeks</option>
                    @foreach($weekMapping as $week)
                        <option value="{{ $week['week'] }}">Week {{ $week['week'] }} — {{ $week['end'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1 font-medium">Worker / Biller</label>
                <input type="text" wire:model.live.debounce.300ms="filterBiller" placeholder="Search name..."
                    class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1 font-medium">Agency / Company</label>
                <select wire:model.live="filterCompany"
                    class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All Agencies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1 font-medium">CID / Deal Owner</label>
                <select wire:model.live="filterDealOwner"
                    class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition text-slate-900 dark:text-slate-100">
                    <option value="">All Owners</option>
                    @foreach($users as $user)
                        <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-3">
            <button wire:click="generateReport"
                class="inline-flex items-center gap-2 px-4 py-1.5 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Generate Report
            </button>
            @if($loaded)
                <button wire:click="resetFilters"
                    class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition font-medium">
                    Reset All
                </button>
            @endif
        </div>
    </div>

    {{-- ── Results ────────────────────────────────────────── --}}
    @if($loaded)

        {{-- ── Stats Cards ────────────────────────────────── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
            {{-- Active Billers --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Billers</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($activeBillersCount) }}</p>
                @if($fiscalYearLabel)
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">FY {{ $fiscalYearLabel }}</p>
                @endif
            </div>

            {{-- Inactive Billers --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Inactive Billers</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($inactiveBillersCount) }}</p>
                @if($fiscalYearLabel)
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">FY {{ $fiscalYearLabel }}</p>
                @endif
            </div>

            {{-- Total Billers (Ever Active) --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Billers</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalBillersCount) }}</p>
            </div>

            {{-- Active Workers --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Workers</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($activeWorkersCount) }}</p>
                @if($fiscalYearLabel)
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">FY {{ $fiscalYearLabel }}</p>
                @endif
            </div>

            {{-- Inactive Workers --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M22 10h-6m-3-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Inactive Workers</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($inactiveWorkersCount) }}</p>
                @if($fiscalYearLabel)
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">FY {{ $fiscalYearLabel }}</p>
                @endif
            </div>

            {{-- Total Workers (Ever Active) --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Workers</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalWorkersCount) }}</p>
            </div>

            {{-- Total Remittance Value --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total TSV</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">£{{ number_format($totalRemittanceValue, 2) }}</p>
            </div>

            {{-- Total Hours --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Hours</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalHours, 1) }}</p>
            </div>

            {{-- Total Companies --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Companies</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($totalCompanies) }}</p>
            </div>
        </div>

        {{-- ── Charts ─────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Billers by Owner Chart --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Billers by Deal Owner</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Active biller count per owner</p>
                    </div>
                    @if(count($billerByOwner) > 0)
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">{{ count($billerByOwner) }} owners</span>
                    @endif
                </div>
                <div class="relative h-64">
                    <canvas id="billerByOwnerChart" wire:ignore></canvas>
                </div>
            </div>

            {{-- Workers by Company Chart --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Workers by Company</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Worker distribution by agency</p>
                    </div>
                    @if(count($workersByCompany) > 0)
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">{{ count($workersByCompany) }} agencies</span>
                    @endif
                </div>
                <div class="relative h-64">
                    <canvas id="workersByCompanyChart" wire:ignore></canvas>
                </div>
            </div>
        </div>

        {{-- ── Summary + Timesheet Breakdown (Side by Side) ── --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            {{-- Summary Table --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Summary</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Distinct worker + agency + CID per week</p>
                    </div>
                    @if(count($summaryRows) > 0)
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">{{ count($summaryRows) }} rows</span>
                    @endif
                </div>
                <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs uppercase tracking-wider sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold">WK #</th>
                                <th class="px-4 py-2.5 text-left font-semibold">Worker</th>
                                <th class="px-4 py-2.5 text-left font-semibold">Agency</th>
                                <th class="px-4 py-2.5 text-left font-semibold">CID</th>
                                <th class="px-4 py-2.5 text-right font-semibold">TSV (£)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($summaryRows as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                    <td class="px-4 py-2.5 text-slate-900 dark:text-slate-100 font-medium">{{ $row['week_no'] ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-900 dark:text-slate-100">{{ $row['worker_name'] }}</td>
                                    <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400 text-xs">{{ $row['agency'] }}</td>
                                    <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400 text-xs">{{ $row['cid'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium text-slate-900 dark:text-slate-100">
                                        £{{ number_format($row['tsv_sum'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500">
                                        No records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($summaryRows) > 0)
                            <tfoot class="bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 sticky bottom-0">
                                <tr>
                                    <td colspan="4" class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Total</td>
                                    <td class="px-4 py-2.5 text-right text-xs font-bold text-slate-900 dark:text-white">
                                        £{{ number_format(collect($summaryRows)->sum('tsv_sum'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Timesheet Breakdown --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Timesheet Breakdown</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Per-worker timesheet records</p>
                    </div>
                    @if(count($breakdowns) > 0)
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">{{ count($breakdowns) }} workers</span>
                    @endif
                </div>
                <div class="overflow-y-auto max-h-[520px]">
                    @forelse($breakdowns as $breakdown)
                        <div class="border-b border-slate-100 dark:border-slate-800 last:border-b-0"
                            x-data="{ open: false }">
                            {{-- Worker Header --}}
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition text-left">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-90': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <div>
                                        <span class="font-medium text-sm text-slate-900 dark:text-white">{{ $breakdown['worker_name'] }}</span>
                                        <span class="ml-2 text-[11px] text-slate-400 dark:text-slate-500">{{ count($breakdown['rows']) }} record{{ count($breakdown['rows']) !== 1 ? 's' : '' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">{{ number_format($breakdown['total_hours'], 1) }}h</span>
                                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">£{{ number_format($breakdown['total_tsv'], 2) }}</span>
                                </div>
                            </button>

                            {{-- Breakdown Table --}}
                            <div x-show="open" x-collapse>
                                <div class="overflow-x-auto border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                                    <table class="w-full text-xs">
                                        <thead class="text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            <tr>
                                                <th class="px-4 py-2 text-left font-semibold">WK #</th>
                                                <th class="px-4 py-2 text-left font-semibold">Agency</th>
                                                <th class="px-4 py-2 text-left font-semibold">CID</th>
                                                <th class="px-4 py-2 text-left font-semibold">Shift</th>
                                                <th class="px-4 py-2 text-left font-semibold">WE</th>
                                                <th class="px-4 py-2 text-right font-semibold">Hrs</th>
                                                <th class="px-4 py-2 text-right font-semibold">Rate</th>
                                                <th class="px-4 py-2 text-right font-semibold">TSV</th>
                                                <th class="px-4 py-2 text-right font-semibold">Margin</th>
                                                <th class="px-4 py-2 text-left font-semibold">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                            @foreach($breakdown['rows'] as $row)
                                                <tr class="hover:bg-white dark:hover:bg-slate-800/40 transition">
                                                    <td class="px-4 py-2 text-slate-900 dark:text-slate-100 font-medium">{{ $row['week_no'] ?? '—' }}</td>
                                                    <td class="px-4 py-2 text-slate-600 dark:text-slate-400">{{ $row['agency'] }}</td>
                                                    <td class="px-4 py-2 text-slate-600 dark:text-slate-400">{{ $row['cid'] }}</td>
                                                    <td class="px-4 py-2 text-slate-600 dark:text-slate-400">{{ $row['shift_date'] }}</td>
                                                    <td class="px-4 py-2 text-slate-600 dark:text-slate-400">{{ $row['we_date'] }}</td>
                                                    <td class="px-4 py-2 text-right text-slate-900 dark:text-slate-100">{{ number_format($row['hours'], 1) }}</td>
                                                    <td class="px-4 py-2 text-right text-slate-900 dark:text-slate-100">£{{ number_format($row['rate'], 2) }}</td>
                                                    <td class="px-4 py-2 text-right font-medium text-slate-900 dark:text-slate-100">£{{ number_format($row['tsv'], 2) }}</td>
                                                    <td class="px-4 py-2 text-right text-slate-600 dark:text-slate-400">£{{ number_format($row['margin'], 2) }}</td>
                                                    <td class="px-4 py-2">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium
                                                            {{ match($row['status']) {
                                                                'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                                                'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                                'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                                default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                                                            } }}">
                                                            {{ ucfirst(str_replace('_', ' ', $row['status'])) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-400 dark:text-slate-500">
                            No timesheet data found.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Deal Owner Breakdown Table ──────────────────── --}}
        @if(count($billerByOwner) > 0)
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Deal Owner Breakdown</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Active biller count and totals per owner</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Deal Owner</th>
                            <th class="px-5 py-3 text-center font-semibold">Active Billers</th>
                            <th class="px-5 py-3 text-right font-semibold">Total TSV (£)</th>
                            <th class="px-5 py-3 text-right font-semibold">Total Hours</th>
                            <th class="px-5 py-3 text-right font-semibold">Avg TSV / Biller</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($billerByOwner as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="px-5 py-3 text-slate-900 dark:text-slate-100 font-medium">{{ $row['owner_name'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 text-xs font-bold">
                                        {{ $row['active_billers'] ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-medium text-slate-900 dark:text-slate-100">£{{ number_format($row['total_tsv'] ?? 0, 2) }}</td>
                                <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-400">{{ number_format($row['total_hours'] ?? 0, 1) }}</td>
                                <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-400">
                                    @if(($row['active_billers'] ?? 0) > 0)
                                        £{{ number_format(($row['total_tsv'] ?? 0) / $row['active_billers'], 2) }}
                                    @else
                                        £0.00
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ── Company Breakdown Table ─────────────────────── --}}
        @if(count($workersByCompany) > 0)
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Company Breakdown</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Worker distribution and totals by agency</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Company</th>
                            <th class="px-5 py-3 text-center font-semibold">Workers</th>
                            <th class="px-5 py-3 text-right font-semibold">Total TSV (£)</th>
                            <th class="px-5 py-3 text-right font-semibold">Total Hours</th>
                            <th class="px-5 py-3 text-right font-semibold">Avg TSV / Worker</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($workersByCompany as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="px-5 py-3 text-slate-900 dark:text-slate-100 font-medium">{{ $row['company_name'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 text-xs font-bold">
                                        {{ $row['worker_count'] ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-medium text-slate-900 dark:text-slate-100">£{{ number_format($row['total_tsv'] ?? 0, 2) }}</td>
                                <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-400">{{ number_format($row['total_hours'] ?? 0, 1) }}</td>
                                <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-400">
                                    @if(($row['worker_count'] ?? 0) > 0)
                                        £{{ number_format(($row['total_tsv'] ?? 0) / $row['worker_count'], 2) }}
                                    @else
                                        £0.00
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    @else
        {{-- ── Empty State ────────────────────────────────── --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No Report Generated</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">Use the filters above to generate a remittance report. Click "Generate Report" or adjust any filter to see results.</p>
        </div>
    @endif
</div>

{{-- ── Chart.js + Dashboard Alpine Component ────────────── --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
function remittanceDashboard(initialBillerData, initialCompanyData) {
    let billerChart = null;
    let companyChart = null;

    return {
        billerData: initialBillerData,
        companyData: initialCompanyData,

        init() {
            this.$watch('billerData', () => this.$nextTick(() => this.renderCharts()));
            this.$watch('companyData', () => this.$nextTick(() => this.renderCharts()));
            this.$nextTick(() => this.renderCharts());
        },

        renderCharts() {
            const billerData = this.billerData || [];
            const companyData = this.companyData || [];
            const isDark = document.documentElement.classList.contains('dark');

            const gridColor = isDark ? '#334155' : '#e2e8f0';
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const tooltipBg = isDark ? '#1e293b' : '#ffffff';
            const tooltipTitle = isDark ? '#f1f5f9' : '#0f172a';
            const tooltipBody = isDark ? '#cbd5e1' : '#334155';
            const tooltipBorder = isDark ? '#475569' : '#e2e8f0';

            // ── Biller by Owner Bar Chart ──
            const billerCtx = document.getElementById('billerByOwnerChart');
            if (billerCtx) {
                if (billerChart) {
                    billerChart.destroy();
                    billerChart = null;
                }
                if (billerData.length > 0) {
                    billerChart = new Chart(billerCtx, {
                        type: 'bar',
                        data: {
                            labels: billerData.map(d => d.owner_name),
                            datasets: [{
                                label: 'Active Billers',
                                data: billerData.map(d => d.active_billers),
                                backgroundColor: isDark ? 'rgba(99, 102, 241, 0.7)' : 'rgba(99, 102, 241, 0.8)',
                                borderColor: '#6366f1',
                                borderWidth: 1,
                                borderRadius: 6,
                                borderSkipped: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: tooltipBg,
                                    titleColor: tooltipTitle,
                                    bodyColor: tooltipBody,
                                    borderColor: tooltipBorder,
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    padding: 12,
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { color: textColor, stepSize: 1, font: { size: 11 } },
                                    grid: { color: gridColor, drawBorder: false },
                                    border: { display: false },
                                },
                                x: {
                                    ticks: { color: textColor, font: { size: 11 } },
                                    grid: { display: false },
                                    border: { display: false },
                                }
                            }
                        }
                    });
                }
            }

            // ── Workers by Company Doughnut Chart ──
            const companyCtx = document.getElementById('workersByCompanyChart');
            if (companyCtx) {
                if (companyChart) {
                    companyChart.destroy();
                    companyChart = null;
                }
                if (companyData.length > 0) {
                    const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#84cc16', '#06b6d4', '#f97316'];
                    const colorsBg = isDark
                        ? ['rgba(99,102,241,0.7)', 'rgba(139,92,246,0.7)', 'rgba(236,72,153,0.7)', 'rgba(245,158,11,0.7)', 'rgba(16,185,129,0.7)', 'rgba(59,130,246,0.7)', 'rgba(239,68,68,0.7)', 'rgba(132,204,22,0.7)', 'rgba(6,182,212,0.7)', 'rgba(249,115,22,0.7)']
                        : colors;

                    companyChart = new Chart(companyCtx, {
                        type: 'doughnut',
                        data: {
                            labels: companyData.map(d => d.company_name),
                            datasets: [{
                                data: companyData.map(d => d.worker_count),
                                backgroundColor: colorsBg.slice(0, companyData.length),
                                borderWidth: 0,
                                hoverOffset: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '60%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: textColor,
                                        padding: 16,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        font: { size: 11 },
                                    }
                                },
                                tooltip: {
                                    backgroundColor: tooltipBg,
                                    titleColor: tooltipTitle,
                                    bodyColor: tooltipBody,
                                    borderColor: tooltipBorder,
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    padding: 12,
                                    callbacks: {
                                        label: function(context) {
                                            return ' ' + context.label + ': ' + context.parsed + ' workers';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    };
}
</script>
