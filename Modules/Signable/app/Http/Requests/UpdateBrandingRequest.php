<?php

namespace Modules\Signable\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'branding_logo' => ['sometimes', 'nullable', 'string'],
            'branding_colour' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->has('branding_logo') && ! $this->has('branding_colour')) {
                $v->errors()->add('branding_logo', 'At least one of branding_logo or branding_colour must be provided.');
            }
        });
    }
}
