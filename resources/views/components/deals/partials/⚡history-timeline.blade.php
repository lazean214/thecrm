<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

{{-- components/deals/partials/⚡history-timeline.blade.php --}}

<div class="flow-root">
    <ul role="list" class="-mb-6">
        @forelse ($deal->histories as $history)
            <li>
                <div class="relative pb-6">
                    @if (! $loop->last)
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200 dark:bg-slate-700" aria-hidden="true"></span>
                    @endif
                    <div class="relative flex items-start space-x-3">
                        <div class="relative">
                            <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center ring-4 ring-white dark:ring-slate-800"
                                @php
                                    $icon = match ($history->action) {
                                        'created' => '🎉',
                                        'stage_moved' => '🔄',
                                        'details_updated' => '✏️',
                                        'association_updated' => '🔗',
                                        'owner_changed' => '👤',
                                        default => '📝',
                                    };
                                @endphp>
                                <span class="text-sm">{{ $icon }}</span>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ match ($history->action) {
                                        'created' => 'Deal Created',
                                        'stage_moved' => 'Stage Changed',
                                        'details_updated' => 'Details Updated',
                                        'association_updated' => 'Association Updated',
                                        'owner_changed' => 'Owner Changed',
                                        default => ucfirst($history->action),
                                    } }}
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ $history->created_at->format('d M Y, H:i') }}
                                </span>
                            </div>
                            @if ($history->details)
                                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $history->details }}
                                </p>
                            @endif
                            @if ($history->user)
                                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                                    by {{ $history->user->name }}
                                </p>
                            @else
                                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">by System</p>
                            @endif
                        </div>
                    </div>
                </div>
            </li>
        @empty
            <li class="text-center py-8 text-sm text-slate-400 dark:text-slate-500">
                No activity recorded yet
            </li>
        @endforelse
    </ul>
</div>
