<?php

use App\Models\Deal;
use App\Services\AiDealService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $dealId;

    public bool $loading = false;

    public bool $generated = false;

    public array $prompts = [];

    public array $dismissed = [];

    public function mount(int $dealId, array $dismissed = []): void
    {
        $this->dealId = $dealId;
        $this->dismissed = $dismissed;
    }

    #[Computed]
    public function deal(): Deal
    {
        return Deal::with('user', 'contacts', 'companies')->findOrFail($this->dealId);
    }

    public function generate(): void
    {
        $this->loading = true;
        $this->prompts = [];
        $this->generated = false;

        $this->prompts = app(AiDealService::class)->actionPrompts($this->deal);
        $this->generated = true;
        $this->loading = false;
    }

    public function dismiss(int $index): void
    {
        $this->dismissed[] = $index;
        if (isset($this->prompts[$index])) {
            unset($this->prompts[$index]);
            $this->prompts = array_values($this->prompts);
        }
    }

    public function refresh(): void
    {
        app(AiDealService::class)->forget($this->deal);
        $this->loading = true;
        $this->prompts = app(AiDealService::class)->actionPrompts($this->deal);
        $this->loading = false;
    }

    public function visiblePrompts(): array
    {
        return array_values(array_filter($this->prompts, fn ($key) => !in_array($key, $this->dismissed), ARRAY_FILTER_USE_KEY));
    }
};
?>

<div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-gradient-to-br from-amber-50/80 to-white dark:from-amber-950/30 dark:to-slate-800 p-5 mb-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Suggested Actions</h3>
        </div>

        @if ($generated)
            <button wire:click="refresh" wire:loading.attr="disabled" class="text-xs text-amber-500 hover:text-amber-700 dark:hover:text-amber-300 flex items-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg wire:loading.remove wire:target="refresh" class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 2v6h-6M3 12a9 9 0 0115-6.7L21 8M3 22v-6h6M21 12a9 9 0 01-15 6.7L3 16"/>
                </svg>
                <svg wire:loading.flex wire:target="refresh" class="animate-spin h-3.5 w-3.5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                </svg>
                <span wire:loading.remove wire:target="refresh">Refresh</span>
                <span wire:loading.flex wire:target="refresh">Refreshing...</span>
            </button>
        @endif
    </div>

    @if ($generated && count($this->visiblePrompts()) > 0)
        <div class="relative">
            @if ($loading)
                <div class="absolute inset-0 bg-amber-50/60 dark:bg-amber-950/40 rounded-lg flex items-center justify-center z-10">
                    <span class="text-xs text-amber-500 font-medium flex items-center gap-1.5">
                        <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                        </svg>
                        Updating...
                    </span>
                </div>
            @endif
            <ul class="space-y-2 @if($loading) opacity-40 @endif">
                @foreach ($this->visiblePrompts() as $index => $prompt)
                    <li class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <span class="text-amber-500 mt-0.5 shrink-0">→</span>
                        <span class="flex-1">{{ $prompt }}</span>
                        <button wire:click="dismiss({{ $index }})" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0" title="Dismiss">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif ($generated && count($this->prompts) === 0)
        <p class="text-sm text-slate-400 dark:text-slate-500 italic">No suggestions right now. <button wire:click="refresh" class="text-amber-500 hover:text-amber-700 underline">Refresh</button></p>
    @else
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-400 dark:text-slate-500 italic">Get AI-powered suggestions for next steps.</p>
            <button wire:click="generate" wire:loading.attr="disabled" class="text-xs font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-500/10 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <svg wire:loading.remove wire:target="generate" class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <svg wire:loading.flex wire:target="generate" class="animate-spin h-3.5 w-3.5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                </svg>
                <span wire:loading.remove wire:target="generate">Get Suggestions</span>
                <span wire:loading.flex wire:target="generate">Analysing...</span>
            </button>
        </div>
    @endif
</div>
