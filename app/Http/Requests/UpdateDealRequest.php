<?php

namespace App\Http\Requests;

use App\Enums\DealStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'stage' => ['sometimes', 'nullable', Rule::enum(DealStage::class)],
            'recruitment_agency' => ['sometimes', 'nullable', 'string', 'max:255'],
            'consultant_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'agency_deal_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'margin_agreed' => ['sometimes', 'nullable', 'numeric'],
            'date_sent' => ['sometimes', 'nullable', 'date'],
            'date_signed' => ['sometimes', 'nullable', 'date'],
            'who_signed' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mda_setup' => ['sometimes', 'nullable', 'boolean'],
            'mda_reference_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_set_up' => ['sometimes', 'nullable', 'date'],
            'remittance_received' => ['sometimes', 'nullable', 'boolean'],
            'date_logged' => ['sometimes', 'nullable', 'date'],
            'starter_checklist_recieved_date' => ['sometimes', 'nullable', 'date'],
            'starter_form' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tax_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contract_recieved_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
