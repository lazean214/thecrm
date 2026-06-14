<?php

namespace Modules\Signable\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'signing_order_enabled' => ['sometimes', 'boolean'],
            'send_sign_complete_email' => ['sometimes', 'boolean'],
            'send_sign_each_email' => ['sometimes', 'boolean'],
            'send_bounced_email' => ['sometimes', 'boolean'],
            'send_rejected_email' => ['sometimes', 'boolean'],
            'send_cancelled_email' => ['sometimes', 'boolean'],
            'send_expired_email' => ['sometimes', 'boolean'],
            'auto_remind_enabled' => ['sometimes', 'boolean'],
            'auto_remind_hours' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'auto_expire_enabled' => ['sometimes', 'boolean'],
            'auto_expire_hours' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'signature_format' => ['sometimes', 'string', 'max:100'],
            'default_redirect_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ];
    }
}
