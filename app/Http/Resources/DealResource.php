<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'stage' => $this->stage?->value,
            'recruitment_agency' => $this->recruitment_agency,
            'consultant_name' => $this->consultant_name,
            'agency_deal_value' => $this->agency_deal_value,
            'margin_agreed' => $this->margin_agreed,
            'date_sent' => $this->date_sent,
            'date_signed' => $this->date_signed,
            'who_signed' => $this->who_signed,
            'mda_setup' => $this->mda_setup,
            'mda_reference_number' => $this->mda_reference_number,
            'date_set_up' => $this->date_set_up,
            'remittance_received' => $this->remittance_received,
            'date_logged' => $this->date_logged,
            'user_id' => $this->user_id,
            'starter_checklist_recieved_date' => $this->starter_checklist_recieved_date,
            'starter_form' => $this->starter_form,
            'tax_code' => $this->tax_code,
            'contract_recieved_date' => $this->contract_recieved_date,
            'contacts' => $this->whenLoaded('contacts', fn () => $this->contacts->map(
                fn ($contact) => array_merge(
                    $contact->toArray(),
                    ['pivot' => ['is_primary' => (bool) $contact->pivot?->is_primary]],
                ),
            )),
            'companies' => $this->whenLoaded('companies', fn () => $this->companies->map(
                fn ($company) => array_merge(
                    $company->toArray(),
                    ['pivot' => [
                        'is_primary' => (bool) $company->pivot?->is_primary,
                        'agency_deal_value' => $company->pivot?->agency_deal_value,
                        'margin_agreed' => $company->pivot?->margin_agreed,
                    ]],
                ),
            )),
            'user' => $this->whenLoaded('user'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
