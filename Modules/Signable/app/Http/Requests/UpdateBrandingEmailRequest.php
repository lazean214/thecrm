<?php

namespace Modules\Signable\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'branding_email_subject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'branding_email_body' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
