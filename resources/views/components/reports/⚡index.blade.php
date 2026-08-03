<?php

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $reportType = 'pipeline';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $ownerId = '';

    public string $stage = '';

    public string $companyId = '';

    public int $perPage = 15;

    public array $reportTypes = [
        'pipeline' => 'Pipeline Summary',
        'deals' => 'Deals Report',
        'agency' => 'Agency Performance',
        'worker' => 'Worker Performance',
        'stage' => 'Stage Analysis',
        'company' => 'Company Report',
    ];

    public array $chartColors = [
        '#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626', '#7c3aed',
        '#db2777', '#2563eb', '#ca8a04', '#16a34a',
    ];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function getOwnersProperty(): array
    {
        return User::select('id', 'name')->orderBy('name')->get()->toArray();
    }

    public function getStagesProperty(): array
    {
        return DealStage::cases();
    }

    public function getCompaniesProperty(): array
    {
        return Company::select('id', 'name')->orderBy('name')->get()->toArray();
    }

    public function getKpisProperty(): array
    {
        return match ($this->reportType) {
            'pipeline' => $this->pipelineKpis(),
            'deals' => $this->dealsKpis(),
            'agency' => $this->agencyKpis(),
            'worker' => $this->workerKpis(),
            'stage' => $this->stageKpis(),
            'company' => $this->companyKpis(),
            default => [],
        };
    }

    public function getChartDataProperty(): array
    {
        return match ($this->reportType) {
            'pipeline' => $this->pipelineChartData(),
            'deals' => $this->dealsChartData(),
            'agency' => $this->agencyChartData(),
            'worker' => $this->workerChartData(),
            'stage' => $this->stageChartData(),
            'company' => $this->companyChartData(),
            default => [],
        };
    }

    public function getRowsProperty(): mixed
    {
        return match ($this->reportType) {
            'pipeline' => $this->pipelineRows(),
            'deals' => $this->dealsRows(),
            'agency' => $this->agencyRows(),
            'worker' => $this->workerRows(),
            'stage' => $this->stageRows(),
            'company' => $this->companyRows(),
            default => collect(),
        };
    }

    public function updatedReportType(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->ownerId = '';
        $this->stage = '';
        $this->companyId = '';
        $this->resetPage();
    }

    private function baseQuery(): mixed
    {
        return Deal::when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->ownerId, fn ($q) => $q->where('user_id', $this->ownerId))
            ->when($this->stage, fn ($q) => $q->where('stage', $this->stage))
            ->when($this->companyId, fn ($q) => $q->whereHas('companies', fn ($q2) => $q2->where('companies.id', $this->companyId)));
    }

    // ── Pipeline ──

    private function pipelineKpis(): array
    {
        $query = $this->baseQuery();
        $totalValue = (clone $query)->whereNotIn('stage', ['lost'])->sum('amount');
        $activeDeals = (clone $query)->whereNotIn('stage', ['paid', 'lost'])->count();
        $avgMargin = (clone $query)->whereNotNull('margin_agreed')->avg('margin_agreed');

        return [
            ['label' => 'Pipeline Value', 'value' => '£' . number_format($totalValue), 'color' => 'text-indigo-600'],
            ['label' => 'Active Deals', 'value' => number_format($activeDeals), 'color' => 'text-cyan-600'],
            ['label' => 'Avg Margin', 'value' => $avgMargin ? number_format($avgMargin, 1) . '%' : '—', 'color' => 'text-emerald-600'],
            ['label' => 'Total Deals (range)', 'value' => number_format((clone $query)->count()), 'color' => 'text-amber-600'],
        ];
    }

    private function pipelineRows(): mixed
    {
        return $this->baseQuery()
            ->selectRaw('stage, count(*) as count, sum(amount) as total_value, avg(margin_agreed) as avg_margin')
            ->groupBy('stage')
            ->orderBy('stage')
            ->get();
    }

    private function pipelineChartData(): array
    {
        $stages = array_map(fn ($s) => $s->value, DealStage::cases());

        return $this->pipelineRows()->map(fn ($r) => [
            'label' => $r->stage,
            'value' => (float) $r->total_value,
            'color' => $this->chartColors[array_search($r->stage, $stages) % count($this->chartColors)] ?? '#4f46e5',
        ])->toArray();
    }

    // ── Deals ──

    private function dealsKpis(): array
    {
        $query = $this->baseQuery();
        $totalDeals = (clone $query)->count();
        $totalValue = (clone $query)->sum('amount');
        $avgValue = $totalDeals > 0 ? $totalValue / $totalDeals : 0;

        return [
            ['label' => 'Total Deals', 'value' => number_format($totalDeals), 'color' => 'text-indigo-600'],
            ['label' => 'Total Value', 'value' => '£' . number_format($totalValue), 'color' => 'text-cyan-600'],
            ['label' => 'Avg Deal Value', 'value' => '£' . number_format($avgValue), 'color' => 'text-emerald-600'],
            ['label' => 'Avg Margin', 'value' => (clone $query)->whereNotNull('margin_agreed')->avg('margin_agreed') ? number_format((clone $query)->avg('margin_agreed'), 1) . '%' : '—', 'color' => 'text-amber-600'],
        ];
    }

    private function dealsRows(): mixed
    {
        return $this->baseQuery()
            ->with('user')
            ->withPrimaryCompany()
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    private function dealsChartData(): array
    {
        $driver = DB::getDriverName();
        $dateFormat = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $months = $this->baseQuery()
            ->selectRaw("{$dateFormat} as month, count(*) as count")
            ->groupBy(DB::raw($dateFormat))
            ->orderBy('month')
            ->get();

        return $months->map(fn ($r) => [
            'label' => $r->month,
            'value' => (int) $r->count,
        ])->toArray();
    }

    // ── Agency ──

    private function agencyKpis(): array
    {
        $query = $this->baseQuery()->whereNotNull('recruitment_agency');
        $totalAgencies = (clone $query)->distinct('recruitment_agency')->count('recruitment_agency');
        $totalDeals = (clone $query)->count();
        $totalValue = (clone $query)->sum('amount');

        return [
            ['label' => 'Agencies', 'value' => number_format($totalAgencies), 'color' => 'text-indigo-600'],
            ['label' => 'Total Deals', 'value' => number_format($totalDeals), 'color' => 'text-cyan-600'],
            ['label' => 'Total Value', 'value' => '£' . number_format($totalValue), 'color' => 'text-emerald-600'],
            ['label' => 'Avg Margin', 'value' => (clone $query)->avg('margin_agreed') ? number_format((clone $query)->avg('margin_agreed'), 1) . '%' : '—', 'color' => 'text-amber-600'],
        ];
    }

    private function agencyRows(): mixed
    {
        return $this->baseQuery()
            ->whereNotNull('recruitment_agency')
            ->selectRaw('recruitment_agency, count(*) as count, sum(amount) as total_value, avg(margin_agreed) as avg_margin, sum(agency_deal_value) as total_agency_value')
            ->groupBy('recruitment_agency')
            ->orderBy('total_value', 'desc')
            ->get();
    }

    private function agencyChartData(): array
    {
        return $this->agencyRows()->map(fn ($r) => [
            'label' => $r->recruitment_agency,
            'value' => (float) $r->total_value,
        ])->toArray();
    }

    // ── Worker ──

    private function workerKpis(): array
    {
        $query = $this->baseQuery()->whereNotNull('consultant_name');
        $totalWorkers = (clone $query)->distinct('consultant_name')->count('consultant_name');
        $totalDeals = (clone $query)->count();
        $totalValue = (clone $query)->sum('amount');

        return [
            ['label' => 'Workers', 'value' => number_format($totalWorkers), 'color' => 'text-indigo-600'],
            ['label' => 'Total Deals', 'value' => number_format($totalDeals), 'color' => 'text-cyan-600'],
            ['label' => 'Total Value', 'value' => '£' . number_format($totalValue), 'color' => 'text-emerald-600'],
            ['label' => 'Avg Deal Value', 'value' => $totalDeals > 0 ? '£' . number_format($totalValue / $totalDeals) : '—', 'color' => 'text-amber-600'],
        ];
    }

    private function workerRows(): mixed
    {
        return $this->baseQuery()
            ->whereNotNull('consultant_name')
            ->selectRaw('consultant_name, count(*) as count, sum(amount) as total_value, avg(margin_agreed) as avg_margin')
            ->groupBy('consultant_name')
            ->orderBy('total_value', 'desc')
            ->get();
    }

    private function workerChartData(): array
    {
        return $this->workerRows()->map(fn ($r) => [
            'label' => $r->consultant_name,
            'value' => (float) $r->total_value,
        ])->toArray();
    }

    // ── Stage ──

    private function stageKpis(): array
    {
        $query = $this->baseQuery();
        $paidDeals = (clone $query)->where('stage', DealStage::PAID)
            ->whereNotNull('stage_updated_at')
            ->get(['created_at', 'stage_updated_at']);

        $avgDays = $paidDeals->count() > 0
            ? $paidDeals->avg(fn ($d) => $d->created_at->diffInDays($d->stage_updated_at))
            : null;

        return [
            ['label' => 'Total Deals', 'value' => number_format((clone $query)->count()), 'color' => 'text-indigo-600'],
            ['label' => 'Total Value', 'value' => '£' . number_format((clone $query)->sum('amount')), 'color' => 'text-cyan-600'],
            ['label' => 'Avg Cycle (paid)', 'value' => $avgDays !== null ? number_format($avgDays, 1) . ' days' : '—', 'color' => 'text-emerald-600'],
            ['label' => 'Conversion Rate', 'value' => $this->conversionRate(), 'color' => 'text-amber-600'],
        ];
    }

    private function conversionRate(): string
    {
        $paid = (clone $this->baseQuery())->where('stage', DealStage::PAID)->count();
        $lost = (clone $this->baseQuery())->where('stage', DealStage::LOST)->count();
        $total = $paid + $lost;

        return $total > 0 ? number_format(($paid / $total) * 100, 1) . '%' : '—';
    }

    private function stageRows(): mixed
    {
        return $this->baseQuery()
            ->selectRaw('stage, count(*) as count, sum(amount) as total_value, avg(margin_agreed) as avg_margin')
            ->groupBy('stage')
            ->orderBy('stage')
            ->get();
    }

    private function stageChartData(): array
    {
        return $this->stageRows()->map(fn ($r) => [
            'label' => $r->stage,
            'value' => (float) $r->count,
        ])->toArray();
    }

    // ── Company ──

    private function companyKpis(): array
    {
        $companyIds = DB::table('company_deal')->pluck('company_id')->unique();
        $dealIds = DB::table('company_deal')->pluck('deal_id');

        $query = $this->baseQuery()->whereIn('id', $dealIds);
        $totalCompanies = $companyIds->count();
        $totalDeals = (clone $query)->count();
        $totalValue = (clone $query)->sum('amount');

        return [
            ['label' => 'Companies', 'value' => number_format($totalCompanies), 'color' => 'text-indigo-600'],
            ['label' => 'Total Deals', 'value' => number_format($totalDeals), 'color' => 'text-cyan-600'],
            ['label' => 'Total Value', 'value' => '£' . number_format($totalValue), 'color' => 'text-emerald-600'],
            ['label' => 'Avg Margin', 'value' => (clone $query)->avg('margin_agreed') ? number_format((clone $query)->avg('margin_agreed'), 1) . '%' : '—', 'color' => 'text-amber-600'],
        ];
    }

    private function companyRows(): mixed
    {
        return DB::table('deals')
            ->join('company_deal', 'deals.id', '=', 'company_deal.deal_id')
            ->join('companies', 'company_deal.company_id', '=', 'companies.id')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('deals.created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('deals.created_at', '<=', $this->dateTo))
            ->when($this->ownerId, fn ($q) => $q->where('deals.user_id', $this->ownerId))
            ->when($this->stage, fn ($q) => $q->where('deals.stage', $this->stage))
            ->when($this->companyId, fn ($q) => $q->where('companies.id', $this->companyId))
            ->selectRaw('companies.name, companies.id, count(*) as count, sum(deals.amount) as total_value, avg(deals.margin_agreed) as avg_margin')
            ->groupBy('companies.id', 'companies.name')
            ->orderBy('total_value', 'desc')
            ->get();
    }

    private function companyChartData(): array
    {
        return $this->companyRows()->map(fn ($r) => [
            'label' => $r->name,
            'value' => (float) $r->total_value,
        ])->toArray();
    }

    // ── Export ──

    public function export(): mixed
    {
        $rows = $this->rows;
        $type = $this->reportTypes[$this->reportType];
        $csv = $this->buildCsv($rows);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, str_replace(' ', '_', $type) . '_' . now()->toDateString() . '.csv');
    }

    private function buildCsv(mixed $rows): string
    {
        $header = match ($this->reportType) {
            'pipeline' => ['Stage', 'Deal Count', 'Total Value', 'Avg Margin (%)'],
            'deals' => ['Deal Name', 'Stage', 'Amount', 'Owner', 'Company', 'Created'],
            'agency' => ['Agency', 'Deal Count', 'Total Value', 'Avg Margin (%)', 'Total Agency Value'],
            'worker' => ['Consultant', 'Deal Count', 'Total Value', 'Avg Margin (%)'],
            'stage' => ['Stage', 'Deal Count', 'Total Value', 'Avg Margin (%)'],
            'company' => ['Company', 'Deal Count', 'Total Value', 'Avg Margin (%)'],
            default => [],
        };

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $header);

        foreach ($rows as $row) {
            $line = match ($this->reportType) {
                'pipeline' => [$row->stage?->value ?? $row->stage, $row->count, $row->total_value ?? 0, number_format($row->avg_margin ?? 0, 1)],
                'deals' => [
                    $row->name,
                    $row->stage?->value ?? $row->stage,
                    $row->amount,
                    $row->user?->name ?? '—',
                    $row->companies->first()?->name ?? '—',
                    $row->created_at?->toDateString(),
                ],
                'agency' => [$row->recruitment_agency, $row->count, $row->total_value ?? 0, number_format($row->avg_margin ?? 0, 1), $row->total_agency_value ?? 0],
                'worker' => [$row->consultant_name, $row->count, $row->total_value ?? 0, number_format($row->avg_margin ?? 0, 1)],
                'stage' => [$row->stage?->value ?? $row->stage, $row->count, $row->total_value ?? 0, number_format($row->avg_margin ?? 0, 1)],
                'company' => [$row->name, $row->count, $row->total_value ?? 0, number_format($row->avg_margin ?? 0, 1)],
                default => [],
            };
            fputcsv($stream, $line);
        }

        rewind($stream);

        return stream_get_contents($stream);
    }

    public function maxChartValue(): float
    {
        $data = $this->chartData;

        return count($data) > 0 ? max(array_column($data, 'value')) : 1;
    }
};

?>

<div class="p-6 max-w-7xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Reports</h1>
        <button wire:click="export" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-sm">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export CSV
        </button>
    </div>

    {{-- Report Type Tabs --}}
    <div class="flex flex-wrap gap-1 p-1 bg-slate-100 dark:bg-slate-800/70 rounded-xl">
        @foreach ($reportTypes as $key => $label)
            <button wire:click="$set('reportType', '{{ $key }}')" @class([
                'px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200',
                'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' => $reportType === $key,
                'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-white/50 dark:hover:bg-slate-700/50' => $reportType !== $key,
            ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-end gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">From</label>
            <input type="date" wire:model.live="dateFrom" class="block w-40 px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">To</label>
            <input type="date" wire:model.live="dateTo" class="block w-40 px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Owner</label>
            <select wire:model.live="ownerId" class="block w-44 px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="">All Owners</option>
                @foreach ($this->owners as $owner)
                    <option value="{{ $owner['id'] }}">{{ $owner['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Stage</label>
            <select wire:model.live="stage" class="block w-40 px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="">All Stages</option>
                @foreach ($this->stages as $s)
                    <option value="{{ $s->value }}">{{ $s->value }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Company</label>
            <select wire:model.live="companyId" class="block w-44 px-3 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                <option value="">All Companies</option>
                @foreach ($this->companies as $c)
                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="resetFilters" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition">
            Reset
        </button>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($this->kpis as $kpi)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">{{ $kpi['label'] }}</p>
                <p class="text-2xl font-bold {{ $kpi['color'] }}">{{ $kpi['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Chart --}}
    @if (count($this->chartData) > 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">
                {{ $reportType === 'deals' ? 'Deals per Month' : ($reportType === 'stage' ? 'Deals per Stage' : 'Value Distribution') }}
            </h3>
            <div class="space-y-2">
                @php $maxVal = $this->maxChartValue(); @endphp
                @foreach ($this->chartData as $item)
                    <div class="flex items-center gap-3">
                        <span class="w-28 text-xs font-medium text-slate-600 dark:text-slate-300 truncate text-right">{{ $item['label'] }}</span>
                        <div class="flex-1 bg-slate-100 dark:bg-slate-700 rounded-full h-6 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 flex items-center justify-end px-2"
                                style="width: {{ max(1, ($item['value'] / $maxVal) * 100) }}%; background-color: {{ $item['color'] ?? '#4f46e5' }};">
                                <span class="text-xs font-semibold text-white {{ $item['value'] / $maxVal < 0.15 ? 'sr-only' : '' }}">
                                    {{ $reportType === 'deals' || $reportType === 'stage' ? number_format($item['value']) : '£' . number_format($item['value']) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Data Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        @if ($reportType === 'deals')
            {{-- Deals paginated table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Deal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Stage</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Owner</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Company</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($this->rows as $deal)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $deal->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 capitalize">
                                        {{ $deal->stage?->value ?? $deal->stage }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">£{{ number_format($deal->amount) }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $deal->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $deal->companies->first()?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-slate-500 dark:text-slate-400">{{ $deal->created_at?->toDateString() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500">No deals found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
                {{ $this->rows->links(data: ['scrollTo' => false]) }}
            </div>
        @else
            {{-- Aggregated table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            @if ($reportType === 'pipeline' || $reportType === 'stage')
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Stage</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Deals</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total Value</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Avg Margin</th>
                            @elseif ($reportType === 'agency')
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Agency</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Deals</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total Value</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Agency Value</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Avg Margin</th>
                            @elseif ($reportType === 'worker')
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Consultant</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Deals</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total Value</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Avg Margin</th>
                            @elseif ($reportType === 'company')
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Company</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Deals</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total Value</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Avg Margin</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @php $rows = $this->rows; @endphp
                        @forelse ($rows as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                @if ($reportType === 'pipeline' || $reportType === 'stage')
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white capitalize">{{ $row->stage?->value ?? $row->stage }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">{{ number_format($row->count) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">£{{ number_format($row->total_value ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-400">{{ $row->avg_margin ? number_format($row->avg_margin, 1) . '%' : '—' }}</td>
                                @elseif ($reportType === 'agency')
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $row->recruitment_agency }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">{{ number_format($row->count) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">£{{ number_format($row->total_value ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-400">£{{ number_format($row->total_agency_value ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-400">{{ $row->avg_margin ? number_format($row->avg_margin, 1) . '%' : '—' }}</td>
                                @elseif ($reportType === 'worker')
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $row->consultant_name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">{{ number_format($row->count) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">£{{ number_format($row->total_value ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-400">{{ $row->avg_margin ? number_format($row->avg_margin, 1) . '%' : '—' }}</td>
                                @elseif ($reportType === 'company')
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $row->name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">{{ number_format($row->count) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">£{{ number_format($row->total_value ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-400">{{ $row->avg_margin ? number_format($row->avg_margin, 1) . '%' : '—' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500">No data found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
