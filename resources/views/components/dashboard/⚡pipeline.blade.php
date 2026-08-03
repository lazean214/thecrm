<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Deal;
use App\Models\User;
use App\Models\Company;
use App\Models\Contact;
use App\Enums\DealStage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public ?int $filterUserId = null;
    public ?int $filterCompanyId = null;
    public ?int $filterContactId = null;
    public string $filterStage = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';
    public string $reportView = 'master';
    public int $perPage = 15;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user && $user->isSalesTeam() && !$user->isAdmin()) {
            $this->filterUserId = $user->id;
        }
    }

    public function updatedFilterUserId(): void
    {
        $this->resetPage();
    }
    public function updatedFilterCompanyId(): void
    {
        $this->resetPage();
    }
    public function updatedFilterContactId(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStage(): void
    {
        $this->resetPage();
    }
    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
    }
    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
    public function updatedReportView(): void
    {
        $this->resetPage();
    }

    public function visibleTo(): mixed
    {
        return Deal::visibleTo(Auth::user());
    }

    #[Computed]
    public function deals()
    {
        return $this->visibleTo()
            ->with(['user:id,name', 'companies', 'contacts'])
            ->when($this->filterUserId, fn($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->filterStage !== '', fn($q) => $q->where('stage', $this->filterStage))
            ->when($this->filterCompanyId, fn($q) => $q->whereHas('companies', fn($q2) => $q2->where('companies.id', $this->filterCompanyId)))
            ->when($this->filterContactId, fn($q) => $q->whereHas('contacts', fn($q2) => $q2->where('contacts.id', $this->filterContactId)))
            ->when($this->filterDateFrom !== '', fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo !== '', fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function totalPipelineValue(): string
    {
        $total = $this->getBaseQuery()->sum('amount') ?? 0;
        return number_format((float) $total, 2);
    }

    #[Computed]
    public function totalActiveDeals(): int
    {
        return $this->getBaseQuery()->count();
    }

    #[Computed]
    public function averageMargin(): string
    {
        $avg = $this->getBaseQuery()->avg('margin_agreed') ?? 0;
        return number_format((float) $avg, 2);
    }

    #[Computed]
    public function stageDistribution(): array
    {
        return $this->getBaseQuery()
            ->selectRaw('stage, count(*) as count, sum(amount) as total_value')
            ->groupBy('stage')
            ->get()
            ->map(
                fn($r) => [
                    'stage' => $r->stage,
                    'count' => (int) $r->count,
                    'total' => (float) ($r->total_value ?? 0),
                ],
            )
            ->toArray();
    }

    #[Computed]
    public function weeklySummary()
    {
        $startDate = $this->filterDateFrom ? Carbon::parse($this->filterDateFrom) : Carbon::now()->subMonths(3);
        $endDate = $this->filterDateTo ? Carbon::parse($this->filterDateTo) : Carbon::now();

        $deals = $this->visibleTo()
            ->when($this->filterUserId, fn($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->filterCompanyId, fn($q) => $q->whereHas('companies', fn($q2) => $q2->where('companies.id', $this->filterCompanyId)))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $weeks = $deals->groupBy(function ($deal) {
            return Carbon::parse($deal->created_at)->format('o-W');
        });

        return $weeks
            ->take(12)
            ->sortKeysDesc()
            ->map(function ($weekDeals, $weekKey) {
                $weekStart = $weekDeals->min(fn($d) => $d->created_at);
                return (object) [
                    'week_key' => $weekKey,
                    'week_start' => $weekStart,
                    'paid_deals' => $weekDeals->where('stage', 'paid')->count(),
                    'paid_amount' => $weekDeals->where('stage', 'paid')->sum('amount'),
                    'created_this_week' => $weekDeals
                        ->where('stage', 'doc_sent')
                        ->where('created_at', '>=', Carbon::now()->subDays(7))
                        ->count(),
                    'total_deals' => $weekDeals->count(),
                    'week_range' => Carbon::parse($weekStart)->format('d M') . ' - ' . Carbon::parse($weekStart)->addDays(6)->format('d M Y'),
                ];
            })
            ->values()
            ->all();
    }

    #[Computed]
    public function tsvReport()
    {
        $query = $this->getBaseQuery()->with(['user:id,name', 'companies', 'contacts']);

        return [
            'by_company' => $this->getTsvByCompany($query),
            'by_contact' => $this->getTsvByContact($query),
            'by_user' => $this->getTsvByUser($query),
            'by_deal' => $this->getTsvByDeal($query),
        ];
    }

    private function getTsvByCompany($query)
    {
        $deals = $query->get();
        $summary = [];

        foreach ($deals as $deal) {
            foreach ($deal->companies as $company) {
                if (!isset($summary[$company->id])) {
                    $summary[$company->id] = [
                        'name' => $company->name,
                        'total_value' => 0,
                        'deal_count' => 0,
                        'avg_margin' => [],
                    ];
                }
                $summary[$company->id]['total_value'] += (float) ($deal->amount ?? 0);
                $summary[$company->id]['deal_count']++;
                if ($deal->margin_agreed !== null) {
                    $summary[$company->id]['avg_margin'][] = (float) $deal->margin_agreed;
                }
            }
        }

        return collect($summary)
            ->map(
                fn($item) => [
                    'name' => $item['name'],
                    'total_value' => (float) $item['total_value'],
                    'deal_count' => (int) $item['deal_count'],
                    'avg_margin' => !empty($item['avg_margin']) ? number_format(array_sum($item['avg_margin']) / count($item['avg_margin']), 2) : '0.00',
                ],
            )
            ->sortByDesc('total_value')
            ->values();
    }

    private function getTsvByContact($query)
    {
        $deals = $query->get();
        $summary = [];

        foreach ($deals as $deal) {
            foreach ($deal->contacts as $contact) {
                if (!isset($summary[$contact->id])) {
                    $summary[$contact->id] = [
                        'name' => $contact->first_name . ' ' . $contact->last_name,
                        'email' => $contact->email ?? '—',
                        'company' => $contact->company?->name ?? '—',
                        'total_value' => 0,
                        'deal_count' => 0,
                    ];
                }
                $summary[$contact->id]['total_value'] += (float) ($deal->amount ?? 0);
                $summary[$contact->id]['deal_count']++;
            }
        }

        return collect($summary)->sortByDesc('total_value')->values();
    }

    private function getTsvByUser($query)
    {
        return $query
            ->get()
            ->groupBy('user_id')
            ->map(
                fn($deals, $userId) => [
                    'name' => $deals->first()->user?->name ?? 'Unassigned',
                    'total_value' => (float) $deals->sum('amount'),
                    'deal_count' => $deals->count(),
                    'avg_margin' => number_format((float) ($deals->avg('margin_agreed') ?? 0), 2),
                    'avg_deal_size' => number_format((float) ($deals->avg('amount') ?? 0), 2),
                ],
            )
            ->sortByDesc('total_value')
            ->values();
    }

    private function getTsvByDeal($query)
    {
        return $query->get()->map(
            fn($deal) => [
                'name' => $deal->name ?? '—',
                'owner' => $deal->user?->name ?? '—',
                'company' => $deal->companies->first()?->name ?? '—',
                'contact' => $deal->contacts->first()?->name ?? '—',
                'stage' => ucwords(is_object($deal->stage) ? $deal->stage->value : $deal->stage ?? 'unknown'),
                'amount' => (float) ($deal->amount ?? 0),
                'margin' => $deal->margin_agreed !== null ? (float) $deal->margin_agreed : null,
                'created_date' => $deal->created_at?->format('Y-m-d') ?? '—',
            ],
        );
    }

    #[Computed]
    public function dealHistory()
    {
        return $this->visibleTo()
            ->with(['user:id,name', 'companies'])
            ->whereIn('stage', [DealStage::PAID, DealStage::COMPLIANT])
            ->when($this->filterUserId, fn($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->filterCompanyId, fn($q) => $q->whereHas('companies', fn($q2) => $q2->where('companies.id', $this->filterCompanyId)))
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(function ($deal) {
                $deal->history_type = (is_object($deal->stage) ? $deal->stage->value : $deal->stage) === 'paid' ? 'paid' : 'completed';
                return $deal;
            });
    }

    #[Computed]
    public function users()
    {
        $user = Auth::user();

        if ($user && $user->isSalesTeam() && !$user->isAdmin()) {
            return collect([$user]);
        }

        return User::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function companies()
    {
        return Company::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function contacts()
    {
        return Contact::orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }

    #[Computed]
    public function stages(): array
    {
        return DealStage::cases();
    }

    private function getBaseQuery()
    {
        return $this->visibleTo()
            ->when($this->filterUserId, fn($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->filterStage !== '', fn($q) => $q->where('stage', $this->filterStage))
            ->when($this->filterCompanyId, fn($q) => $q->whereHas('companies', fn($q2) => $q2->where('companies.id', $this->filterCompanyId)))
            ->when($this->filterContactId, fn($q) => $q->whereHas('contacts', fn($q2) => $q2->where('contacts.id', $this->filterContactId)))
            ->when($this->filterDateFrom !== '', fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo !== '', fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo));
    }

    public function resetFilters(): void
    {
        $this->filterUserId = null;
        $this->filterCompanyId = null;
        $this->filterContactId = null;
        $this->filterStage = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->reportView = 'master';
        $this->resetPage();

        $user = Auth::user();
        if ($user && $user->isSalesTeam() && !$user->isAdmin()) {
            $this->filterUserId = $user->id;
        }
    }

    public function exportTsv($type)
    {
        $data = $this->tsvReport[$type] ?? [];
        if (empty($data)) {
            return;
        }

        $filename = "tsv_report_{$type}_" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($data, $type) {
            $file = fopen('php://output', 'w');

            switch ($type) {
                case 'by_company':
                    fputcsv($file, ['Company', 'Total Value (£)', 'Deal Count', 'Avg Margin (%)']);
                    foreach ($data as $row) {
                        fputcsv($file, [$row['name'], number_format((float) $row['total_value'], 2), $row['deal_count'], $row['avg_margin']]);
                    }
                    break;
                case 'by_contact':
                    fputcsv($file, ['Contact', 'Email', 'Company', 'Total Value (£)', 'Deal Count']);
                    foreach ($data as $row) {
                        fputcsv($file, [$row['name'], $row['email'], $row['company'], number_format((float) $row['total_value'], 2), $row['deal_count']]);
                    }
                    break;
                case 'by_user':
                    fputcsv($file, ['User', 'Total Value (£)', 'Deal Count', 'Avg Margin (%)', 'Avg Deal Size (£)']);
                    foreach ($data as $row) {
                        fputcsv($file, [$row['name'], number_format((float) $row['total_value'], 2), $row['deal_count'], $row['avg_margin'], $row['avg_deal_size']]);
                    }
                    break;
                case 'by_deal':
                    fputcsv($file, ['Deal Name', 'Owner', 'Company', 'Contact', 'Stage', 'Amount (£)', 'Margin (%)', 'Created Date']);
                    foreach ($data as $row) {
                        fputcsv($file, [$row['name'], $row['owner'], $row['company'], $row['contact'], $row['stage'], number_format((float) $row['amount'], 2), $row['margin'] !== null ? (string) $row['margin'] : '—', $row['created_date']]);
                    }
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
};
?>

<div class="flex flex-col gap-6" wire:poll.60s>
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ Auth::user()?->name ? 'Good ' . (now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening')) . ', ' . Auth::user()->name : 'Dashboard' }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ now()->format('l, jS F Y') }}
            </p>
        </div>
        <a href="{{ route('deals') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Create Deal
        </a>
    </div>

    {{-- View Toggle --}}
    <div class="flex gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
        <button wire:click="$set('reportView', 'master')"
            class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-all {{ $reportView === 'master' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' }}">
            <span class="hidden sm:inline">Master Report</span>
            <span class="sm:hidden">Master</span>
        </button>
        <button wire:click="$set('reportView', 'weekly')"
            class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-all {{ $reportView === 'weekly' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' }}">
            <span class="hidden sm:inline">Weekly Summary</span>
            <span class="sm:hidden">Weekly</span>
        </button>
        <button wire:click="$set('reportView', 'tsv')"
            class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-all {{ $reportView === 'tsv' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700' }}">
            <span class="hidden sm:inline">TSV Report</span>
            <span class="sm:hidden">TSV</span>
        </button>
    </div>

    {{-- Filter Bar --}}
    <div
        class="flex flex-wrap items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
        <div
            class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mr-1">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
            </svg>
            Filters
        </div>

        <div class="flex flex-col gap-1">
            <label
                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Owner</label>
            <select wire:model.live="filterUserId"
                class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">
                <option value="">All Owners</option>
                @foreach ($this->users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label
                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Company</label>
            <select wire:model.live="filterCompanyId"
                class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">
                <option value="">All Companies</option>
                @foreach ($this->companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label
                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Contact</label>
            <select wire:model.live="filterContactId"
                class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">
                <option value="">All Contacts</option>
                @foreach ($this->contacts as $contact)
                    <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label
                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Stage</label>
            <select wire:model.live="filterStage"
                class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">
                <option value="">All Stages</option>
                @foreach ($this->stages as $stage)
                    <option value="{{ $stage->value }}">{{ ucwords($stage->value) }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">From</label>
            <input type="date" wire:model.live="filterDateFrom"
                class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">To</label>
            <input type="date" wire:model.live="filterDateTo"
                class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">
        </div>

        @if (
            $filterUserId ||
                $filterCompanyId ||
                $filterContactId ||
                $filterStage !== '' ||
                $filterDateFrom !== '' ||
                $filterDateTo !== '')
            <button wire:click="resetFilters"
                class="inline-flex items-center gap-1.5 self-end rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Clear
            </button>
        @endif
    </div>

    {{-- KPI Cards --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
            <div
                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pipeline Value
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">£{{ $this->totalPipelineValue }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
            <div
                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Active Deals
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $this->totalActiveDeals }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
            <div
                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Avg Margin
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $this->averageMargin }}%</p>
        </div>
    </div>

    {{-- Stage Distribution --}}
    @if (count($this->stageDistribution) > 0)
        @php $maxStageCount = max(array_column($this->stageDistribution, 'count')); @endphp
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-4">Stage
                Distribution</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->stageDistribution as $item)
                    <div class="flex items-center gap-3">
                        <span
                            class="w-28 text-xs font-medium text-slate-700 dark:text-slate-300 truncate text-right">{{ ucwords($item['stage']) }}</span>
                        <div class="flex-1 bg-slate-100 dark:bg-slate-700 rounded-full h-6 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 flex items-center justify-end px-2"
                                style="width: {{ max(1, ($item['count'] / $maxStageCount) * 100) }}%; background-color: {{ ['#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626', '#7c3aed'][$loop->index % 6] }};">
                                <span
                                    class="text-xs font-semibold text-white {{ $item['count'] / $maxStageCount < 0.15 ? 'sr-only' : '' }}">
                                    {{ $item['count'] }}
                                </span>
                            </div>
                        </div>
                        <span
                            class="w-16 text-xs text-slate-500 dark:text-slate-400">£{{ number_format($item['total']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Master Report View --}}
    @if ($reportView === 'master')
        <div
            class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Deal Master Report</h2>
                <div class="flex items-center gap-3">
                    <select wire:model.live="perPage"
                        class="rounded-lg border border-slate-200 dark:border-slate-600 px-2 py-1 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="10">10 per page</option>
                        <option value="15">15 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                    </select>
                    <span class="text-xs text-slate-400">{{ $this->deals->total() }} total</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-left">
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Deal</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Owner</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Company</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Stage</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Value</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($this->deals as $deal)
                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                                    <a href="{{ route('deals.show', $deal) }}"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $deal->name }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-400">
                                    {{ $deal->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-400">
                                    {{ $deal->companies->first()?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 capitalize">
                                        {{ is_object($deal->stage) ? $deal->stage->value : $deal->stage }}
                                    </span>
                                </td>
                                <td
                                    class="px-5 py-3 text-right font-medium tabular-nums text-slate-900 dark:text-white">
                                    £{{ number_format((float) $deal->amount, 2) }}</td>
                                <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                                    {{ $deal->created_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="px-5 py-10 text-center text-sm text-slate-400 dark:text-slate-500">
                                    No deals found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-700 px-5 py-3">
                {{ $this->deals->links() }}
            </div>
        </div>

        {{-- Recent Deal History --}}
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
            <div class="border-b border-slate-100 dark:border-slate-700 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Recent Deal History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-left">
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Deal</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Owner</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Company</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Status</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Value</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($this->dealHistory as $deal)
                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">{{ $deal->name }}
                                </td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-400">
                                    {{ $deal->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-400">
                                    {{ $deal->companies->first()?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $deal->history_type === 'paid' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                        {{ ucfirst($deal->history_type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-medium text-slate-900 dark:text-white">
                                    £{{ number_format((float) $deal->amount, 2) }}</td>
                                <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                                    {{ $deal->updated_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="px-5 py-10 text-center text-sm text-slate-400 dark:text-slate-500">
                                    No recent deal activity.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Weekly Summary View --}}
    @if ($reportView === 'weekly')
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
            <div class="border-b border-slate-100 dark:border-slate-700 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Weekly Deal Summary</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-left">
                            <th class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Week</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Total</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Created</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Paid</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                Paid Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($this->weeklySummary as $week)
                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                                    {{ $week->week_range }}</td>
                                <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                    {{ $week->total_deals }}</td>
                                <td class="px-5 py-3 text-right text-emerald-600 dark:text-emerald-400 font-medium">
                                    {{ $week->created_this_week }}</td>
                                <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                    {{ $week->paid_deals }}</td>
                                <td class="px-5 py-3 text-right font-medium text-slate-900 dark:text-white">
                                    £{{ number_format((float) $week->paid_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-5 py-10 text-center text-sm text-slate-400 dark:text-slate-500">
                                    No weekly data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- TSV Report View --}}
    @if ($reportView === 'tsv')
        <div class="space-y-6">
            {{-- By Company --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">TSV by Company</h2>
                    <button wire:click="exportTsv('by_company')"
                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSV
                        </span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-left">
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Company</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Value</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Deals</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Avg Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse($this->tsvReport['by_company'] as $company)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                                        {{ $company['name'] }}</td>
                                    <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                        £{{ number_format($company['total_value'], 2) }}</td>
                                    <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                        {{ $company['deal_count'] }}</td>
                                    <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-400">
                                        {{ $company['avg_margin'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4"
                                        class="px-5 py-10 text-center text-slate-400 dark:text-slate-500">No data
                                        available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- By Contact --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">TSV by Contact</h2>
                    <button wire:click="exportTsv('by_contact')"
                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSV
                        </span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-left">
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Contact</th>
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Email</th>
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Company</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Value</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Deals</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse($this->tsvReport['by_contact'] as $contact)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                                        {{ $contact['name'] }}</td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-400">{{ $contact['email'] }}
                                    </td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-400">{{ $contact['company'] }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                        £{{ number_format($contact['total_value'], 2) }}</td>
                                    <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                        {{ $contact['deal_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-5 py-10 text-center text-slate-400 dark:text-slate-500">No data
                                        available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- By User --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">TSV by User</h2>
                    <button wire:click="exportTsv('by_user')"
                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSV
                        </span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-left">
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    User</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Value</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Deals</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Avg Margin</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Avg Deal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse($this->tsvReport['by_user'] as $user)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                                        {{ $user['name'] }}</td>
                                    <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                        £{{ $user['total_value'] }}</td>
                                    <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                        {{ $user['deal_count'] }}</td>
                                    <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-400">
                                        {{ $user['avg_margin'] }}%</td>
                                    <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                        £{{ $user['avg_deal_size'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-5 py-10 text-center text-slate-400 dark:text-slate-500">No data
                                        available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Detailed Deal List --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Detailed Deal List</h2>
                    <button wire:click="exportTsv('by_deal')"
                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSV
                        </span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-left">
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Deal</th>
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Owner</th>
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Company</th>
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Contact</th>
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Stage</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Amount</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Margin</th>
                                <th
                                    class="px-5 py-3 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                                    Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse($this->tsvReport['by_deal'] as $deal)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                                        {{ $deal['name'] }}</td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-400">{{ $deal['owner'] }}</td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-400">{{ $deal['company'] }}
                                    </td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-400">{{ $deal['contact'] }}
                                    </td>
                                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ $deal['stage'] }}</td>
                                    <td class="px-5 py-3 text-right text-slate-700 dark:text-slate-300">
                                        £{{ number_format($deal['amount'], 2) }}</td>
                                    <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-400">
                                        {{ $deal['margin'] !== null ? $deal['margin'] . '%' : '—' }}</td>
                                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                                        {{ $deal['created_date'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8"
                                        class="px-5 py-10 text-center text-slate-400 dark:text-slate-500">No data
                                        available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
