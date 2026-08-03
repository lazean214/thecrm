<?php

namespace App\Livewire;

use App\Exports\RemittanceBreakdownExport;
use App\Exports\RemittanceSummaryExport;
use App\Exports\RemittanceUnifiedExport;
use App\Models\BusinessSetting;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Remittance;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RemittanceReport extends Component
{
    // ── Filters ──────────────────────────────────────────
    public string $dateRange = 'all';

    public string $filterWeekFrom = '';

    public string $filterWeekTo = '';

    public string $filterBiller = '';

    public string $filterCompany = '';

    public string $filterDealOwner = '';

    // ── Data ─────────────────────────────────────────────
    public array $contacts = [];

    public array $companies = [];

    public array $users = [];

    public array $summaryRows = [];

    public array $breakdowns = [];

    public bool $loaded = false;

    // ── Week Mapping ─────────────────────────────────────
    public array $weekMapping = [];

    // ── Stats ────────────────────────────────────────────
    public string $fiscalYearLabel = '';

    public int $activeBillersCount = 0;

    public int $inactiveBillersCount = 0;

    public int $totalBillersCount = 0;

    public float $totalRemittanceValue = 0;

    public float $totalHours = 0;

    public int $totalCompanies = 0;

    public array $billerByOwner = [];

    public array $workersByCompany = [];

    public function mount(): void
    {
        $this->contacts = Contact::orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim($c->first_name.' '.$c->last_name),
            ])
            ->toArray();

        $this->companies = Company::orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        $this->users = User::orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        $this->generateWeekMapping();
    }

    // ── Auto-update on filter change ─────────────────────
    public function updatedDateRange(): void
    {
        $this->applyDateRange();
        $this->generateReport();
    }

    public function updatedFilterWeekFrom(): void
    {
        $this->generateReport();
    }

    public function updatedFilterWeekTo(): void
    {
        $this->generateReport();
    }

    public function updatedFilterBiller(): void
    {
        $this->generateReport();
    }

    public function updatedFilterCompany(): void
    {
        $this->generateReport();
    }

    public function updatedFilterDealOwner(): void
    {
        $this->generateReport();
    }

    public function applyDateRange(): void
    {
        $now = Carbon::now();

        match ($this->dateRange) {
            '30days' => [
                $this->filterWeekFrom = (string) $now->copy()->subDays(30)->weekOfYear,
                $this->filterWeekTo = (string) $now->copy()->weekOfYear,
            ],
            '90days' => [
                $this->filterWeekFrom = (string) $now->copy()->subDays(90)->weekOfYear,
                $this->filterWeekTo = (string) $now->copy()->weekOfYear,
            ],
            'all' => [
                $this->filterWeekFrom = '',
                $this->filterWeekTo = '',
            ],
            default => null,
        };
    }

    private function generateWeekMapping(): void
    {
        $fy = $this->getCurrentFiscalYear();
        $this->fiscalYearLabel = $fy['label'];

        $this->weekMapping = [];
        $weekStart = $fy['start']->copy()->startOfWeek(Carbon::MONDAY);
        $weekNumber = 1;

        while ($weekStart->lte($fy['end'])) {
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            $this->weekMapping[] = [
                'week' => $weekNumber,
                'start' => $weekStart->format('d M Y'),
                'end' => $weekEnd->format('d M Y'),
                'start_date' => $weekStart->toDateString(),
                'end_date' => $weekEnd->toDateString(),
            ];

            $weekStart->addWeek();
            $weekNumber++;
        }
    }

    private function getWeekStartDate(string $weekNumber): ?string
    {
        $week = collect($this->weekMapping)->firstWhere('week', (int) $weekNumber);

        return $week['start_date'] ?? null;
    }

    private function getWeekEndDate(string $weekNumber): ?string
    {
        $week = collect($this->weekMapping)->firstWhere('week', (int) $weekNumber);

        return $week['end_date'] ?? null;
    }

    public function generateReport(): void
    {
        $query = Remittance::with(['contact', 'company', 'owner'])
            ->whereNotNull('contact_id');

        if ($this->filterWeekFrom !== '') {
            $weekStartDate = $this->getWeekStartDate($this->filterWeekFrom);
            if ($weekStartDate) {
                $query->whereDate('we_date', '>=', $weekStartDate);
            }
        }

        if ($this->filterWeekTo !== '') {
            $weekEndDate = $this->getWeekEndDate($this->filterWeekTo);
            if ($weekEndDate) {
                $query->whereDate('we_date', '<=', $weekEndDate);
            }
        }

        if ($this->filterBiller !== '') {
            $query->whereHas('contact', fn ($q) => $q->where('first_name', 'like', '%'.$this->filterBiller.'%')
                ->orWhere('last_name', 'like', '%'.$this->filterBiller.'%'));
        }

        if ($this->filterCompany !== '') {
            $query->where('company_id', (int) $this->filterCompany);
        }

        if ($this->filterDealOwner !== '') {
            $query->where('deal_owner', (int) $this->filterDealOwner);
        }

        $records = $query->orderBy('week_no')->get();

        // ── Build summary: distinct worker + agency + CID per week ──
        $grouped = $records->groupBy(fn ($r) => implode('|', [
            $r->week_no ?? '',
            $r->contact_id ?? '',
            $r->company_id ?? '',
            $r->deal_owner ?? '',
        ]));

        $summaryRows = [];

        foreach ($grouped as $group) {
            $first = $group->first();

            $summaryRows[] = [
                'week_no' => $first->week_no,
                'worker_name' => $first->contact ? trim($first->contact->first_name.' '.$first->contact->last_name) : '—',
                'contact_id' => $first->contact_id,
                'agency' => $first->company?->name ?? '—',
                'company_id' => $first->company_id,
                'cid' => $first->owner?->name ?? '—',
                'deal_owner' => $first->deal_owner,
                'tsv_sum' => $group->sum('amount'),
            ];
        }

        usort($summaryRows, fn ($a, $b) => $a['week_no'] <=> $b['week_no'] ?: strcmp($a['worker_name'], $b['worker_name']));

        $this->summaryRows = $summaryRows;

        // ── Build breakdowns: per worker group ──
        $workerGroups = $records->groupBy(fn ($r) => $r->contact_id);

        $breakdowns = [];

        foreach ($workerGroups as $contactId => $workerRecords) {
            $firstContact = $workerRecords->first()->contact;
            $workerName = $firstContact ? trim($firstContact->first_name.' '.$firstContact->last_name) : '—';

            $workerRows = $workerRecords->map(fn ($r) => [
                'week_no' => $r->week_no,
                'agency' => $r->company?->name ?? '—',
                'cid' => $r->owner?->name ?? '—',
                'shift_date' => $r->shirft_date?->toDateString() ?? '—',
                'we_date' => $r->we_date?->toDateString() ?? '—',
                'hours' => $r->hours ?? 0,
                'rate' => $r->rate ?? 0,
                'tsv' => $r->amount ?? 0,
                'margin' => $r->margin_agreed ?? 0,
                'status' => $r->status ?? '—',
            ])->toArray();

            $breakdowns[] = [
                'contact_id' => $contactId,
                'worker_name' => $workerName,
                'total_tsv' => $workerRecords->sum('amount'),
                'total_hours' => $workerRecords->sum('hours'),
                'rows' => $workerRows,
            ];
        }

        usort($breakdowns, fn ($a, $b) => strcmp($a['worker_name'], $b['worker_name']));

        $this->breakdowns = $breakdowns;

        // ── Calculate Stats ──────────────────────────────────
        $fy = $this->getCurrentFiscalYear();
        $this->fiscalYearLabel = $fy['label'];

        $fyStartStr = $fy['start']->format('Y-m-d');
        $fyEndStr = $fy['end']->format('Y-m-d');

        // All distinct billers across the entire fiscal year (ignoring filters)
        $fyBillers = Remittance::whereNotNull('contact_id')
            ->where('we_date', '>=', $fyStartStr)
            ->where('we_date', '<=', $fyEndStr)
            ->pluck('contact_id')
            ->unique()
            ->filter();

        // Distinct billers in the current filtered result set
        $filteredBillers = $records->pluck('contact_id')->unique()->filter();

        $this->totalBillersCount = $fyBillers->count();
        $this->activeBillersCount = $filteredBillers->count();
        $this->inactiveBillersCount = $fyBillers->diff($filteredBillers)->count();

        $this->totalRemittanceValue = (float) $records->sum('amount');
        $this->totalHours = (float) $records->sum('hours');
        $this->totalCompanies = $records->pluck('company_id')->unique()->filter()->count();

        // ── Biller by Owner ──────────────────────────────────
        $this->billerByOwner = $records->groupBy('deal_owner')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'owner_id' => $first->deal_owner,
                    'owner_name' => $first->owner?->name ?? '—',
                    'active_billers' => $group->pluck('contact_id')->unique()->count(),
                    'total_tsv' => $group->sum('amount'),
                    'total_hours' => $group->sum('hours'),
                ];
            })
            ->sortByDesc('active_billers')
            ->values()
            ->toArray();

        // ── Workers by Company ────────────────────────────────
        $this->workersByCompany = $records->groupBy('company_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'company_id' => $first->company_id,
                    'company_name' => $first->company?->name ?? '—',
                    'worker_count' => $group->pluck('contact_id')->unique()->count(),
                    'total_tsv' => $group->sum('amount'),
                    'total_hours' => $group->sum('hours'),
                ];
            })
            ->sortByDesc('worker_count')
            ->values()
            ->toArray();

        $this->loaded = true;
    }

    private function getCurrentFiscalYear(): array
    {
        $now = Carbon::now();
        $startMonth = (int) BusinessSetting::get('fiscal_year_start_month', 4);
        $startDay = (int) BusinessSetting::get('fiscal_year_start_day', 6);
        $endMonth = (int) BusinessSetting::get('fiscal_year_end_month', 4);
        $endDay = (int) BusinessSetting::get('fiscal_year_end_day', 5);

        $fyStart = Carbon::create($now->year, $startMonth, $startDay)->startOfDay();
        $fyEnd = Carbon::create($now->year + 1, $endMonth, $endDay)->endOfDay();

        if ($now->lt($fyStart)) {
            $fyStart = $fyStart->subYear();
            $fyEnd = $fyEnd->subYear();
        }

        return [
            'start' => $fyStart,
            'end' => $fyEnd,
            'label' => $fyStart->year.'/'.$fyEnd->year,
        ];
    }

    public function resetFilters(): void
    {
        $this->dateRange = 'all';
        $this->filterWeekFrom = '';
        $this->filterWeekTo = '';
        $this->filterBiller = '';
        $this->filterCompany = '';
        $this->filterDealOwner = '';
        $this->summaryRows = [];
        $this->breakdowns = [];
        $this->fiscalYearLabel = '';
        $this->activeBillersCount = 0;
        $this->inactiveBillersCount = 0;
        $this->totalBillersCount = 0;
        $this->totalRemittanceValue = 0;
        $this->totalHours = 0;
        $this->totalCompanies = 0;
        $this->billerByOwner = [];
        $this->workersByCompany = [];
        $this->loaded = false;
    }

    public function exportSummary(): BinaryFileResponse
    {
        $filename = 'remittance-summary-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new RemittanceSummaryExport($this->summaryRows), $filename);
    }

    public function exportBreakdown(): BinaryFileResponse
    {
        $filename = 'remittance-breakdown-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new RemittanceBreakdownExport($this->breakdowns), $filename);
    }

    public function exportUnified(): BinaryFileResponse
    {
        $filename = 'remittance-unified-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new RemittanceUnifiedExport($this->billerByOwner, $this->workersByCompany, $this->breakdowns),
            $filename
        );
    }

    public function render()
    {
        return view('livewire.remittance-report');
    }
}
