<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="w-full mb-6">
    <div class="flex w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 shadow-sm">
        <div class="flex items-center w-full">
            @php
                $currentStage = is_object($stage) ? $stage->value : strtolower(trim((string) $stage));

                $stageValues = collect($stages)->map(fn ($s) => $s->value)->values();
                $currentStageIndex = $stageValues->search($currentStage);
                $currentStageIndex = $currentStageIndex !== false ? $currentStageIndex : 0;
            @endphp

            @foreach ($stages as $index => $listStage)
                @php
                    $cfg = $stageConfig[$listStage->value] ?? [
                        'accent' => '#64748b',
                        'label' => ucwords($listStage->value),
                        'icon' => '📌',
                    ];

                    $isActive = $currentStage === $listStage->value;
                    // When stage is 'lost', don't mark other stages as completed - they remain pending
                    $isCompleted = $currentStage !== 'lost' && $index < $currentStageIndex;
                    $isUpcoming = $index > $currentStageIndex || $currentStage === 'lost';
                    $canMove = ! $isActive && $this->canChangeStage($listStage->value);
                @endphp

                <div class="flex items-center flex-1 min-w-[160px]">
                    {{-- Stage Button --}}
                    <button
                        wire:click="{{ $canMove ? "setStage('{$listStage->value}')" : '' }}"
                        wire:loading.attr="disabled"
                        wire:target="setStage"
                        @if (! $canMove && ! $isActive) disabled title="Your team cannot move deals to this stage" @endif
                        class="group relative w-full rounded-xl border transition-all duration-300 px-4 py-3 text-left overflow-hidden
                            {{ $isActive ? 'cursor-default' : ($canMove ? 'cursor-pointer hover:scale-[1.02] hover:shadow-md' : 'opacity-50 cursor-not-allowed') }}"
                        @style([
                            'background-color: '.$cfg['accent'].'15; border-color: '.$cfg['accent'].'50' => $isActive,
                            'background-color:#dcfce7; border-color:#22c55e' => $isCompleted && $canMove,
                            'background-color:#f0fdf4; border-color:#bbf7d0' => $isCompleted && ! $canMove,
                            'background-color:transparent; border-color:transparent' => $isUpcoming,
                        ])>
                        {{-- Active Glow --}}
                        @if ($isActive)
                            <div class="absolute inset-0 opacity-8" @style(['background: '.$cfg['accent']])></div>
                        @endif

                        <div class="relative flex items-start gap-3">
                            {{-- Icon --}}
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-base shrink-0"
                                @style([
                                    'background: '.$cfg['accent'].'20; color:'.$cfg['accent'] => $isActive,
                                    'background:#22c55e20; color:#16a34a' => $isCompleted && $canMove,
                                    'background:#f1f5f920; color:#94a3b8' => $isCompleted && ! $canMove,
                                    'background:#f1f5f9; color:#64748b' => $isUpcoming,
                                ])>
                                @if (! $canMove && ! $isActive)
                                    🔒
                                @elseif ($isCompleted)
                                    ✓
                                @else
                                    {{ $cfg['icon'] }}
                                @endif
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-sm truncate"
                                    @style([
                                        'color:'.$cfg['accent'] => $isActive,
                                        'color:#15803d' => $isCompleted && $canMove,
                                        'color:#94a3b8' => $isCompleted && ! $canMove,
                                        'color:#64748b' => $isUpcoming,
                                    ])>
                                    {{ $cfg['label'] }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    @if ($isActive)
                                        Current Stage
                                    @elseif (! $canMove)
                                        Compliance only
                                    @elseif ($isCompleted)
                                        Completed
                                    @else
                                        Pending
                                    @endif
                                </p>
                            </div>
                        </div>
                    </button>

                    {{-- Connector --}}
                    @if (! $loop->last)
                        <div class="flex-1 h-[2px] mx-2 rounded-full
                            {{ $index < $currentStageIndex ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
