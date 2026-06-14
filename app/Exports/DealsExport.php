<?php

namespace App\Exports;

use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DealsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param array{
     *     filterDealName: string,
     *     filterOwner: string,
     *     filterContact: string,
     *     filterCompanyName: string,
     *     filterStage: string,
     *     minAmount: string|null,
     *     maxAmount: string|null,
     *     dateFrom: string|null,
     *     dateTo: string|null,
     * } $filters
     */
    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        $query = Deal::query()
            ->with('contacts', 'companies', 'user')
            ->visibleTo(auth()->user());

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if (! empty($this->filters['filterDealName'])) {
            $query->where(
                'name',
                'like',
                '%'.$this->filters['filterDealName'].'%'
            );
        }

        if (! empty($this->filters['filterOwner'])) {
            $query->whereHas(
                'user',
                fn ($q) => $q->where(
                    'name',
                    'like',
                    '%'.$this->filters['filterOwner'].'%'
                )
            );
        }

        if (! empty($this->filters['filterContact'])) {
            $query->whereHas(
                'contacts',
                function ($q) {
                    $q->where('first_name', 'like', '%'.$this->filters['filterContact'].'%')
                        ->orWhere('last_name', 'like', '%'.$this->filters['filterContact'].'%');
                }
            );
        }

        if (! empty($this->filters['filterCompanyName'])) {
            $query->whereHas(
                'companies',
                fn ($q) => $q->where(
                    'name',
                    'like',
                    '%'.$this->filters['filterCompanyName'].'%'
                )
            );
        }

        if (! empty($this->filters['filterStage'])) {
            $query->where(
                'stage',
                $this->filters['filterStage']
            );
        }

        if (! empty($this->filters['minAmount'])) {
            $query->where(
                'amount',
                '>=',
                $this->filters['minAmount']
            );
        }

        if (! empty($this->filters['maxAmount'])) {
            $query->where(
                'amount',
                '<=',
                $this->filters['maxAmount']
            );
        }

        if (! empty($this->filters['dateFrom'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $this->filters['dateFrom']
            );
        }

        if (! empty($this->filters['dateTo'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $this->filters['dateTo']
            );
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Deal Name',
            'Deal Owner',
            'Stage',
            'Amount',
            'Recruitment Agency',
            'Consultant Name',
            'Agency Deal Value',
            'Margin Agreed (%)',
            'Date Sent',
            'Date Signed',
            'Who Signed',
            'MDA Setup',
            'MDA Reference Number',
            'Date Set Up',
            'Remittance Received',
            'Date Logged',
            'Starter Checklist Received',
            'Starter Form',
            'Tax Code',
            'Contract Received Date',
            'NI Number',
            'Bank',
            'Account Number',
            'Sort Code',
            'Primary Contact Name',
            'Primary Contact Email',
            'Primary Contact Phone',
            'Primary Company',
            'Primary Company Email',

            'Created At',
        ];
    }

    /** @param Deal $deal */
    public function map($deal): array
    {
        $contact = $deal->primaryContact();
        $company = $deal->primaryCompany();

        return [
            $deal->id,
            $deal->name,
            $deal->user?->name,
            $deal->stage?->value,
            $deal->amount,
            $deal->recruitment_agency,
            $deal->consultant_name,
            $deal->agency_deal_value,
            $deal->margin_agreed,
            $deal->date_sent ? Carbon::parse($deal->date_sent)->format('d/m/Y') : null,
            $deal->date_signed ? Carbon::parse($deal->date_signed)->format('d/m/Y') : null,
            $deal->who_signed,
            $deal->mda_setup,
            $deal->mda_reference_number,
            $deal->date_set_up ? Carbon::parse($deal->date_set_up)->format('d/m/Y') : null,
            $deal->remittance_received ? 'Yes' : 'No',
            $deal->date_logged ? Carbon::parse($deal->date_logged)->format('d/m/Y') : null,
            $deal->starter_checklist_recieved_date ? Carbon::parse($deal->starter_checklist_recieved_date)->format('d/m/Y') : null,
            $deal->starter_form,
            $deal->tax_code,
            $deal->contract_recieved_date ? Carbon::parse($deal->contract_recieved_date)->format('d/m/Y') : null,
            $contact ? $contact->first_name.' '.$contact->last_name : null,
            $contact?->ni_number,
            $contact?->bank,
            $contact?->account_number,
            $contact?->sort_code,
            $contact?->email,
            $contact?->phone,
            $company?->name,
            $company?->email,
            $deal->created_at->format('d/m/Y'),
        ];
    }
}
