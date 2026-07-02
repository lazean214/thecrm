<?php

use Livewire\Component;
use App\Models\Contact;

new class extends Component
{
    public $contact;

    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $street_address = '';
    public $city = '';
    public $state = '';
    public $postal_code = '';
    public $country = '';
    public $ni_number = '';
    public $bank = '';
    public $account_number = '';
    public $sort_code = '';
    public $date_of_birth = '';
    public $marital_status = '';
    public $gender = '';

    public function mount(Contact $contact)
    {
        $this->contact = Contact::query()
            ->with(['deals.user', 'companies'])
            ->findOrFail($contact->getKey());

        $this->first_name = (string) $this->contact->first_name;
        $this->last_name = (string) $this->contact->last_name;
        $this->email = (string) ($this->contact->email ?? '');
        $this->phone = (string) ($this->contact->phone ?? '');
        $this->street_address = (string) ($this->contact->street_address ?? '');
        $this->city = (string) ($this->contact->city ?? '');
        $this->state = (string) ($this->contact->state ?? '');
        $this->postal_code = (string) ($this->contact->postal_code ?? '');
        $this->country = (string) ($this->contact->country ?? '');
        $this->ni_number = (string) ($this->contact->ni_number ?? '');
        $this->bank = (string) ($this->contact->bank ?? '');
        $this->account_number = (string) ($this->contact->account_number ?? '');
        $this->sort_code = (string) ($this->contact->sort_code ?? '');
        $this->date_of_birth = (string) ($this->contact->date_of_birth ?? '');
        $this->marital_status = (string) ($this->contact->marital_status ?? '');
        $this->gender = (string) ($this->contact->gender ?? '');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:contacts,email,' . $this->contact->id],
            'phone' => ['nullable', 'string', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'ni_number' => ['nullable', 'string', 'max:255'],
            'bank' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'sort_code' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
        ]);

        $this->contact->update($validated);
        $this->contact->refresh()->loadMissing(['deals.user', 'companies']);

        session()->flash('success', 'Contact updated successfully.');
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
                            <h2 class="text-xl font-bold">Edit Contact</h2>
                            <p class="text-sm text-slate-500">Update this contact record and save the changes.</p>
                        </div>

                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">Save Contact</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="first_name" class="mb-1 block text-sm font-medium text-slate-700">First Name</label>
                            <input id="first_name" wire:model="first_name" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="last_name" class="mb-1 block text-sm font-medium text-slate-700">Last Name</label>
                            <input id="last_name" wire:model="last_name" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                            <input id="email" wire:model="email" type="email" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                            <input id="phone" wire:model="phone" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="date_of_birth" class="mb-1 block text-sm font-medium text-slate-700">Date of Birth</label>
                            <input id="date_of_birth" wire:model="date_of_birth" type="date" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('date_of_birth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="gender" class="mb-1 block text-sm font-medium text-slate-700">Gender</label>
                            <input id="gender" wire:model="gender" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('gender') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="marital_status" class="mb-1 block text-sm font-medium text-slate-700">Marital Status</label>
                            <select id="marital_status" wire:model="marital_status" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200">
                                <option value="">Select Marital Status</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="divorced">Divorced</option>
                                <option value="widowed">Widowed</option>
                            </select>
                            @error('marital_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-xl font-bold">Address</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="street_address" class="mb-1 block text-sm font-medium text-slate-700">Street Address</label>
                            <input id="street_address" wire:model="street_address" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('street_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="city" class="mb-1 block text-sm font-medium text-slate-700">City</label>
                            <input id="city" wire:model="city" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="state" class="mb-1 block text-sm font-medium text-slate-700">State</label>
                            <input id="state" wire:model="state" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="country" class="mb-1 block text-sm font-medium text-slate-700">Country</label>
                            <input id="country" wire:model="country" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="postal_code" class="mb-1 block text-sm font-medium text-slate-700">Postal Code</label>
                            <input id="postal_code" wire:model="postal_code" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('postal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-xl font-bold">Legal & Bank</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="ni_number" class="mb-1 block text-sm font-medium text-slate-700">NI Number</label>
                            <input id="ni_number" wire:model="ni_number" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('ni_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank" class="mb-1 block text-sm font-medium text-slate-700">Bank</label>
                            <input id="bank" wire:model="bank" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('bank') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="account_number" class="mb-1 block text-sm font-medium text-slate-700">Account Number</label>
                            <input id="account_number" wire:model="account_number" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('account_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="sort_code" class="mb-1 block text-sm font-medium text-slate-700">Sort Code</label>
                            <input id="sort_code" wire:model="sort_code" type="text" class="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                            @error('sort_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>
            </form>
        </aside>
        <main class="space-y-4">
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-xl font-bold mb-4">Associated Deals</h2>
                <ul class="space-y-2">
                    @forelse($contact->deals as $deal)
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
                <h2 class="text-xl font-bold mb-4">Associated Companies</h2>
                <ul class="space-y-2">
                    @forelse($contact->companies as $company)
                        <li class="rounded-lg bg-slate-50 px-4 py-3">
                            <a href="{{ route('companies.show', $company) }}" class="font-medium text-indigo-600 hover:underline">{{ $company->name }}</a>
                        </li>
                    @empty
                        <li class="rounded-lg bg-slate-50 px-4 py-3 text-slate-500">No companies linked.</li>
                    @endforelse
                </ul>
            </section>
        </main>

    </div>
</div>