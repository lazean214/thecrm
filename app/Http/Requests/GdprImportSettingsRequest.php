<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GdprImportSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-gdpr') ?? false;
    }

    public function rules(): array
    {
        return [
            'settings_file' => 'required|file|mimes:json|max:1024',
        ];
    }
}
