<?php

namespace App\Livewire;

use App\Models\BusinessSetting;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Remittance;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RemittanceTable extends Component
{
    public array $rows = [];

    public array $contacts = [];

    public array $companies = [];

    public array $users = [];

    public string $savingStatus = '';

    public string $dateRange = 'month'; // week, month, quarter, year, all, custom

    // ── Filters ──────────────────────────────────────────
    public string $filterWeekNo = '';

    public string $filterCompany = '';

    public string $filterDealOwner = '';

    public string $filterStatus = '';

    public string $filterBiller = '';

    public string $filterWeDateFrom = '';

    public string $filterWeDateTo = '';

    public string $filterShiftDateFrom = '';

    public string $filterShiftDateTo = '';

    public string $filterCompliance = '';

    // ── Week Mapping ─────────────────────────────────────
    public array $weekMapping = [];

    public function mount(): void
    {
        $this->contacts = Contact::orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'ni_number', 'bank', 'account_number', 'sort_code'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim($c->first_name.' '.$c->last_name),
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'ni_number' => $c->ni_number,
                'bank' => $c->bank,
                'account_number' => $c->account_number,
                'sort_code' => $c->sort_code,
            ])
            ->toArray();

        $this->companies = Company::orderBy('name')
            ->get(['id', 'name', 'email', 'phone'])
            ->toArray();

        $this->users = User::orderBy('name')
            ->get(['id', 'name', 'email'])
            ->toArray();

        $this->generateWeekMapping();
        $this->applyDateRange();
        $this->refreshRows();
    }

    // ── Date Range Presets ───────────────────────────────

    public function updatedDateRange(): void
    {
        $this->applyDateRange();
        $this->refreshRows();
    }

    public function applyDateRange(): void
    {
        $now = Carbon::now();

        match ($this->dateRange) {
            'week' => [
                $this->filterWeDateFrom = $now->copy()->startOfWeek()->toDateString(),
                $this->filterWeDateTo = $now->copy()->endOfWeek()->toDateString(),
            ],
            'month' => [
                $this->filterWeDateFrom = $now->copy()->startOfMonth()->toDateString(),
                $this->filterWeDateTo = $now->copy()->endOfMonth()->toDateString(),
            ],
            'quarter' => [
                $this->filterWeDateFrom = $now->copy()->startOfQuarter()->toDateString(),
                $this->filterWeDateTo = $now->copy()->endOfQuarter()->toDateString(),
            ],
            'year' => [
                $this->filterWeDateFrom = $now->copy()->startOfYear()->toDateString(),
                $this->filterWeDateTo = $now->copy()->endOfYear()->toDateString(),
            ],
            'all' => [
                $this->filterWeDateFrom = '',
                $this->filterWeDateTo = '',
            ],
            'custom' => null, // don't touch from/to
        };
    }

    // ── Filter Methods ───────────────────────────────────

    public function updatedFilterWeekNo(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterCompany(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterDealOwner(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterStatus(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterBiller(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterWeDateFrom(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterWeDateTo(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterShiftDateFrom(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterShiftDateTo(): void
    {
        $this->applyFilters();
    }

    public function updatedFilterCompliance(): void
    {
        $this->applyFilters();
    }

    public function resetFilters(): void
    {
        $this->dateRange = 'month';
        $this->filterWeekNo = '';
        $this->filterCompany = '';
        $this->filterDealOwner = '';
        $this->filterStatus = '';
        $this->filterBiller = '';
        $this->filterWeDateFrom = '';
        $this->filterWeDateTo = '';
        $this->filterShiftDateFrom = '';
        $this->filterShiftDateTo = '';
        $this->filterCompliance = '';

        $this->applyDateRange();
        $this->refreshRows();
    }

    private function generateWeekMapping(): void
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

        $this->weekMapping = [];
        $weekStart = $fyStart->copy()->startOfWeek(Carbon::MONDAY);
        $weekNumber = 1;

        while ($weekStart->lte($fyEnd)) {
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

    // ── Row Management ───────────────────────────────────

    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'contact_id' => '',
            'company_id' => '',
            'deal_owner' => auth()->id(),
            'week_no' => '',
            'amount' => '',
            'date_added' => now()->toDateString(),
            'status' => '',
            'margin_agreed' => '',
            'hours' => '',
            'rate' => '',
            'we_date' => '',
            'shirft_date' => '',
            'remarks' => '',
            'from' => '',
            'invoice' => '',
            'batch' => '',
            'agency_funds' => '',
            'payment_status' => '',
            'compliance' => false,
        ];
    }

    public function removeRow(int $index): void
    {
        if (isset($this->rows[$index]['id']) && $this->rows[$index]['id']) {
            Remittance::findOrFail($this->rows[$index]['id'])->delete();
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if (empty($this->rows)) {
            $this->addRow();
        }
    }

    public function duplicateRow(int $index): void
    {
        $row = $this->rows[$index];
        $row['id'] = null;

        array_splice($this->rows, $index + 1, 0, [$row]);
    }

    public function onContactSelected(int $index, string $contactId): void
    {
        $contact = Contact::with(['deals.companies', 'companies'])->find((int) $contactId);

        if (! $contact) {
            return;
        }

        $this->rows[$index]['contact_id'] = (int) $contactId;

        $primaryDeal = $contact->deals->first();
        if ($primaryDeal) {
            $this->rows[$index]['hours'] = $primaryDeal->hours ?? '';
            $this->rows[$index]['rate'] = $primaryDeal->rate ?? '';
            $this->rows[$index]['margin_agreed'] = $primaryDeal->margin_agreed ?? '';
            $this->rows[$index]['deal_owner'] = $primaryDeal->user_id ?? auth()->id();
            $this->rows[$index]['amount'] = $primaryDeal->amount ?? '';

            $stage = $primaryDeal->stage?->value ?? $primaryDeal->stage;
            $this->rows[$index]['compliance'] = in_array($stage, ['ready for payment', 'paid']);

            $primaryCompany = $primaryDeal->companies->first();
            if ($primaryCompany) {
                $this->rows[$index]['company_id'] = $primaryCompany->id;
            }
        }

        if (empty($this->rows[$index]['company_id']) && $contact->companies->first()) {
            $this->rows[$index]['company_id'] = $contact->companies->first()->id;
        }
    }

    public function updateCell(int $index, string $field, mixed $value): void
    {
        if (isset($this->rows[$index])) {
            $this->rows[$index][$field] = $value;
        }
    }

    // ── Save ─────────────────────────────────────────────

    public function saveAll(): void
    {
        $this->savingStatus = 'saving';

        foreach ($this->rows as $row) {
            if (empty($row['contact_id'])) {
                continue;
            }

            $data = [
                'contact_id' => (int) $row['contact_id'],
                'user_id' => auth()->id(),
                'company_id' => ! empty($row['company_id']) ? (int) $row['company_id'] : null,
                'deal_owner' => ! empty($row['deal_owner']) ? (int) $row['deal_owner'] : auth()->id(),
                'week_no' => ! empty($row['week_no']) ? (int) $row['week_no'] : null,
                'amount' => ! empty($row['amount']) ? (float) $row['amount'] : null,
                'date_added' => ! empty($row['date_added']) ? $row['date_added'] : null,
                'status' => $row['status'] ?: null,
                'margin_agreed' => ! empty($row['margin_agreed']) ? (float) $row['margin_agreed'] : null,
                'hours' => ! empty($row['hours']) ? (float) $row['hours'] : null,
                'rate' => ! empty($row['rate']) ? (float) $row['rate'] : null,
                'we_date' => ! empty($row['we_date']) ? $row['we_date'] : null,
                'shirft_date' => ! empty($row['shirft_date']) ? $row['shirft_date'] : null,
                'remarks' => $row['remarks'] ?: null,
                'from' => $row['from'] ?: null,
                'invoice' => $row['invoice'] ?: null,
                'batch' => $row['batch'] ?: null,
                'agency_funds' => $row['agency_funds'] ?: null,
                'payment_status' => $row['payment_status'] ?: null,
                'compliance' => (bool) $row['compliance'],
            ];

            if (! empty($row['id'])) {
                Remittance::findOrFail($row['id'])->update($data);
            } else {
                Remittance::create($data);
            }
        }

        $this->savingStatus = 'saved';
        $this->dispatch('notify', type: 'success', message: 'Remittances saved successfully');

        $this->refreshRows();
    }

    // ── Load & Filter ────────────────────────────────────

    public function refreshRows(): void
    {
        $query = Remittance::with(['contact', 'company', 'owner']);

        // Apply filters
        if ($this->filterWeekNo !== '') {
            $weekStartDate = $this->getWeekStartDate($this->filterWeekNo);
            $weekEndDate = $this->getWeekEndDate($this->filterWeekNo);
            if ($weekStartDate && $weekEndDate) {
                $query->whereDate('we_date', '>=', $weekStartDate)
                    ->whereDate('we_date', '<=', $weekEndDate);
            }
        }

        if ($this->filterCompany !== '') {
            $query->where('company_id', (int) $this->filterCompany);
        }

        if ($this->filterDealOwner !== '') {
            $query->where('deal_owner', (int) $this->filterDealOwner);
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterBiller !== '') {
            $query->whereHas('contact', fn ($q) => $q->where('first_name', 'like', '%'.$this->filterBiller.'%')
                ->orWhere('last_name', 'like', '%'.$this->filterBiller.'%'));
        }

        if ($this->filterWeDateFrom !== '') {
            $query->whereDate('we_date', '>=', $this->filterWeDateFrom);
        }

        if ($this->filterWeDateTo !== '') {
            $query->whereDate('we_date', '<=', $this->filterWeDateTo);
        }

        if ($this->filterShiftDateFrom !== '') {
            $query->whereDate('shirft_date', '>=', $this->filterShiftDateFrom);
        }

        if ($this->filterShiftDateTo !== '') {
            $query->whereDate('shirft_date', '<=', $this->filterShiftDateTo);
        }

        if ($this->filterCompliance !== '') {
            $query->where('compliance', $this->filterCompliance === '1');
        }

        $remittances = $query->latest()->get();

        $this->rows = $remittances->map(fn ($r) => [
            'id' => $r->id,
            'contact_id' => $r->contact_id,
            'company_id' => $r->company_id,
            'deal_owner' => $r->deal_owner,
            'week_no' => $r->week_no,
            'amount' => $r->amount,
            'date_added' => $r->date_added?->toDateString() ?? '',
            'status' => $r->status ?? '',
            'margin_agreed' => $r->margin_agreed,
            'hours' => $r->hours,
            'rate' => $r->rate,
            'we_date' => $r->we_date?->toDateString() ?? '',
            'shirft_date' => $r->shirft_date?->toDateString() ?? '',
            'remarks' => $r->remarks ?? '',
            'from' => $r->from ?? '',
            'invoice' => $r->invoice ?? '',
            'batch' => $r->batch ?? '',
            'agency_funds' => $r->agency_funds ?? '',
            'payment_status' => $r->payment_status ?? '',
            'compliance' => (bool) $r->compliance,
        ])->toArray();

        if (empty($this->rows)) {
            $this->addRow();
        }
    }

    public function applyFilters(): void
    {
        $this->refreshRows();
    }

    // ── Helpers ──────────────────────────────────────────

    public function getContactName($contactId): string
    {
        $contact = collect($this->contacts)->firstWhere('id', (int) $contactId);

        return $contact ? $contact['name'] : '';
    }

    public function getContactNi($contactId): string
    {
        $contact = collect($this->contacts)->firstWhere('id', (int) $contactId);

        return $contact ? ($contact['ni_number'] ?? '') : '';
    }

    public function getCompanyName($companyId): string
    {
        $company = collect($this->companies)->firstWhere('id', (int) $companyId);

        return $company ? $company['name'] : '';
    }

    public function getUserName($userId): string
    {
        $user = collect($this->users)->firstWhere('id', (int) $userId);

        return $user ? $user['name'] : '';
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->dateRange !== 'month'
            || $this->filterWeekNo !== ''
            || $this->filterCompany !== ''
            || $this->filterDealOwner !== ''
            || $this->filterStatus !== ''
            || $this->filterBiller !== ''
            || $this->filterShiftDateFrom !== ''
            || $this->filterShiftDateTo !== ''
            || $this->filterCompliance !== '';
    }

    public function render()
    {
        return view('livewire.remittance-table');
    }
}
