<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'filterDealName' => 'nullable|string|max:255',
            'filterOwner' => 'nullable|string|max:255',
            'filterContact' => 'nullable|string|max:255',
            'filterCompanyName' => 'nullable|string|max:255',
            'filterStage' => 'nullable|string|max:255',
            'minAmount' => 'nullable|numeric|min:0',
            'maxAmount' => 'nullable|numeric|min:0',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ];
    }
}
