@php
    $isSales = $this->isSalesTeam();
    $isCompliance = $this->isComplianceTeam();

    $cols = $visibleColumns;
    $hasCol = fn(string $key) => in_array($key, $cols);

    $allColumns = collect($this::AVAILABLE_COLUMNS)
        ->groupBy('group', preserveKeys: true)
        ->map(fn($columns) => $columns->map(fn($column) => $column['label']));

    $colCount = count($cols) + 2; // checkbox + action column
@endphp

{{-- Toolbar --}}
<div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
    <div class="flex items-center gap-3">

        {{-- Per-page --}}
        <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <span>Show</span>
            <select wire:model.live="perPage"
                class="rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2 py-1 text-sm">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>per page</span>
        </div>

        {{-- Column chooser --}}
        <div class="relative" x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                Columns
                <span class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">{{ count($cols) }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute left-0 top-full mt-1.5 z-50 w-72 rounded-xl shadow-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div
                    class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Toggle
                        Columns</p>
                </div>

                <div class="max-h-80 overflow-y-auto py-2 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($allColumns as $group => $columns)
                        <div class="px-4 py-2">
                            <p
                                class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">
                                {{ $group }}</p>
                            <div class="space-y-0.5">
                                @foreach ($columns as $key => $label)
                                    <label
                                        class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition group">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $key }}')"
                                            @checked(in_array($key, $cols))
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700" />
                                        <span
                                            class="text-sm text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white">
                                            {{ $label }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <div class="text-sm text-slate-500 dark:text-slate-400">
        @if ($totalDeals > 0)
            Showing <span
                class="font-medium text-slate-700 dark:text-slate-200">{{ $paginationFrom }}–{{ $paginationTo }}</span>
            of <span class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($totalDeals) }}</span>
            deals
        @else
            No deals found
        @endif
    </div>
</div>

<div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
    <table class="w-full text-sm">
        <thead>
            <tr
                class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400">

                {{-- Checkbox --}}
                <th class="w-10 px-4 py-3 text-left">
                    <input type="checkbox" wire:click="toggleSelectAll" @checked($selectAll)
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800" />
                </th>

                @if ($hasCol('name'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">Deal
                        Name</th>
                @endif
                @if ($hasCol('owner'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Owner</th>
                @endif
                @if ($hasCol('owner_email'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Owner Email</th>
                @endif
                @if ($hasCol('contact'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Contact</th>
                @endif
                @if ($hasCol('company'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Company</th>
                @endif
                @if ($hasCol('company_email'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Company Email</th>
                @endif
                @if ($hasCol('company_phone'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Company Phone</th>
                @endif
                @if ($hasCol('company_domain'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Company Domain</th>
                @endif
                @if ($hasCol('amount'))
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Amount</th>
                @endif
                @if ($hasCol('agency_deal_value'))
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Agency Value</th>
                @endif
                @if ($hasCol('margin_agreed'))
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Margin</th>
                @endif
                @if ($hasCol('stage'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Stage</th>
                @endif
                @if ($hasCol('recruitment_agency'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Agency</th>
                @endif
                @if ($hasCol('consultant_name'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Consultant</th>
                @endif
                @if ($hasCol('date_sent'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">Date
                        Sent</th>
                @endif
                @if ($hasCol('date_signed'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">Date
                        Signed</th>
                @endif
                @if ($hasCol('who_signed'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">Who
                        Signed</th>
                @endif
                @if ($hasCol('right_to_work'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">RTW
                    </th>
                @endif
                @if ($hasCol('mda_reference_number'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">MDA
                        Ref</th>
                @endif
                @if ($hasCol('date_set_up'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">Set
                        Up</th>
                @endif
                @if ($hasCol('tax_code'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">Tax
                        Code</th>
                @endif
                @if ($hasCol('created_at'))
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide whitespace-nowrap">
                        Created</th>
                @endif

                {{-- Actions (always visible) --}}
                <th class="w-10 px-3 py-3"></th>
            </tr>
        </thead>

        <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($deals as $deal)
                @php
                    $currentStage = $deal['stage'];
                    $canEditThisStage = $this->canEditStage($currentStage, $currentStage);
                    $isLocked = $isSales && !$canEditThisStage;
                    $availableStages = $isSales ? $this->getAllowedStagesForUser() : $this->stages;
                    $isRowSelected = in_array($deal['id'], $selectedDeals);
                    $dealUrl = route('deals.show', $deal['id']);
                @endphp

                <tr wire:key="row-{{ $deal['id'] }}"
                    class="group transition-colors cursor-pointer
                        {{ $isRowSelected
                            ? 'bg-indigo-50 dark:bg-indigo-900/15'
                            : ($isLocked
                                ? 'bg-slate-50/70 dark:bg-slate-900/50 opacity-80 hover:opacity-100 hover:bg-slate-50 dark:hover:bg-slate-800/40'
                                : 'hover:bg-slate-50 dark:hover:bg-slate-800/40') }}"
                    onclick="if(!event.target.closest('input,select,button,a')){window.location='{{ $dealUrl }}'}">
                    {{-- Checkbox --}}
                    <td class="px-4 py-3" onclick="event.stopPropagation()">
                        <input type="checkbox" wire:click="toggleDealSelection({{ $deal['id'] }})"
                            @checked($isRowSelected)
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800" />
                    </td>

                    @if ($hasCol('name'))
                        <td class="px-4 py-3 max-w-[200px]">
                            <div class="flex items-center gap-2">
                                <a href="{{ $dealUrl }}"
                                    class="text-sm font-medium text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate"
                                    onclick="event.stopPropagation()">
                                    {{ $deal['name'] }}
                                </a>
                                @if ($isLocked)
                                    <span class="shrink-0 text-amber-500 dark:text-amber-400"
                                        title="Managed by Compliance">🔒</span>
                                @endif
                            </div>
                        </td>
                    @endif

                    @if ($hasCol('owner'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['user']['name'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('owner_email'))
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                            {{ $deal['user']['email'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('contact'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            @if (!empty($deal['contacts'][0]))
                                {{ trim(($deal['contacts'][0]['first_name'] ?? '') . ' ' . ($deal['contacts'][0]['last_name'] ?? '')) ?: '—' }}
                            @else
                                —
                            @endif
                        </td>
                    @endif

                    @if ($hasCol('company'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['companies'][0]['name'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('company_email'))
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                            {{ $deal['companies'][0]['email'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('company_phone'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['companies'][0]['phone'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('company_domain'))
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                            {{ $deal['companies'][0]['domain'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('amount'))
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <span class="font-semibold text-slate-900 dark:text-white tabular-nums">
                                £{{ number_format($deal['amount'] ?? 0, 0) }}
                            </span>
                        </td>
                    @endif

                    @if ($hasCol('agency_deal_value'))
                        <td
                            class="px-4 py-3 text-right whitespace-nowrap text-slate-600 dark:text-slate-400 tabular-nums">
                            {{ $deal['agency_deal_value'] ? '£' . number_format($deal['agency_deal_value'], 0) : '—' }}
                        </td>
                    @endif

                    @if ($hasCol('margin_agreed'))
                        <td
                            class="px-4 py-3 text-right whitespace-nowrap text-slate-600 dark:text-slate-400 tabular-nums">
                            {{ $deal['margin_agreed'] ? '£' . number_format($deal['margin_agreed'], 0) : '—' }}
                        </td>
                    @endif

                    @if ($hasCol('stage'))
                        <td class="px-4 py-3" onclick="event.stopPropagation()">
                            @if ($canEditThisStage)
                                <select wire:change="updateStage({{ $deal['id'] }}, $event.target.value)"
                                    class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer"
                                    style="background-color: {{ $stageConfig[$currentStage]['accentLight'] ?? 'transparent' }}; color: {{ $stageConfig[$currentStage]['accentText'] ?? 'inherit' }}; border-color: {{ $stageConfig[$currentStage]['accent'] ?? '' }}40;">
                                    @foreach ($availableStages as $stage)
                                        <option value="{{ $stage }}" @selected($stage === $currentStage)>
                                            {{ $stageConfig[$stage]['icon'] ?? '' }}
                                            {{ $stageConfig[$stage]['label'] ?? ucwords($stage) }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium whitespace-nowrap"
                                    style="background-color: {{ $stageConfig[$currentStage]['accentLight'] ?? '#f3f4f6' }}; color: {{ $stageConfig[$currentStage]['accentText'] ?? '#374151' }};"
                                    title="{{ $isLocked ? 'Managed by Compliance Team' : '' }}">
                                    {{ $stageConfig[$currentStage]['icon'] ?? '' }}
                                    {{ $stageConfig[$currentStage]['label'] ?? $currentStage }}
                                </span>
                            @endif
                        </td>
                    @endif

                    @if ($hasCol('recruitment_agency'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['recruitment_agency'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('consultant_name'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['consultant_name'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('date_sent'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                            {{ $deal['date_sent'] ? \Carbon\Carbon::parse($deal['date_sent'])->format('d M Y') : '—' }}
                        </td>
                    @endif

                    @if ($hasCol('date_signed'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                            {{ $deal['date_signed'] ? \Carbon\Carbon::parse($deal['date_signed'])->format('d M Y') : '—' }}
                        </td>
                    @endif

                    @if ($hasCol('who_signed'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['who_signed'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('right_to_work'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['right_to_work'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('mda_reference_number'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['mda_reference_number'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('date_set_up'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                            {{ $deal['date_set_up'] ? \Carbon\Carbon::parse($deal['date_set_up'])->format('d M Y') : '—' }}
                        </td>
                    @endif

                    @if ($hasCol('tax_code'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $deal['tax_code'] ?? '—' }}
                        </td>
                    @endif

                    @if ($hasCol('created_at'))
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                            {{ \Carbon\Carbon::parse($deal['created_at'])->format('d M Y') }}
                        </td>
                    @endif

                    {{-- Quick action --}}
                    <td class="px-3 py-3 text-right" onclick="event.stopPropagation()">
                        <a href="{{ $dealUrl }}"
                            class="opacity-0 group-hover:opacity-100 transition inline-flex items-center justify-center w-7 h-7 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
                            title="Open deal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colCount }}" class="text-center py-16">
                        <div class="flex flex-col items-center gap-2 text-slate-400 dark:text-slate-500">
                            <svg class="w-8 h-8 opacity-50" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm">No deals match the current filters.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination (cursor-based with prefetch) --}}
@if ($hasMorePages || $previousCursor)
    <div class="flex items-center justify-between mt-4 px-1">
        <div class="text-sm text-slate-500 dark:text-slate-400">
            Showing {{ count($deals) }} deals per page
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="previousPage()" @disabled(!$previousCursor)
                class="px-3 py-1.5 rounded-md text-sm border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
                ‹ Previous
            </button>

            <button
                wire:click="nextPage()"
                @mouseenter="$dispatch('prefetchNextPage')"
                @disabled(!$hasMorePages)
                class="px-3 py-1.5 rounded-md text-sm border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
                Next ›
            </button>
        </div>
    </div>
@endif
