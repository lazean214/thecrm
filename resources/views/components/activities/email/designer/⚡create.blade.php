<?php

use App\Helpers\InternalCompanies;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateAttachment;
use App\Services\EmailTemplateParser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?EmailTemplate $template = null;

    public ?int $templateId = null;

    public string $name = '';
    public string $description = '';
    public string $subject = '';
    public string $body = '';

    public bool $is_html = true;
    public bool $is_active = true;

    public string $editorMode = 'legacy';

    public array $sections = [];

    public ?string $activeSectionId = null;

    public $sectionImageUpload = null;

    public $attachments = [];

    public $savedAttachments = [];

    public ?string $internalCompany = null;

    public array $tokens = [
        'Deal' => [
            '[deal.name]',
            '[deal.amount]',
            '[deal.stage]',
            '[deal.consultant_name]',
        ],
        'Contact' => [
            '[contact.first_name]',
            '[contact.last_name]',
            '[contact.full_name]',
            '[contact.email]',
        ],
        'Company' => [
            '[company.name]',
            '[company.email]',
        ],
        'User' => [
            '[user.name]',
            '[user.email]',
        ],
    ];

    public static function getEmailTemplateView(?string $companySlug): string
    {
        if (! $companySlug) {
            return 'email.simple-template';
        }

        $company = InternalCompanies::getBySlug($companySlug);

        if (! $company) {
            return 'email.simple-template';
        }

        return 'email.' . $companySlug;
    }

    public function mount(?int $templateId = null): void
    {
        if (! $templateId) {
            return;
        }

        $this->template = EmailTemplate::with(['attachments', 'media'])->findOrFail($templateId);
        $this->templateId = $this->template->id;

        $this->fill([
            'name' => $this->template->name,
            'description' => $this->template->description ?? '',
            'subject' => $this->template->subject,
            'body' => $this->template->body,
            'is_html' => (bool) ($this->template->is_html ?? true),
            'is_active' => (bool) $this->template->is_active,
            'editorMode' => $this->template->editor_mode ?? 'legacy',
            'sections' => $this->template->sections ?? [],
            'internalCompany' => $this->template->internal_company ?? null,
        ]);

        if (! empty($this->sections)) {
            $this->activeSectionId = $this->sections[0]['id'] ?? null;
        }

        $this->savedAttachments = $this->template->attachments;
    }

    public function setEditorMode(string $mode): void
    {
        if (! in_array($mode, ['legacy', 'builder'], true)) {
            return;
        }

        $this->editorMode = $mode;

        if ($mode === 'builder' && empty($this->sections)) {
            $this->addSection('text');
        }
    }

    public function addSection(string $type): void
    {
        if (! in_array($type, ['text', 'image', 'button'], true)) {
            return;
        }

        $section = [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'content' => '',
            'image_url' => '',
            'media_id' => null,
            'alt' => '',
            'label' => 'Open link',
            'url' => '',
        ];

        $this->sections[] = $section;
        $this->activeSectionId = $section['id'];
    }

    public function removeSection(string $sectionId): void
    {
        $sectionIndex = $this->getSectionIndexById($sectionId);

        if ($sectionIndex === null) {
            return;
        }

        $mediaId = $this->sections[$sectionIndex]['media_id'] ?? null;

        if ($mediaId && $this->template) {
            $this->template->media()->where('id', $mediaId)->delete();
        }

        array_splice($this->sections, $sectionIndex, 1);

        if ($this->activeSectionId === $sectionId) {
            $this->activeSectionId = $this->sections[0]['id'] ?? null;
        }
    }

    public function setActiveSection(string $sectionId): void
    {
        $this->activeSectionId = $sectionId;
    }

    public function handleSectionSort(string $id, int $position): void
    {
        $index = $this->getSectionIndexById($id);

        if ($index === null) {
            return;
        }

        $section = $this->sections[$index];

        array_splice($this->sections, $index, 1);
        array_splice($this->sections, $position, 0, [$section]);
    }

    public function insertToken(string $token): void
    {
        if ($this->editorMode === 'legacy') {
            $this->body .= ' '.$token;

            return;
        }

        if (! $this->activeSectionId) {
            return;
        }

        $index = $this->getSectionIndexById($this->activeSectionId);

        if ($index === null) {
            return;
        }

        if (($this->sections[$index]['type'] ?? '') === 'button') {
            $this->sections[$index]['label'] = ($this->sections[$index]['label'] ?? '').' '.$token;

            return;
        }

        if (($this->sections[$index]['type'] ?? '') === 'text') {
            $this->sections[$index]['content'] = ($this->sections[$index]['content'] ?? '').' '.$token;
        }
    }

    public function updatedSectionImageUpload(): void
    {
        if (! $this->sectionImageUpload) {
            return;
        }

        $this->validate([
            'sectionImageUpload' => 'image|max:3072',
        ]);

        $sectionIndex = $this->getActiveSectionIndex();

        if ($sectionIndex === null || ($this->sections[$sectionIndex]['type'] ?? '') !== 'image') {
            return;
        }

        $template = $this->persistTemplateForMedia();

        $oldMediaId = $this->sections[$sectionIndex]['media_id'] ?? null;

        if ($oldMediaId) {
            $template->media()->where('id', $oldMediaId)->delete();
        }

        $media = $template
            ->addMedia($this->sectionImageUpload->getRealPath())
            ->usingFileName(now()->timestamp.'-'.$this->sectionImageUpload->getClientOriginalName())
            ->toMediaCollection('builder_images');

        $this->sections[$sectionIndex]['media_id'] = $media->id;
        $this->sections[$sectionIndex]['image_url'] = $media->getFullUrl();

        $this->sectionImageUpload = null;
    }

    public function removeAttachment(int $id): void
    {
        $attachment = EmailTemplateAttachment::findOrFail($id);

        Storage::disk('local')->delete($attachment->file_path);

        $attachment->delete();

        if ($this->template) {
            $this->savedAttachments = $this->template->fresh()->attachments;
        }
    }

    public function getActiveSectionIndex(): ?int
    {
        return $this->getSectionIndexById($this->activeSectionId);
    }

    public function getPreviewBodyProperty(): string
    {
        if ($this->editorMode === 'builder') {
            return EmailTemplateParser::parse($this->sections);
        }

        return $this->body;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|min:3|max:255',
            'subject' => 'required|max:255',
            'attachments.*' => 'file|max:10240',
        ];

        if ($this->editorMode === 'legacy') {
            $rules['body'] = 'required|min:10';
        } else {
            $rules['sections'] = 'required|array|min:1';
        }

        $this->validate($rules);

        if ($this->editorMode === 'builder') {
            $this->validateBuilderSections();
        }

        $payload = [
            'name' => $this->name,
            'description' => $this->description,
            'subject' => $this->subject,
            'body' => $this->editorMode === 'builder' ? EmailTemplateParser::parse($this->sections) : $this->body,
            'is_html' => $this->is_html,
            'is_active' => $this->is_active,
            'created_by' => Auth::id(),
            'editor_mode' => $this->editorMode,
            'sections' => $this->editorMode === 'builder' ? $this->sections : null,
            'internal_company' => $this->internalCompany,
        ];

        $template = EmailTemplate::updateOrCreate(
            [
                'id' => $this->templateId,
            ],
            $payload
        );

        foreach ($this->attachments as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store(
                'email-template-attachments',
                'local'
            );

            $template->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => Storage::disk('local')->size($path),
            ]);
        }

        $this->template = $template;
        $this->templateId = $template->id;
        $this->savedAttachments = $template->fresh()->attachments;
        $this->attachments = [];

        session()->flash('success', 'Template saved successfully.');
    }

    protected function validateBuilderSections(): void
    {
        $errors = [];

        foreach ($this->sections as $index => $section) {
            $type = $section['type'] ?? null;

            if (! in_array($type, ['text', 'image', 'button'], true)) {
                $errors["sections.$index.type"] = 'Invalid section type.';
                continue;
            }

            if ($type === 'text' && blank($section['content'] ?? '')) {
                $errors["sections.$index.content"] = 'Text content is required.';
            }

            if ($type === 'image') {
                $url = $section['image_url'] ?? '';

                if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors["sections.$index.image_url"] = 'Upload an image for this section.';
                }
            }

            if ($type === 'button') {
                if (blank($section['label'] ?? '')) {
                    $errors["sections.$index.label"] = 'Button label is required.';
                }

                $url = $section['url'] ?? '';

                if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors["sections.$index.url"] = 'A valid button URL is required.';
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function persistTemplateForMedia(): EmailTemplate
    {
        if ($this->templateId) {
            return EmailTemplate::findOrFail($this->templateId);
        }

        $template = EmailTemplate::create([
            'name' => $this->name ?: 'Untitled template',
            'subject' => $this->subject ?: 'Untitled subject',
            'body' => $this->body ?: ' ',
            'description' => $this->description,
            'is_html' => $this->is_html,
            'is_active' => $this->is_active,
            'editor_mode' => $this->editorMode,
            'sections' => $this->sections,
            'created_by' => Auth::id(),
        ]);

        $this->template = $template;
        $this->templateId = $template->id;

        return $template;
    }

    protected function getSectionIndexById(?string $sectionId): ?int
    {
        if (! $sectionId) {
            return null;
        }

        foreach ($this->sections as $index => $section) {
            if (($section['id'] ?? null) === $sectionId) {
                return $index;
            }
        }

        return null;
    }
};
?>

<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-8 space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">
                        Email Template Designer
                    </h2>

                    <button wire:click="save" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        {{ $templateId ? 'Update Template' : 'Save Template' }}
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Template Name
                        </label>
                        <input
                            type="text"
                            wire:model="name"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-emerald-500 focus:outline-none" />
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Internal Company
                        </label>
                        <select
                            wire:model="internalCompany"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-emerald-500 focus:outline-none">
                            <option value="">-- None --</option>
                            @foreach (\App\Helpers\InternalCompanies::all() as $company)
                                <option value="{{ $company['slug'] }}">{{ ucfirst($company['name']) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end justify-between gap-4">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300">
                            <span class="text-sm text-slate-700">Active</span>
                        </label>

                        <div class="inline-flex rounded-lg border border-slate-300 p-1">
                            <button type="button" wire:click="setEditorMode('legacy')" class="rounded-md px-3 py-1 text-xs {{ $editorMode === 'legacy' ? 'bg-emerald-600 text-white' : 'text-slate-600' }}">
                                Legacy
                            </button>
                            <button type="button" wire:click="setEditorMode('builder')" class="rounded-md px-3 py-1 text-xs {{ $editorMode === 'builder' ? 'bg-emerald-600 text-white' : 'text-slate-600' }}">
                                Builder
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea
                        wire:model="description"
                        rows="2"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-emerald-500 focus:outline-none"></textarea>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Subject</label>
                    <input
                        type="text"
                        wire:model.live="subject"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-emerald-500 focus:outline-none" />
                    @error('subject')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($editorMode === 'legacy')
                    <div class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email Body</label>
                        <div class="mb-2 flex items-center gap-4">
                            <label class="flex items-center gap-2 text-xs">
                                <input type="radio" wire:model="is_html" value="1"> HTML
                            </label>
                            <label class="flex items-center gap-2 text-xs">
                                <input type="radio" wire:model="is_html" value="0"> Plain Text
                            </label>
                        </div>

                        <textarea
                            wire:model.live="body"
                            rows="18"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-4 font-mono text-sm focus:border-emerald-500 focus:outline-none"
                            placeholder="Write your email {{ $is_html ? 'HTML' : 'plain text' }} here..."></textarea>
                        @error('body')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <div class="space-y-3">
                            <div class="rounded-xl border border-slate-200 p-3">
                                <p class="mb-2 text-sm font-semibold text-slate-700">Add Section</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="addSection('text')" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:border-emerald-500 hover:bg-emerald-50">Text</button>
                                    <button type="button" wire:click="addSection('image')" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:border-emerald-500 hover:bg-emerald-50">Image</button>
                                    <button type="button" wire:click="addSection('button')" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:border-emerald-500 hover:bg-emerald-50">Button</button>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-3">
                                <p class="mb-2 text-sm font-semibold text-slate-700">Sections</p>
                                <div wire:sort="handleSectionSort" class="space-y-2">
                                    @forelse ($sections as $section)
                                        <div
                                            wire:key="section-{{ $section['id'] }}"
                                            wire:sort:item="{{ $section['id'] }}"
                                            class="flex items-center justify-between rounded-lg border px-3 py-2 {{ $activeSectionId === $section['id'] ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white' }}">
                                            <button type="button" wire:click="setActiveSection('{{ $section['id'] }}')" class="text-left text-sm font-medium text-slate-700">
                                                {{ ucfirst($section['type']) }} Section
                                            </button>

                                            <div class="flex items-center gap-2">
                                                <button type="button" wire:click="removeSection('{{ $section['id'] }}')" class="text-xs text-red-600 hover:text-red-700">Remove</button>
                                                <span wire:sort:handle class="cursor-move text-slate-500">::</span>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-500">No sections yet. Add your first one.</p>
                                    @endforelse
                                </div>
                                @error('sections')
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="mb-2 text-sm font-semibold text-slate-700">Section Editor</p>

                            @php
                                $activeIndex = $this->getActiveSectionIndex();
                            @endphp

                            @if ($activeIndex === null)
                                <p class="text-xs text-slate-500">Select a section to edit.</p>
                            @elseif (($sections[$activeIndex]['type'] ?? '') === 'text')
                                <div wire:key="text-editor-{{ $sections[$activeIndex]['id'] }}" x-data="emailTextTools()">
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Content</label>

                                    <div class="mb-2 flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1.5">
                                        <button type="button" @click="wrap($refs.textArea, '**', '**')" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700">B</button>
                                        <button type="button" @click="wrap($refs.textArea, '_', '_')" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs italic text-slate-700">I</button>
                                        <button type="button" @click="prependLine($refs.textArea, '## ')" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700">H2</button>
                                        <button type="button" @click="prependLine($refs.textArea, '- ')" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700">List</button>
                                        <button type="button" @click="addLink($refs.textArea)" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700">Link</button>
                                        <button type="button" @click="stripBasicMarkdown($refs.textArea)" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700">Clear</button>
                                    </div>

                                    <textarea
                                        x-ref="textArea"
                                        @keydown="handleShortcuts($event, $refs.textArea)"
                                        wire:model.live="sections.{{ $activeIndex }}.content"
                                        rows="8"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                                        placeholder="Write your section content..."
                                    ></textarea>

                                    <p class="mt-1 text-[11px] text-slate-500">
                                        Shortcuts: Ctrl/Cmd+B bold, Ctrl/Cmd+I italic, Ctrl/Cmd+K link
                                    </p>
                                </div>

                                @error("sections.$activeIndex.content")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @elseif (($sections[$activeIndex]['type'] ?? '') === 'image')
                                <label class="mb-1 block text-xs font-medium text-slate-600">Upload Image</label>
                                <input type="file" wire:model="sectionImageUpload" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                                <div wire:loading wire:target="sectionImageUpload" class="mt-1 text-xs text-slate-500">Uploading image...</div>

                                @if (! empty($sections[$activeIndex]['image_url']))
                                    <img src="{{ $sections[$activeIndex]['image_url'] }}" alt="Section image" class="mt-3 max-h-40 rounded border border-slate-200" />
                                @endif

                                <label class="mb-1 mt-3 block text-xs font-medium text-slate-600">Alt text</label>
                                <input type="text" wire:model.live="sections.{{ $activeIndex }}.alt" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />

                                @error("sections.$activeIndex.image_url")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error('sectionImageUpload')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @elseif (($sections[$activeIndex]['type'] ?? '') === 'button')
                                <label class="mb-1 block text-xs font-medium text-slate-600">Button Label</label>
                                <input type="text" wire:model.live="sections.{{ $activeIndex }}.label" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />

                                <label class="mb-1 mt-3 block text-xs font-medium text-slate-600">Button URL</label>
                                <input type="url" wire:model.live="sections.{{ $activeIndex }}.url" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />

                                @error("sections.$activeIndex.label")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error("sections.$activeIndex.url")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>
                @endif

                <div class="mt-5">
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Template Attachments
                    </label>

                    <input
                        type="file"
                        wire:model="attachments"
                        multiple
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3">

                    <div wire:loading wire:target="attachments" class="mt-2 text-xs text-slate-500">
                        Uploading...
                    </div>

                    @if (count($savedAttachments))
                        <div class="mt-4 space-y-2">
                            @foreach ($savedAttachments as $file)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div>
                                        <div class="text-sm font-medium text-slate-800">
                                            {{ $file->file_name }}
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            {{ number_format($file->file_size / 1024, 1) }} KB
                                        </div>
                                    </div>

                                    <button type="button" wire:click="removeAttachment({{ $file->id }})" class="text-red-500 hover:text-red-700">
                                        Remove
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-base font-semibold text-slate-800">Preview</h3>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mb-3 border-b border-slate-200 pb-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Subject</div>
                        <div class="font-medium text-slate-800">{{ $subject }}</div>
                    </div>
                    <div class="prose prose-sm max-w-none whitespace-pre-wrap text-slate-700">
                        @if ($editorMode === 'builder' || $is_html)
                            {!! $this->previewBody !!}
                        @else
                            {{ $this->previewBody }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-4">
            <div class="sticky top-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-base font-semibold text-slate-800">
                    Dynamic Variables
                </h3>

                <div class="space-y-5">
                    @foreach ($tokens as $group => $items)
                        <div>
                            <h4 class="mb-2 text-sm font-semibold text-slate-600">
                                {{ $group }}
                            </h4>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($items as $token)
                                    <button
                                        wire:click="insertToken('{{ $token }}')"
                                        type="button"
                                        class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 hover:border-emerald-500 hover:bg-emerald-50">
                                        {{ $token }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        function emailTextTools() {
            return {
                wrap(textarea, before, after) {
                    const start = textarea.selectionStart ?? 0;
                    const end = textarea.selectionEnd ?? start;
                    const value = textarea.value ?? '';
                    const selected = value.slice(start, end);
                    const next = value.slice(0, start) + before + selected + after + value.slice(end);

                    textarea.value = next;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    textarea.focus();

                    const cursorStart = start + before.length;
                    const cursorEnd = cursorStart + selected.length;
                    textarea.setSelectionRange(cursorStart, cursorEnd);
                },
                prependLine(textarea, prefix) {
                    const start = textarea.selectionStart ?? 0;
                    const value = textarea.value ?? '';
                    const lineStart = value.lastIndexOf('\n', start - 1) + 1;
                    const next = value.slice(0, lineStart) + prefix + value.slice(lineStart);

                    textarea.value = next;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    textarea.focus();
                    textarea.setSelectionRange(start + prefix.length, start + prefix.length);
                },
                addLink(textarea) {
                    const url = window.prompt('Enter URL (https://...)');

                    if (!url) {
                        return;
                    }

                    const start = textarea.selectionStart ?? 0;
                    const end = textarea.selectionEnd ?? start;
                    const value = textarea.value ?? '';
                    const selected = value.slice(start, end) || 'link text';
                    const link = `[${selected}](${url})`;
                    const next = value.slice(0, start) + link + value.slice(end);

                    textarea.value = next;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    textarea.focus();
                    const cursor = start + link.length;
                    textarea.setSelectionRange(cursor, cursor);
                },
                stripBasicMarkdown(textarea) {
                    const start = textarea.selectionStart ?? 0;
                    const end = textarea.selectionEnd ?? start;
                    const value = textarea.value ?? '';
                    const selected = value.slice(start, end) || value;
                    const cleaned = selected
                        .replace(/\*\*(.*?)\*\*/g, '$1')
                        .replace(/_(.*?)_/g, '$1')
                        .replace(/^##\s+/gm, '')
                        .replace(/^-\s+/gm, '')
                        .replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g, '$1');

                    let next = '';

                    if (value.slice(start, end)) {
                        next = value.slice(0, start) + cleaned + value.slice(end);
                    } else {
                        next = cleaned;
                    }

                    textarea.value = next;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    textarea.focus();
                },
                handleShortcuts(event, textarea) {
                    const shortcutKey = (event.ctrlKey || event.metaKey) ? event.key.toLowerCase() : null;

                    if (shortcutKey === 'b') {
                        event.preventDefault();
                        this.wrap(textarea, '**', '**');
                        return;
                    }

                    if (shortcutKey === 'i') {
                        event.preventDefault();
                        this.wrap(textarea, '_', '_');
                        return;
                    }

                    if (shortcutKey === 'k') {
                        event.preventDefault();
                        this.addLink(textarea);
                    }
                },
            };
        }
    </script>
@endonce