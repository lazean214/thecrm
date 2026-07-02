<?php

use Livewire\Component;
use App\Models\Company;

new class extends Component {
    public $company;

    public $name = '';
    public $email = '';
    public $domain = '';
    public $phone = '';

    public function mount(Company $company)
    {
        $this->company = Company::query()
            ->with(['contacts', 'deals.user', 'emailLogs.user', 'emailLogs.deal'])
            ->findOrFail($company->getKey());

        $this->name = (string) $this->company->name;
        $this->email = (string) $this->company->email;
        $this->domain = (string) ($this->company->domain ?? '');
        $this->phone = (string) ($this->company->phone ?? '');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:companies,email,' . $this->company->id],
            'domain' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        $this->company->update($validated);
        $this->company->refresh()->loadMissing(['contacts', 'deals.user', 'emailLogs.user', 'emailLogs.deal']);

        session()->flash('success', 'Company updated successfully.');
    }
};
?>

<div>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
        <aside>
            <form wire:submit.prevent="save" class="space-y-4">
                <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-bold">Edit Company</h2>
                            <p class="text-sm text-slate-500">Update this company record and save the changes.</p>
                        </div>

                        <button type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">Save Company</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>

                    @if (session('success'))
                        <div
                            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                            <input id="name" wire:model="name" type="text"
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                            <input id="email" wire:model="email" type="email"
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                            <input id="phone" wire:model="phone" type="text"
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="domain" class="mb-1 block text-sm font-medium text-slate-700">Domain</label>
                            <input id="domain" wire:model="domain" type="text"
                                class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('domain')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>
            </form>
        </aside>

        <main class="space-y-4">
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-4 text-xl font-bold">Associated Deals</h2>
                <ul class="space-y-2">
                    @forelse($company->deals as $deal)
                        <li class="rounded-lg bg-slate-50 px-4 py-3">
                            <div class="font-medium">
                                <a href="{{ route('deals.show', $deal) }}" class="text-indigo-600 hover:underline">{{ $deal->name }}</a>
                            </div>
                            <div class="mt-1 text-sm text-slate-500">
                                Deal Owner: {{ $deal->user?->name ?? 'Unknown owner' }}
                            </div>
                        </li>
                    @empty
                        <li class="rounded-lg bg-slate-50 px-4 py-3 text-slate-500">No deals linked.</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-4 text-xl font-bold">Associated Contacts</h2>
                <ul class="space-y-2">
                    @forelse($company->contacts as $contact)
                        <li class="rounded-lg bg-slate-50 px-4 py-3">
                            <a href="{{ route('contacts.show', $contact) }}" class="font-medium text-indigo-600 hover:underline">{{ $contact->first_name }}
                                {{ $contact->last_name }}</a>
                        </li>
                    @empty
                        <li class="rounded-lg bg-slate-50 px-4 py-3 text-slate-500">No contacts linked.</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-4 text-xl font-bold">Email Activity</h2>
                <ul class="space-y-2">
                    @forelse($company->emailLogs->sortByDesc('created_at')->take(5) as $log)
                        <li class="rounded-lg bg-slate-50 px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $log->subject }}</div>
                            <div class="mt-1 text-sm text-slate-500">
                                {{ $log->status }}{{ $log->deal?->name ? ' • Deal: ' . $log->deal->name : '' }}</div>
                        </li>
                    @empty
                        <li class="rounded-lg bg-slate-50 px-4 py-3 text-slate-500">No email activity yet.</li>
                    @endforelse
                </ul>
            </section>
        </main>
    </div>
</div>
