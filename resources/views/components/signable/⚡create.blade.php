<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Http;
use App\Models\Deal;

new class extends Component
{
    use WithFileUploads;

    // Required: the active Deal this envelope will be linked to
    public $deal;

    public $isCreateOpen = false;
    public $step = 1;

    // Envelope Details
    public string $envelope_title = '';
    public bool $envelope_all_at_once_enabled = true;
    public ?int $envelope_auto_expire_hours = 144;
    public ?int $envelope_auto_remind_hours = 72;

    // Document source: 'upload' or 'template'
    public string $document_source = 'template';

    // File upload (used when document_source = 'upload')
    public $document_file;
    public string $document_title = '';

    // Templates fetched from Signable API
    public array $available_templates = [];
    public bool $templates_loading = false;
    public string $templates_error = '';

    // Selected templates (list of fingerprints the user has chosen)
    // Each entry: ['fingerprint' => '...', 'title' => '...', 'document_title' => '']
    public array $selected_templates = [];

    // Parties (List of Signers/Copy recipients)
    public array $envelope_parties = [
        ['party_name' => '', 'party_email' => '', 'party_role' => 'signer1', 'party_message' => '']
    ];

    // Response State
    public string $status_message = '';
    public string $status_type = ''; // success or error

    public function mount(Deal $deal): void
    {
        $this->deal = $deal;
        $this->loadTemplates();
    }

    // ----------------------------------------------------------------
    // Templates
    // ----------------------------------------------------------------

    public function loadTemplates(): void
    {
        $this->templates_loading = true;
        $this->templates_error = '';
        $this->available_templates = [];

        try {
            $allTemplates = [];
            $offset = 0;
            $limit  = 50;

            do {
                $response = Http::withBasicAuth(config('services.signable.api_key'), '')
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get('https://api.signable.co.uk/v1/templates', [
                        'offset' => $offset,
                        'limit'  => $limit,
                    ]);

                if (! $response->successful()) {
                    $this->templates_error = $response->json()['message']
                        ?? 'Failed to load templates from Signable.';
                    break;
                }

                $data      = $response->json();
                $page      = $data['templates'] ?? [];
                $allTemplates = array_merge($allTemplates, $page);
                $total     = $data['total_results'] ?? count($allTemplates);
                $offset   += $limit;

            } while (count($page) === $limit && count($allTemplates) < $total);

            $this->available_templates = $allTemplates;

        } catch (\Exception $e) {
            $this->templates_error = 'Error fetching templates: ' . $e->getMessage();
        }

        $this->templates_loading = false;
    }

    public function addTemplate(string $fingerprint): void
    {
        foreach ($this->selected_templates as $t) {
            if ($t['fingerprint'] === $fingerprint) {
                return;
            }
        }

        $match = collect($this->available_templates)
            ->firstWhere('template_fingerprint', $fingerprint);

        $this->selected_templates[] = [
            'fingerprint'    => $fingerprint,
            'title'          => $match['template_title'] ?? $fingerprint,
            'document_title' => $match['template_title'] ?? '',
        ];
    }

    public function removeTemplate(int $index): void
    {
        unset($this->selected_templates[$index]);
        $this->selected_templates = array_values($this->selected_templates);
    }

    // ----------------------------------------------------------------
    // Parties
    // ----------------------------------------------------------------

    public function addParty(): void
    {
        $nextIndex = count($this->envelope_parties) + 1;
        $this->envelope_parties[] = [
            'party_name'    => '',
            'party_email'   => '',
            'party_role'    => "signer{$nextIndex}",
            'party_message' => ''
        ];
    }

    public function removeParty(int $index): void
    {
        if (count($this->envelope_parties) > 1) {
            unset($this->envelope_parties[$index]);
            $this->envelope_parties = array_values($this->envelope_parties);
        }
    }

    // ----------------------------------------------------------------
    // Validation
    // ----------------------------------------------------------------

    protected function rules(): array
    {
        $rules = [
            'envelope_title'               => 'required|string|max:255',
            'envelope_all_at_once_enabled' => 'required|boolean',
            'envelope_auto_expire_hours'   => 'nullable|integer|min:12',
            'envelope_auto_remind_hours'   => 'nullable|integer|min:12',
            'envelope_parties'             => 'required|array|min:1',
            'envelope_parties.*.party_name'    => 'required|string|max:255',
            'envelope_parties.*.party_email'   => 'required|email|max:255',
            'envelope_parties.*.party_role'    => 'required|string|max:255',
            'envelope_parties.*.party_message' => 'nullable|string',
        ];

        if ($this->document_source === 'upload') {
            $rules['document_file']  = 'required|file|mimes:pdf,doc,docx|max:10240';
            $rules['document_title'] = 'required|string|max:255';
        } else {
            $rules['selected_templates']   = 'required|array|min:1';
            $rules['selected_templates.*.document_title'] = 'required|string|max:255';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'selected_templates.required' => 'Please select at least one template.',
            'selected_templates.min'      => 'Please select at least one template.',
            'selected_templates.*.document_title.required' => 'Each template needs a document title.',
        ];
    }

    // ----------------------------------------------------------------
    // Send
    // ----------------------------------------------------------------

    public function sendEnvelope(): void
{
    $this->validate();

    try {

        /**
         * ----------------------------------------------------
         * BUILD PARTIES
         * ----------------------------------------------------
         */
        $payloadParties = [];

        foreach ($this->envelope_parties as $party) {

            $baseParty = [
                'party_name'  => $party['party_name'],
                'party_email' => $party['party_email'],
            ];

            /**
             * TEMPLATE MODE
             *
             * Templates already contain signer
             * placements, so we only pass role.
             */
            if ($this->document_source === 'template') {

                $baseParty['party_role'] =
                    $party['party_role'] ?? 'signer1';

                if (!empty($party['party_message'])) {
                    $baseParty['party_message'] =
                        $party['party_message'];
                }
            }

            /**
             * UPLOAD MODE
             *
             * No party_role needed.
             */
            else {

                if (!empty($party['party_message'])) {
                    $baseParty['party_message'] =
                        $party['party_message'];
                }
            }

            $payloadParties[] = $baseParty;
        }

        /**
         * ----------------------------------------------------
         * BASE PAYLOAD
         * ----------------------------------------------------
         */
        $payload = [
            'envelope_title'               => $this->envelope_title,
            'envelope_all_at_once_enabled' => $this->envelope_all_at_once_enabled,
            'envelope_auto_expire_hours'   => $this->envelope_auto_expire_hours,
            'envelope_auto_remind_hours'   => $this->envelope_auto_remind_hours,
            'envelope_parties'             => $payloadParties,
        ];

        /**
         * ----------------------------------------------------
         * DOCUMENT UPLOAD MODE
         * ----------------------------------------------------
         */
        if ($this->document_source === 'upload') {

            if (!$this->document_file) {

                $this->status_type = 'error';
                $this->status_message =
                    'Please upload a document.';

                return;
            }

            $payload['envelope_documents'] = [
                [
                    'document_title' => $this->document_title,

                    'document_file_name' =>
                        $this->document_file
                            ->getClientOriginalName(),

                    'document_file_content' =>
                        base64_encode(
                            file_get_contents(
                                $this->document_file
                                    ->getRealPath()
                            )
                        ),
                ]
            ];
        }

        /**
         * ----------------------------------------------------
         * TEMPLATE MODE
         * ----------------------------------------------------
         */
        else {

            if (empty($this->selected_templates)) {

                $this->status_type = 'error';
                $this->status_message =
                    'Please select at least one template.';

                return;
            }

            $documents = [];

            foreach ($this->selected_templates as $template) {

                if (
                    empty($template['fingerprint']) ||
                    empty($template['document_title'])
                ) {
                    continue;
                }

                $documents[] = [
                    'document_title' =>
                        $template['document_title'],

                    /**
                     * REQUIRED FIX
                     */
                    'document_template_fingerprint' =>
                        $template['fingerprint'],
                ];
            }

            if (empty($documents)) {

                $this->status_type = 'error';
                $this->status_message =
                    'No valid templates selected.';

                return;
            }

            $payload['envelope_documents'] =
                $documents;
        }

        /**
         * ----------------------------------------------------
         * DEBUG LOG (non-PII fields only)
         * ----------------------------------------------------
         */
        logger()->info('SIGNABLE PAYLOAD', [
            'envelope_title' => $payload['envelope_title'] ?? null,
            'document_count' => count($payload['envelope_documents'] ?? []),
            'party_count' => count($payload['envelope_parties'] ?? []),
        ]);

        /**
         * ----------------------------------------------------
         * SEND ENVELOPE
         * ----------------------------------------------------
         */
        $response = Http::withBasicAuth(
                config('services.signable.api_key'),
                ''
            )
            ->withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post(
                'https://api.signable.co.uk/v1/envelopes',
                $payload
            );

        /**
         * ----------------------------------------------------
         * SUCCESS
         * ----------------------------------------------------
         */
        if ($response->successful()) {

            $data = $response->json();

            $fingerprint =
                $data['envelope_fingerprint']
                ?? ('unk_' . time());

            \App\Models\SignableEnvelope::create([
                'deal_id'              => $this->deal->id,
                'envelope_fingerprint' => $fingerprint,
                'title'                => $this->envelope_title,
                'status'               => 'sent',
                'queued_at'            => now(),
            ]);

            $this->status_type =
                'success';

            $this->status_message =
                "Envelope '{$this->envelope_title}' successfully sent!";

            $this->resetForm();

            $this->isCreateOpen =
                false;

            $this->step = 1;
        }

        /**
         * ----------------------------------------------------
         * API ERROR
         * ----------------------------------------------------
         */
        else {

            $responseBody =
                $response->json();

            logger()->error(
                'SIGNABLE API ERROR',
                [
                    'status' =>
                        $response->status(),

                    'response' =>
                        $responseBody,
                ]
            );

            $message =
                $responseBody['message']
                ?? $responseBody['error']
                ?? json_encode($responseBody)
                ?? 'Failed to send envelope.';

            $this->status_type =
                'error';

            $this->status_message =
                $message;
        }

    } catch (\Throwable $e) {

        logger()->error(
            'SIGNABLE EXCEPTION',
            [
                'message' =>
                    $e->getMessage(),

                'line' =>
                    $e->getLine(),

                'file' =>
                    $e->getFile(),

                'trace' =>
                    $e->getTraceAsString(),
            ]
        );

        $this->status_type =
            'error';

        $this->status_message =
            'Unexpected error: ' .
            $e->getMessage();
    }
}

    private function resetForm(): void
    {
        $this->reset([
            'envelope_title', 'envelope_all_at_once_enabled',
            'envelope_auto_expire_hours', 'envelope_auto_remind_hours',
            'document_file', 'document_title', 'document_source',
        ]);
        $this->document_source  = 'template';
        $this->selected_templates = [];
        $this->envelope_parties = [
            ['party_name' => '', 'party_email' => '', 'party_role' => 'signer1', 'party_message' => '']
        ];
    }
};
?>

<div class="max-w-4xl mx-auto my-6 p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm">

@if($isCreateOpen)

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-50">Create & Send Signable Envelope </h2>
        <p class="text-zinc-500 text-sm mt-1">Fill out the envelope details, choose a template or upload a document, then assign signers.</p>
    </div>


    @if($status_message)
        <div class="mb-6 p-4 rounded-lg flex items-start gap-3 {{ $status_type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
            <div class="flex-1 text-sm font-medium">{{ $status_message }}</div>
            <button type="button" wire:click="$set('status_message', '')" class="text-lg leading-none">&times;</button>
        </div>
    @endif

    <form wire:submit.prevent="sendEnvelope" class="space-y-6">
        @if ($step === 1)
        {{-- 1. Envelope Setup --}}
        <div class="space-y-4">
            <h3 class="text-md font-semibold text-zinc-800 dark:text-zinc-200 border-b pb-2">1. Envelope Setup</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Envelope Title *</label>
                    <input type="text" wire:model="envelope_title" placeholder="e.g., Employment Contract - John Doe"
                        class="mt-1 py-2 px-4 block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                    @error('envelope_title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mt-2 md:mt-8 flex items-center gap-2">
                        <input type="checkbox" wire:model="envelope_all_at_once_enabled" class=" py-2 px-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Send to all parties at once (Simultaneous)</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Auto Expire (Hours)</label>
                    <input type="number" wire:model="envelope_auto_expire_hours"
                        class="mt-1 block w-full py-2 px-4 rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                    @error('envelope_auto_expire_hours') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Auto Remind Frequency (Hours)</label>
                    <input type="number" wire:model="envelope_auto_remind_hours"
                        class="mt-1 block w-full py-2 px-4 rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                    @error('envelope_auto_remind_hours') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <button type="button" wire:click="$set('step', 2)"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Next: Choose Document & Template
            </button>
        </div>
        @elseif ($step === 2)
        {{-- 2. Document Source --}}
        <div class="space-y-4">
            <h3 class="text-md font-semibold text-zinc-800 dark:text-zinc-200 border-b pb-2">2. Document</h3>

            {{-- Source toggle --}}
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model="document_source" value="template" class="text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm py-2 px-4 font-medium text-zinc-700 dark:text-zinc-300">Use Signable Template</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model="document_source" value="upload" class="text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm py-2 px-4 font-medium text-zinc-700 dark:text-zinc-300">Upload a File</span>
                </label>
            </div>

            {{-- Template picker --}}
            @if($document_source === 'template')
                <div class="space-y-3">

                    {{-- Template library --}}
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-2 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">Available Templates</span>
                            <button type="button" wire:click="loadTemplates"
                                class="text-xs text-indigo-600 hover:underline flex items-center gap-1"
                                wire:loading.attr="disabled" wire:target="loadTemplates">
                                <span wire:loading wire:target="loadTemplates" class="animate-spin inline-block w-3 h-3 border border-indigo-600 border-t-transparent rounded-full"></span>
                                Refresh
                            </button>
                        </div>

                        @if($templates_loading)
                            <div class="px-4 py-6 text-center text-sm text-zinc-400">
                                <span class="animate-spin inline-block w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full mr-2"></span>
                                Loading templates…
                            </div>
                        @elseif($templates_error)
                            <div class="px-4 py-4 text-sm text-rose-600">{{ $templates_error }}</div>
                        @elseif(empty($available_templates))
                            <div class="px-4 py-4 text-sm text-zinc-400">No templates found in your Signable account.</div>
                        @else
                            <ul class="divide-y divide-zinc-100 dark:divide-zinc-700 max-h-56 overflow-y-auto">
                                @foreach($available_templates as $tpl)
                                    @php
                                        $alreadyAdded = collect($selected_templates)
                                            ->pluck('fingerprint')
                                            ->contains($tpl['template_fingerprint']);
                                    @endphp
                                    <li class="flex items-center justify-between px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                        <div>
                                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $tpl['template_title'] }}</p>
                                            <p class="text-[11px] text-zinc-400">{{ $tpl['template_fingerprint'] }}</p>
                                        </div>
                                        @if($alreadyAdded)
                                            <span class="text-xs text-emerald-600 font-medium">Added ✓</span>
                                        @else
                                            <button type="button"
                                                wire:click="addTemplate('{{ $tpl['template_fingerprint'] }}')"
                                                class="text-xs px-2.5 py-1 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-medium">
                                                + Add
                                            </button>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Selected templates --}}
                    @error('selected_templates') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror

                    @if(count($selected_templates) > 0)
                        <div class="space-y-2">
                            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">Selected for this Envelope</p>
                            @foreach($selected_templates as $i => $tpl)
                                <div class="flex items-start gap-3 p-3 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-800 rounded-lg">
                                    <div class="flex-1 space-y-1.5">
                                        <p class="text-sm font-medium text-indigo-800 dark:text-indigo-200">{{ $tpl['title'] }}</p>
                                        <div>
                                            <label class="block text-xs text-zinc-500 mb-0.5">Document title shown to recipients *</label>
                                            <input type="text"
                                                wire:model="selected_templates.{{ $i }}.document_title"
                                                placeholder="e.g., NDA Agreement"
                                                class="block py-2 px-4 w-full rounded border-zinc-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                                            @error("selected_templates.{$i}.document_title")
                                                <span class="text-xs text-rose-500 mt-0.5 block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <button type="button" wire:click="removeTemplate({{ $i }})"
                                        class="text-zinc-400 hover:text-rose-500 mt-0.5 text-lg leading-none">&times;</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- File upload --}}
            @if($document_source === 'upload')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Document Title *</label>
                        <input type="text" wire:model="document_title" placeholder="e.g., Terms of Agreement"
                            class="mt-1 py-2 px-4 block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('document_title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">File Upload (PDF, Word) *</label>
                        <input type="file" wire:model="document_file"
                            class="mt-1 py-2 px-4 block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 dark:file:bg-zinc-800 file:text-zinc-700 dark:file:text-zinc-200 hover:file:bg-zinc-200">
                        <div wire:loading wire:target="document_file" class="text-xs text-indigo-500 mt-1">Uploading…</div>
                        @error('document_file') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif

            <div class="flex gap-3">
                <button type="button" wire:click="$set('step', 1)" class="px-4 py-2 text-sm border rounded-md text-zinc-600 dark:text-zinc-300">Back</button>
                <button type="button" wire:click="$set('step', 3)"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Next: Assign Recipients & Roles 
                </button>
            </div>
        </div>
        @elseif ($step === 3)
        {{-- 3. Parties --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b pb-2">
                <h3 class="text-md font-semibold text-zinc-800 dark:text-zinc-200">3. Recipients / Parties</h3>
                <button type="button" wire:click="addParty"
                    class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    + Add Signer/Recipient
                </button>
            </div>

            @foreach($envelope_parties as $index => $party)
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 rounded-lg relative space-y-4">
                    @if(count($envelope_parties) > 1)
                        <button type="button" wire:click="removeParty({{ $index }})"
                            class="absolute top-2 right-2 text-zinc-400 hover:text-rose-500 text-lg">&times;</button>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Full Name *</label>
                            <input type="text" wire:model="envelope_parties.{{ $index }}.party_name" placeholder="John Doe"
                                class="mt-1 py-2 px-4 block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                            @error("envelope_parties.{$index}.party_name") <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email Address *</label>
                            <input type="email" wire:model="envelope_parties.{{ $index }}.party_email" placeholder="john@example.com"
                                class="mt-1 py-2 px-4 block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                            @error("envelope_parties.{$index}.party_email") <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role / Anchor *</label>
                            <input type="text" wire:model="envelope_parties.{{ $index }}.party_role" placeholder="signer1"
                                class="mt-1 py-2 px-4 block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                            <span class="text-[10px] text-zinc-400 mt-0.5 block">Use 'copy' for CC-only. Signers must match doc fields.</span>
                            @error("envelope_parties.{$index}.party_role") <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Custom Email Message (Optional)</label>
                        <textarea rows="2" wire:model="envelope_parties.{{ $index }}.party_message"
                            placeholder="Please check and sign this document…"
                            class="mt-1 py-2 px-4 block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-800 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100"></textarea>
                        @error("envelope_parties.{$index}.party_message") <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endforeach

            <div class="pt-4 flex justify-between items-center">
                <button type="button" wire:click="$set('step', 2)" class="px-4 py-2 text-sm border rounded-md text-zinc-600 dark:text-zinc-300">Back</button>
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 items-center gap-2">
                    <span wire:loading wire:target="sendEnvelope" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                    Send Envelope via Signable API
                </button>
            </div>
        </div>
        @endif
    </form>

@else
    <div class="text-center py-12">
        <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200 mb-4">Ready to send a document for signing?</h2>
        <p class="text-zinc-500 mb-6">Click the button below to create and send a Signable envelope linked to this deal.</p>
        <button type="button" wire:click="$set('isCreateOpen', true)"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Create & Send Signable Envelope
        </button>
    </div>
@endif
</div>

