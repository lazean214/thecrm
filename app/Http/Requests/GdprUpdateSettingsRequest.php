<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GdprUpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-gdpr') ?? false;
    }

    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.*.entity_type' => 'required|string|max:255',
            'settings.*.retention_months' => 'required|integer|min:1|max:120',
            'settings.*.is_enabled' => 'boolean',
            'settings.*.custom_action' => 'nullable|string|max:255',
        ];
    }
}
