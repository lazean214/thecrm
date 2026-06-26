<?php

namespace App\Http\Requests;

use App\Enums\DealStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['nullable', Rule::enum(DealStage::class)],
            'recruitment_agency' => ['nullable', 'string', 'max:255'],
            'consultant_name' => ['nullable', 'string', 'max:255'],
            'agency_deal_value' => ['nullable', 'numeric', 'min:0'],
            'margin_agreed' => ['nullable', 'numeric'],
            'date_sent' => ['nullable', 'date'],
            'date_signed' => ['nullable', 'date'],
            'who_signed' => ['nullable', 'string', 'max:255'],
            'mda_setup' => ['nullable', 'boolean'],
            'mda_reference_number' => ['nullable', 'string', 'max:255'],
            'date_set_up' => ['nullable', 'date'],
            'remittance_received' => ['nullable', 'boolean'],
            'date_logged' => ['nullable', 'date'],
            'starter_checklist_recieved_date' => ['nullable', 'date'],
            'starter_form' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'contract_recieved_date' => ['nullable', 'date'],
        ];
    }
}
