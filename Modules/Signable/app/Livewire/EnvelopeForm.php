<?php

namespace Modules\Signable\App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Signable\App\Services\Signable\SignableClient;

class EnvelopeForm extends Component
{
    use WithFileUploads;

    public $deal;

    public $envelope_title = '';

    public $envelope_all_at_once_enabled = true;

    public $envelope_auto_expire_hours = 144;

    public $envelope_auto_remind_hours = 72;

    public $document_source = 'template';

    public $document_file;

    public $document_title = '';

    public $available_templates = [];

    public $selected_templates = [];

    public $envelope_parties = [
        ['party_name' => '', 'party_email' => '', 'party_role' => 'signer1', 'party_message' => ''],
    ];

    public $status_message = '';

    public $status_type = '';

    public function mount(SignableClient $signable)
    {
        $this->available_templates = $signable->listTemplates()->json('templates') ?? [];
    }

    public function sendEnvelope(SignableClient $signable)
    {
        $payload = [
            'envelope_title' => $this->envelope_title,
            'envelope_all_at_once_enabled' => $this->envelope_all_at_once_enabled,
            'envelope_auto_expire_hours' => $this->envelope_auto_expire_hours,
            'envelope_auto_remind_hours' => $this->envelope_auto_remind_hours,
            'envelope_parties' => $this->envelope_parties,
        ];
        if ($this->document_source === 'upload') {
            $payload['envelope_documents'] = [[
                'document_title' => $this->document_title,
                'file_name' => $this->document_file->getClientOriginalName(),
                'file_content' => base64_encode(file_get_contents($this->document_file->getRealPath())),
            ]];
        } elseif (count($this->selected_templates) > 0) {
            $payload['envelope_documents'] = array_map(function ($tpl) {
                return [
                    'document_title' => $tpl['document_title'],
                    'template_fingerprint' => $tpl['fingerprint'],
                ];
            }, $this->selected_templates);
        }
        $response = $signable->sendEnvelope($payload);
        if ($response->successful()) {
            $this->status_type = 'success';
            $this->status_message = 'Envelope sent!';
        } else {
            $this->status_type = 'error';
            $this->status_message = $response->json('message') ?? 'Failed to send envelope.';
        }
    }

    public function render()
    {
        return view('signable::livewire.envelope-form');
    }
}
