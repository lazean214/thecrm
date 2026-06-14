<?php

namespace Modules\Signable\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEnvelopePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'party_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'party_title' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (! $this->has('party_email') && ! $this->has('party_title')) {
                $v->errors()->add('party_email', 'At least one of party_email or party_title must be provided.');
            }
        });
    }
}
