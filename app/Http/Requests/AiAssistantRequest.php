<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:1000'],
        ];
    }
}
