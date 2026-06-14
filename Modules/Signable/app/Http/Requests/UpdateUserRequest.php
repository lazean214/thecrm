<?php

namespace Modules\Signable\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_name' => ['sometimes', 'string', 'max:255'],
            'user_email' => ['sometimes', 'email', 'max:255'],
            'role_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->has('user_name') && ! $this->has('user_email')) {
                $v->errors()->add('user_name', 'At least one of user_name or user_email must be provided.');
            }
        });
    }
}
