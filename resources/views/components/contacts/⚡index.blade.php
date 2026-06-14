<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10],
    ];

    #[\Livewire\Attributes\On('contact-created')]
    #[\Livewire\Attributes\On('import-completed')]
    #[\Livewire\Attributes\On('refresh-contacts')]
    public function refreshContacts()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc'
                ? 'desc'
                : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function delete($id)
    {
        Contact::find($id)?->delete();

        session()->flash(
            'success',
            'Contact deleted successfully!'
        );

        $this->resetPage();
    }

    public function getContactsProperty()
    {
        return Contact::with('deals', 'companies')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere(
                            'email',
                            'like',
                            '%'.$this->search.'%'
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            '%'.$this->search.'%'
                        );
                });
            })
            ->orderBy(
                $this->sortBy,
                $this->sortDirection
            )
            ->paginate($this->perPage);
    }
};

?>

<div class="p-6 space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
                Contacts
            </h1>

            <p class="text-slate-600 dark:text-slate-400 mt-2">
                Manage your contacts and their associated deals and companies.
            </p>
        </div>

        <div class="flex gap-2">
            <livewire:contacts.import />
            <livewire:contacts.create />
        </div>
    </div>

    <!-- Search + Limit -->
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <!-- Search -->
            <div class="flex items-center gap-2 flex-1">
                <svg class="w-5 h-5 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
                    </path>
                </svg>

                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, email, or phone..."
                    class="flex-1 bg-transparent text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none">
            </div>

            <!-- Limit -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-slate-600 dark:text-slate-400">
                    Show
                </label>

                <select
                    wire:model.live="perPage"
                    class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">

                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>

                <span class="text-sm text-slate-600 dark:text-slate-400">
                    entries
                </span>
            </div>
        </div>
    </div>

    <!-- Success -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5"
                fill="currentColor"
                viewBox="0 0 20 20">

                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd">
                </path>
            </svg>

            <p class="text-sm font-medium text-green-800 dark:text-green-100">
                {{ session('success') }}
            </p>
        </div>
    @endif

    <!-- Error -->
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 flex items-start gap-3">
            <p class="text-sm font-medium text-red-800 dark:text-red-100">
                {{ session('error') }}
            </p>
        </div>
    @endif

    <!-- Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">

        @if($this->contacts->count())

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-700 dark:text-slate-300">

                    <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-600">
                        <tr>

                            <!-- ID -->
                            <th
                                wire:click="sort('id')"
                                class="px-6 py-4 font-semibold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50 transition">

                                <div class="flex items-center gap-2">
                                    ID

                                    @if($sortBy === 'id')
                                        <span>
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    @endif
                                </div>
                            </th>

                            <!-- Name -->
                            <th
                                wire:click="sort('first_name')"
                                class="px-6 py-4 font-semibold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50 transition">

                                <div class="flex items-center gap-2">
                                    Name

                                    @if($sortBy === 'first_name')
                                        <span>
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    @endif
                                </div>
                            </th>

                            <!-- Email -->
                            <th
                                wire:click="sort('email')"
                                class="px-6 py-4 font-semibold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50 transition">

                                <div class="flex items-center gap-2">
                                    Email

                                    @if($sortBy === 'email')
                                        <span>
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    @endif
                                </div>
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Phone
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Deals
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Companies
                            </th>

                            <th class="px-6 py-4 font-semibold">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">

                        @foreach($this->contacts as $contact)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">

                                <td class="px-6 py-4">
                                    {{ $contact->id }}
                                </td>

                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                    {{ $contact->first_name }}
                                    {{ $contact->last_name }}
                                </td>

                                <td class="px-6 py-4 break-all">
                                    {{ $contact->email }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ $contact->phone ?: '—' }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                                        {{ $contact->deals->count() }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200">
                                        {{ $contact->companies->count() }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 flex gap-2">

                                    <a
                                        href="{{ route('contacts.show', $contact->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200">

                                        View
                                    </a>

                                    <button
                                        wire:click="delete({{ $contact->id }})"
                                        wire:confirm="Are you sure?"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200">

                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 dark:bg-slate-700/30 border-t border-slate-200 dark:border-slate-700 px-6 py-4">

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Showing
                        <span class="font-semibold">
                            {{ $this->contacts->firstItem() ?? 0 }}
                        </span>
                        to
                        <span class="font-semibold">
                            {{ $this->contacts->lastItem() ?? 0 }}
                        </span>
                        of
                        <span class="font-semibold">
                            {{ $this->contacts->total() }}
                        </span>
                        contacts
                    </p>

                    {{ $this->contacts->links() }}

                </div>
            </div>

        @else

            <!-- Empty State -->
            <div class="p-12 text-center">

                <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">
                    No contacts found
                </h3>

                <p class="mt-2 text-slate-600 dark:text-slate-400">
                    @if($search)
                        Try adjusting your search criteria
                    @else
                        Get started by creating your first contact
                    @endif
                </p>

            </div>

        @endif
    </div>
</div>