<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Data Management') }}</flux:heading>

    <x-settings.layout :heading="__('Data Management')" :subheading="__('Manage and purge CRM data')">
        <div class="mt-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                            <th class="px-4 py-3 text-left text-sm font-medium text-zinc-600 dark:text-zinc-300">Data Type</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-zinc-600 dark:text-zinc-300">Records</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-zinc-600 dark:text-zinc-300">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">Deals</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $dealsCount }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button variant="danger" size="sm" wire:click="purgeDeals" wire:confirm="Delete all {{ $dealsCount }} deals?">
                                    Purge Deals
                                </flux:button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">Contacts</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $contactsCount }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button variant="danger" size="sm" wire:click="purgeContacts" wire:confirm="Delete all {{ $contactsCount }} contacts?">
                                    Purge Contacts
                                </flux:button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">Companies</td>
                            <td class="px-4 py-3 text-sm text-zinc-500">{{ $companiesCount }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button variant="danger" size="sm" wire:click="purgeCompanies" wire:confirm="Delete all {{ $companiesCount }} companies?">
                                    Purge Companies
                                </flux:button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="mt-4 text-xs text-zinc-500">Warning: Purging data is irreversible. Pivot table records will also be deleted.</p>
        </div>
    </x-settings.layout>
</section>