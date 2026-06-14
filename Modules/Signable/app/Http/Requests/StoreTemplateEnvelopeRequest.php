<?php

namespace Modules\Signable\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateEnvelopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'string', 'max:255'],
            'template_title' => ['nullable', 'string', 'max:255'],
            'envelope_title' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'integer', 'min:1'],
            'envelope_redirect_url' => ['nullable', 'url', 'max:2048'],
            'envelope_all_at_once_enabled' => ['sometimes', 'boolean'],
            'envelope_requires_otp' => ['sometimes', 'boolean'],
            'envelope_password_protect' => ['sometimes', 'boolean'],
            'envelope_auto_expire_hours' => ['nullable', 'integer', 'min:1'],
            'envelope_auto_remind_hours' => ['nullable', 'integer', 'min:1'],
            'envelope_meta' => ['nullable', 'array'],
            'envelope_parties' => ['required', 'array', 'min:1'],
            'envelope_parties.*.party_name' => ['required', 'string', 'max:255'],
            'envelope_parties.*.party_email' => ['required', 'email', 'max:255'],
            'envelope_parties.*.party_id' => ['nullable', 'string', 'max:255'],
            'envelope_parties.*.party_role' => ['required', 'string', 'max:100'],
            'envelope_parties.*.party_message' => ['nullable', 'string', 'max:1000'],
            'envelope_parties.*.party_mobile' => ['nullable', 'string', 'max:30'],
            'envelope_parties.*.party_documents' => ['nullable', 'array', 'min:1'],
            'envelope_parties.*.party_documents.*.party_id' => ['required_with:envelope_parties.*.party_documents', 'string', 'max:255'],
            'envelope_parties.*.party_documents.*.document_template_fingerprint' => ['required_with:envelope_parties.*.party_documents', 'string', 'max:255'],
            'envelope_documents' => ['nullable', 'array', 'min:1'],
            'envelope_documents.*.document_template_fingerprint' => ['nullable', 'string', 'max:255'],
            'envelope_documents.*.document_url' => ['nullable', 'url', 'max:2048'],
            'envelope_documents.*.document_title' => ['required_with:envelope_documents', 'string', 'max:255'],
            'envelope_documents.*.document_file_name' => ['nullable', 'string', 'max:255'],
            'envelope_documents.*.document_file_content' => ['nullable', 'string'],
        ];
    }
}
