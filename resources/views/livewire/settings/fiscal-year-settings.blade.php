<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Fiscal Year Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Fiscal Year')" :subheading="__('Configure the UK fiscal year period used for active/inactive biller calculations')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            {{-- Preview Banner --}}
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 dark:border-indigo-800 dark:bg-indigo-950/30 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <flux:icon name="calendar-days" class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                    <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">Current Period</span>
                </div>
                <p class="text-sm font-medium text-indigo-900 dark:text-indigo-100">
                    FY {{ $previewLabel }} &mdash; {{ $previewStart }} to {{ $previewEnd }}
                </p>
            </div>

            {{-- Start Date --}}
            <div>
                <flux:heading size="sm">{{ __('Fiscal Year Start') }}</flux:heading>
                <flux:text class="mb-3">The date the fiscal year begins each year.</flux:text>
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Month') }}</flux:label>
                        <flux:select wire:model="startMonth">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}">{{ Carbon\Carbon::create()->month($m)->format('F') }} ({{ $m }})</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Day') }}</flux:label>
                        <flux:input type="number" wire:model="startDay" min="1" max="31" />
                    </flux:field>
                </div>
            </div>

            {{-- End Date --}}
            <div>
                <flux:heading size="sm">{{ __('Fiscal Year End') }}</flux:heading>
                <flux:text class="mb-3">The date the fiscal year ends each year.</flux:text>
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Month') }}</flux:label>
                        <flux:select wire:model="endMonth">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}">{{ Carbon\Carbon::create()->month($m)->format('F') }} ({{ $m }})</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Day') }}</flux:label>
                        <flux:input type="number" wire:model="endDay" min="1" max="31" />
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <flux:button variant="primary" type="submit">{{ __('Save Settings') }}</flux:button>
            </div>
        </form>

        {{-- Week Number Mapping --}}
        <div class="mt-10 border-t border-slate-200 dark:border-slate-700 pt-8">
            <flux:heading size="sm">{{ __('Week Number Mapping') }}</flux:heading>
            <flux:text class="mb-4">Auto-generated week numbers and their corresponding ending dates for the current fiscal year. Weeks run Monday to Sunday.</flux:text>

            @if(count($weekMapping) > 0)
                <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-700 dark:text-slate-300">Week #</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-700 dark:text-slate-300">Week Start (Monday)</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-700 dark:text-slate-300">Week Ending (Sunday)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($weekMapping as $week)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="px-4 py-2 font-medium text-slate-900 dark:text-slate-100">
                                        Week {{ $week['week'] }}
                                    </td>
                                    <td class="px-4 py-2 text-slate-600 dark:text-slate-400">
                                        {{ $week['start'] }}
                                    </td>
                                    <td class="px-4 py-2 text-slate-600 dark:text-slate-400">
                                        {{ $week['end'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ count($weekMapping) }} weeks in FY {{ $previewLabel }}</p>
            @else
                <p class="text-sm text-slate-500 dark:text-slate-400">Save the fiscal year settings to generate the week mapping.</p>
            @endif
        </div>
    </x-settings.layout>
</section>
