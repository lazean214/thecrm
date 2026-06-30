<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Deal;
use App\Models\EmailTemplate;
use App\Services\DealEmailService;
use App\Services\EmailTemplateParser;

new class extends Component
{
    use WithFileUploads;

    public Deal $deal;

    // Store IDs only — avoids Livewire hydration failures on nullable models
    public ?int $contactId = null;
    public ?int $companyId = null;

    public string $contactName = '';
    public string $companyName = '';

    public $templates = [];

    public ?int $templateId = null;

    public string $toEmail = '';

    public string $subject = '';
    public string $body = '';
    public bool $renderBodyAsHtml = false;

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    */

    // uploaded manually
    public $attachments = [];

    // template attachments
    public array $selectedTemplateAttachments = [];

    public $templateAttachments = [];

    public function mount(int $dealId): void
    {
        $this->deal = Deal::with([
            'contacts',
            'companies',
            'emailLogs.user',
            'emailLogs.template',
        ])->findOrFail($dealId);

        $contact = $this->deal->primaryContact();
        $company = $this->deal->primaryCompany();

        $this->contactId = $contact?->id;
        $this->companyId = $company?->id;
        $this->contactName = trim(($contact?->first_name ?? '').' '.($contact?->last_name ?? ''));
        $this->companyName = $company?->name ?? '';
        $this->toEmail = $contact?->email ?? '';

        $this->templates = EmailTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function updatedTemplateId(): void
    {
        if (! $this->templateId) {
            $this->subject = '';
            $this->body = '';
            $this->renderBodyAsHtml = false;
            $this->templateAttachments = [];
            $this->selectedTemplateAttachments = [];

            return;
        }

        $template = EmailTemplate::with('attachments')->find($this->templateId);

        if (! $template) {
            return;
        }

        $contact = $this->contactId
            ? \App\Models\Contact::find($this->contactId)
            : null;

        $company = $this->companyId
            ? \App\Models\Company::find($this->companyId)
            : null;

        $this->renderBodyAsHtml =
            $template->editor_mode === 'builder'
            || (bool) ($template->is_html ?? true);

        $this->subject = EmailTemplateParser::parse(
            $template->subject,
            $this->deal,
            $contact,
            $company,
            auth()->user(),
        );

        $this->body = EmailTemplateParser::parse(
            $template->editor_mode === 'builder'
                ? ($template->sections ?? [])
                : $template->body,
            $this->deal,
            $contact,
            $company,
            auth()->user(),
        );

        /*
        |--------------------------------------------------------------------------
        | Auto-load template attachments
        |--------------------------------------------------------------------------
        */

        $this->templateAttachments = $template->attachments->toArray();

        $this->selectedTemplateAttachments =
            $template->attachments->pluck('id')->toArray();
    }

    public function send(): void
    {
        $this->validate([
            'templateId' => 'required',
            'toEmail' => 'required|email',
            'subject' => 'required|max:255',
            'body' => 'required|min:10',
            'attachments.*' => 'file|max:10240',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save temporary uploads
        |--------------------------------------------------------------------------
        */

        $manualAttachments = [];

        foreach ($this->attachments as $file) {
            $manualAttachments[] = $file->store('private/email-temp-attachments');
        }

        DealEmailService::send(
            deal: $this->deal,
            templateId: $this->templateId,
            to: $this->toEmail,
            customSubject: $this->subject,
            customBody: $this->body,
            selectedTemplateAttachments: $this->selectedTemplateAttachments,
            manualAttachments: $manualAttachments,
        );

        $this->deal->refresh();

        $this->attachments = [];

        session()->flash('success', 'Email queued and will be sent shortly.');
    }
};
?>

<div class="space-y-6">

    @if (session('success'))
        <div
            class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-6">

        {{-- Email Composer --}}
        <div class="col-span-8">

            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">

                <div class="mb-5 flex items-center justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                            Compose Email
                        </h2>

                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $deal->name }}
                        </p>
                    </div>

                    <button
                        wire:click="send"
                        wire:loading.attr="disabled"
                        wire:target="send"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-70">

                        {{-- Normal State --}}
                        <span wire:loading.remove wire:target="send">
                            Send Email
                        </span>

                        {{-- Sending State --}}
                        <span wire:loading.flex wire:target="send" class="items-center gap-2">

                            <svg
                                class="h-4 w-4 animate-spin"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24">

                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4">
                                </circle>

                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>

                            </svg>

                            Sending...

                        </span>

                    </button>

                </div>

                <div class="space-y-4">

                    {{-- Recipient --}}
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            To
                        </label>

                        <input
                            wire:model="toEmail"
                            type="email"
                            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>

                    {{-- Template --}}
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Template
                        </label>

                        <select
                            wire:model.live="templateId"
                            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">

                            <option value="">Select a template…</option>

                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}">
                                    {{ $template->name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    {{-- Subject --}}
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Subject
                        </label>

                        <input
                            wire:model.live="subject"
                            type="text"
                            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>

                    {{-- Body --}}
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Email Body
                            </label>

                            @if ($renderBodyAsHtml)
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                    HTML
                                </span>
                            @endif
                        </div>

                        @if ($renderBodyAsHtml)
                            {{-- Show rendered HTML with a raw-edit toggle via Alpine --}}
                            <div x-data="{ editing: false }">

                                <div class="mb-2 flex justify-end">
                                    <button
                                        type="button"
                                        x-on:click="editing = !editing"
                                        class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                        <span x-show="!editing">Edit raw HTML</span>
                                        <span x-show="editing" x-cloak>Show rendered</span>
                                    </button>
                                </div>

                                <div x-show="!editing">
                                    <div
                                        class="min-h-[200px] w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 [&_p]:mb-3 [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:font-semibold [&_ul]:list-disc [&_ul]:pl-5 [&_a]:text-indigo-600 [&_a]:underline">
                                        {!! $body !!}
                                    </div>
                                </div>

                                <div x-show="editing" x-cloak>
                                    <textarea
                                        wire:model.live="body"
                                        rows="14"
                                        class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-4 py-3 font-mono text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                </div>

                            </div>
                        @else
                            <textarea
                                wire:model.live="body"
                                rows="14"
                                placeholder="Email body…"
                                class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                        @endif
                    </div>

                    {{-- Template Attachments --}}
                    @if (count($templateAttachments))

                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Template Attachments
                            </label>

                            <div class="mt-2 space-y-2">

                                @foreach ($templateAttachments as $file)

                                    <label
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900">

                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedTemplateAttachments"
                                            value="{{ $file['id'] }}"
                                            class="rounded border-slate-300">

                                        <div>
                                            <div class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                                {{ $file['file_name'] }}
                                            </div>

                                            <div class="text-xs text-slate-500">
                                                {{ number_format($file['file_size'] / 1024, 1) }} KB
                                            </div>
                                        </div>

                                    </label>

                                @endforeach

                            </div>
                        </div>

                    @endif

                    {{-- Extra Attachments --}}
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Extra Attachments
                        </label>

                        <input
                            type="file"
                            wire:model="attachments"
                            multiple
                            class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">

                        <div
                            wire:loading
                            wire:target="attachments"
                            class="mt-2 text-xs text-slate-500">
                            Uploading…
                        </div>

                        @if ($attachments)
                            <div class="mt-3 space-y-2">
                                @foreach ($attachments as $file)
                                    <div
                                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-300">
                                        {{ $file->getClientOriginalName() }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

            </div>

        </div>

        {{-- Sidebar --}}
        <div class="col-span-4 space-y-6">

            {{-- Deal Info --}}
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">

                <h3 class="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                    Deal Details
                </h3>

                <div class="space-y-3 text-sm">

                    <div>
                        <span class="font-medium text-slate-500 dark:text-slate-400">Contact:</span>
                        {{ $contactName ?: '—' }}
                    </div>

                    <div>
                        <span class="font-medium text-slate-500 dark:text-slate-400">Company:</span>
                        {{ $companyName ?: '—' }}
                    </div>

                    <div>
                        <span class="font-medium text-slate-500 dark:text-slate-400">Stage:</span>
                        {{ $deal->stage }}
                    </div>

                </div>

            </div>

            {{-- Email History --}}
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">

                <div class="mb-4 flex items-center justify-between">

                    <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">
                        Email History
                    </h3>

                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-100">
                        {{ $deal->emailLogs->count() }}
                    </span>

                </div>

                <div class="space-y-3">

                    @forelse($deal->emailLogs->sortByDesc('created_at') as $email)

                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">

                            <div class="mb-2 flex items-start justify-between gap-2">

                                <div class="text-sm font-medium text-slate-800 dark:text-slate-100">
                                    {{ $email->subject }}
                                </div>

                                <span
                                    class="shrink-0 rounded-full px-2 py-1 text-xs font-medium
                                    @if ($email->status === 'sent') bg-emerald-100 text-emerald-700
                                    @elseif ($email->status === 'failed') bg-red-100 text-red-700
                                    @elseif ($email->status === 'pending') bg-blue-100 text-blue-700
                                    @else bg-amber-100 text-amber-700 @endif">
                                    @if ($email->status === 'pending')
                                        Queued
                                    @else
                                        {{ ucfirst($email->status) }}
                                    @endif
                                </span>

                            </div>

                            <div class="text-xs text-slate-500">
                                To: {{ $email->to_email }}
                            </div>

                            <div class="mt-1 text-xs text-slate-400">
                                {{ $email->created_at?->diffForHumans() }}
                            </div>

                        </div>

                    @empty

                        <div
                            class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-slate-600">
                            No email history yet.
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

</div>
