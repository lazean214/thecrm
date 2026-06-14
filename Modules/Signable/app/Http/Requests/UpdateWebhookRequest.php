<?php

namespace Modules\Signable\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'webhook_url' => ['required', 'url', 'max:2048'],
            'webhook_type' => ['required', 'string', 'max:100'],
        ];
    }
}
