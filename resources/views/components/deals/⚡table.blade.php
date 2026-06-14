<?php

use Livewire\Component;
use App\Models\Deal;
use App\Models\User;
use App\Models\Company;
use App\Enums\DealStage;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public $deals = [];
    public $view = 'kanban';
    public array $stages = [];

    // --- Pagination (table view) ---
    public int $perPage = 25;
    public int $currentPage = 1;
    public int $totalDeals = 0;
    public int $totalPages = 1;
    public int $paginationFrom = 0;
    public int $paginationTo = 0;

    // --- Lazy load (kanban view) ---
    public int $kanbanLoadedCount = 30;
    public bool $kanbanHasMore = false;

    // --- Column Visibility ---
    /** @var array<string> */
    public array $visibleColumns = ['name', 'owner', 'contact', 'company', 'amount', 'stage', 'created_at'];

    /** @var array<string, array{label: string, group: string}> */
    public const AVAILABLE_COLUMNS = [
        'name' => ['label' => 'Deal Name', 'group' => 'Deal'],
        'amount' => ['label' => 'Amount', 'group' => 'Deal'],
        'stage' => ['label' => 'Stage', 'group' => 'Deal'],
        'recruitment_agency' => ['label' => 'Recruitment Agency', 'group' => 'Deal'],
        'consultant_name' => ['label' => 'Consultant Name', 'group' => 'Deal'],
        'agency_deal_value' => ['label' => 'Agency Deal Value', 'group' => 'Deal'],
        'margin_agreed' => ['label' => 'Margin Agreed', 'group' => 'Deal'],
        'date_sent' => ['label' => 'Date Sent', 'group' => 'Deal'],
        'date_signed' => ['label' => 'Date Signed', 'group' => 'Deal'],
        'who_signed' => ['label' => 'Who Signed', 'group' => 'Deal'],
        'right_to_work' => ['label' => 'Right to Work', 'group' => 'Deal'],
        'mda_reference_number' => ['label' => 'MDA Reference', 'group' => 'Deal'],
        'date_set_up' => ['label' => 'Date Set Up', 'group' => 'Deal'],
        'tax_code' => ['label' => 'Tax Code', 'group' => 'Deal'],
        'created_at' => ['label' => 'Created', 'group' => 'Deal'],
        'owner' => ['label' => 'Owner', 'group' => 'Owner'],
        'owner_email' => ['label' => 'Owner Email', 'group' => 'Owner'],
        'contact' => ['label' => 'Contact', 'group' => 'Contact'],
        'company' => ['label' => 'Company', 'group' => 'Company'],
        'company_email' => ['label' => 'Company Email', 'group' => 'Company'],
        'company_phone' => ['label' => 'Company Phone', 'group' => 'Company'],
        'company_domain' => ['label' => 'Company Domain', 'group' => 'Company'],
    ];

    // --- CRM Live Filters ---
    public string $filterDealName = '';
    public string $filterOwner = '';
    public string $filterContact = '';
    public string $filterCompanyName = '';
    public string $filterStage = '';
    public $minAmount = null;
    public $maxAmount = null;
    public $dateFrom = null;
    public $dateTo = null;
    public bool $isDefaultDateRange = false;

    // --- BATCH ---
    public array $selectedDeals = [];
    public bool $selectAll = false;
    public string $batchOperation = '';
    public string $batchOwnerValue = '';
    public string $batchStageValue = '';
    public bool $showBatchModal = false;
    public bool $showConfirmModal = false;
    public string $confirmMessage = '';

    // --- Deferred lookup data (loaded on demand) ---
    public array $allUsers = [];
    public array $allCompanies = [];
    public bool $lookupsLoaded = false;

    // Cache key derived from current filter state
    private function filterCacheKey(string $prefix = ''): string
    {
        $parts = [
            auth()->id(),
            $this->view,
            $this->filterDealName,
            $this->filterOwner,
            $this->filterContact,
            $this->filterCompanyName,
            $this->filterStage,
            $this->minAmount,
            $this->maxAmount,
            $this->dateFrom,
            $this->dateTo,
        ];

        return 'deals_' . $prefix . md5(serialize($parts));
    }

    /**
     * Push an incoming background deal straight into tracking.
     */
    #[On('echo:deals,DealCreated')]
    public function appendIncomingDeal(array $rawDeal, int $targetUserId): void
    {
        $user = $this->getCurrentUser();
        if (! $user || $user->id !== $targetUserId) {
            return;
        }

        if (collect($this->deals)->contains('id', $rawDeal['id'])) {
            return;
        }

        array_unshift($this->deals, $rawDeal);
        $this->totalDeals++;

        if ($this->view === 'table' && count($this->deals) > $this->perPage) {
            array_pop($this->deals);
        }
    }

    private function onFilterChanged(): void
    {
        $this->currentPage = 1;
        $this->kanbanLoadedCount = 30;
        $this->persistState();
        $this->loadDeals();
        $this->resetBatchState();
    }

    public function updatedFilterDealName(): void { $this->onFilterChanged(); }
    public function updatedFilterOwner(): void { $this->onFilterChanged(); }
    public function updatedFilterContact(): void { $this->onFilterChanged(); }
    public function updatedFilterCompanyName(): void { $this->onFilterChanged(); }
    public function updatedFilterStage(): void { $this->onFilterChanged(); }
    public function updatedMinAmount(): void { $this->onFilterChanged(); }
    public function updatedMaxAmount(): void { $this->onFilterChanged(); }
    public function updatedDateFrom(): void
    {
        $this->isDefaultDateRange = false;
        $this->onFilterChanged();
    }
    public function updatedDateTo(): void { $this->onFilterChanged(); }

    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
        $this->persistState();
        $this->loadDeals();
        $this->resetBatchState();
    }

    /**
     * Load lookup data (users, companies) on demand.
     * Called via wire:click when the user opens the filter modal.
     */
    #[On('loadLookups')]
    public function loadLookups(): void
    {
        if (! $this->lookupsLoaded) {
            $this->allUsers = User::orderBy('name')->get(['id', 'name', 'email'])->toArray();
            $this->allCompanies = Company::orderBy('name')->get(['id', 'name'])->toArray();
            $this->lookupsLoaded = true;
        }
    }

    public function mount(): void
    {
        $this->stages = array_map(
            fn ($s) => $s->value,
            [DealStage::DOC_SENT, DealStage::DOC_SIGNED, DealStage::COMPLIANT, DealStage::READY_FOR_PAYMENT, DealStage::PAID],
        );

        // Restore persisted view state
        $state = session('deals_view_state', []);

        $this->view = $state['view'] ?? 'kanban';
        $this->perPage = $state['perPage'] ?? 25;
        $this->visibleColumns = $state['visibleColumns'] ?? ['name', 'owner', 'contact', 'company', 'amount', 'stage', 'created_at'];
        $this->filterDealName = $state['filterDealName'] ?? '';
        $this->filterOwner = $state['filterOwner'] ?? '';
        $this->filterContact = $state['filterContact'] ?? '';
        $this->filterCompanyName = $state['filterCompanyName'] ?? '';
        $this->filterStage = $state['filterStage'] ?? '';
        $this->minAmount = $state['minAmount'] ?? null;
        $this->maxAmount = $state['maxAmount'] ?? null;
        $this->isDefaultDateRange = $state['isDefaultDateRange'] ?? false;

        if (array_key_exists('dateFrom', $state)) {
            $this->dateFrom = $state['dateFrom'];
            $this->dateTo = $state['dateTo'];
        } else {
            $this->dateFrom = now()->startOfMonth()->toDateString();
            $this->dateTo = null;
            $this->isDefaultDateRange = true;
        }

        $this->loadDeals();

        // Auto-widen: if default month filter returns < 100 results, show all-time.
        // Only do this once — skip the double-query if we already have 30+ records.
        if ($this->isDefaultDateRange && $this->getTotalResultCount() < 30) {
            $this->dateFrom = null;
            $this->isDefaultDateRange = false;
            $this->loadDeals();
        }
    }

    private function persistState(): void
    {
        session([
            'deals_view_state' => [
                'view' => $this->view,
                'perPage' => $this->perPage,
                'visibleColumns' => $this->visibleColumns,
                'filterDealName' => $this->filterDealName,
                'filterOwner' => $this->filterOwner,
                'filterContact' => $this->filterContact,
                'filterCompanyName' => $this->filterCompanyName,
                'filterStage' => $this->filterStage,
                'minAmount' => $this->minAmount,
                'maxAmount' => $this->maxAmount,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'isDefaultDateRange' => $this->isDefaultDateRange,
            ],
        ]);
    }

    private function getTotalResultCount(): int
    {
        return $this->view === 'table' ? $this->totalDeals : count($this->deals);
    }

    private function getCurrentUser(): ?User
    {
        return auth()->user();
    }

    public function isSalesTeam(): bool
    {
        $user = $this->getCurrentUser();

        return $user && $user->isSalesTeam();
    }

    public function isComplianceTeam(): bool
    {
        $user = $this->getCurrentUser();

        return $user && $user->isComplianceTeam();
    }

    /**
     * Compute allowed stages once and cache on the component.
     */
    public function getAllowedStagesForUser(): array
    {
        $user = $this->getCurrentUser();

        return $user ? $user->getAllowedDealStages() : [];
    }

    public function canEditDealStage(): bool
    {
        return count($this->getAllowedStagesForUser()) > 0;
    }

    /**
     * Pre-compute the set of stages this user can move deals INTO,
     * and stages they can drag FROM. Cached to avoid per-card queries.
     */
    public function getEditableStages(): array
    {
        return array_filter($this->stages, fn ($s) => $this->canEditStage($s));
    }

    public function canEditStage($stage, $currentDealStage = null): bool
    {
        $user = $this->getCurrentUser();
        if (! $user) {
            return false;
        }

        if ($currentDealStage && $user->isSalesTeam() && ! $user->canMoveToStage($currentDealStage)) {
            return false;
        }

        return $user->canMoveToStage($stage);
    }

    /**
     * Build the base query with eager-loaded relations.
     * Only loads the fields actually used by the UI.
     */
    private function buildQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Deal::query()
            ->with([
                'contacts:id,first_name,last_name',
                'companies:id,name,email,phone,domain',
                'user:id,name,email',
            ]);

        $user = $this->getCurrentUser();

        // Sales Team: only own deals
        if ($user && $user->isSalesTeam()) {
            $query->where('user_id', $user->id);
        }

        // Deal name (simple column — uses index)
        if (! empty($this->filterDealName)) {
            $query->where('name', 'like', '%'.$this->filterDealName.'%');
        }

        // Stage (indexed)
        if (! empty($this->filterStage)) {
            $query->where('stage', $this->filterStage);
        }

        // Amount range (indexed)
        if (! is_null($this->minAmount) && $this->minAmount !== '') {
            $query->where('amount', '>=', $this->minAmount);
        }
        if (! is_null($this->maxAmount) && $this->maxAmount !== '') {
            $query->where('amount', '<=', $this->maxAmount);
        }

        // Created date range
        if (! empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if (! empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Owner name (whereHas — unavoidable but filtered early)
        if (! empty($this->filterOwner)) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$this->filterOwner.'%'));
        }

        // Contact name — use separate firstName/lastName lookup instead of CONCAT
        if (! empty($this->filterContact)) {
            $query->whereHas('contacts', fn ($q) => $q->where('first_name', 'like', '%'.$this->filterContact.'%')
                ->orWhere('last_name', 'like', '%'.$this->filterContact.'%'));
        }

        // Company name
        if (! empty($this->filterCompanyName)) {
            $query->whereHas('companies', fn ($q) => $q->where('name', 'like', '%'.$this->filterCompanyName.'%'));
        }

        return $query;
    }

    #[On('triggerLoadDeal')]
    public function loadDeals(): void
    {
        $query = $this->buildQuery();

        $mapper = function ($deal) {
            $arr = $deal->toArray();
            $arr['stage'] = $deal->stage instanceof \BackedEnum ? $deal->stage->value : (string) $deal->stage;

            return $arr;
        };

        if ($this->view === 'table') {
            // Use short-lived cache for count to avoid repeated COUNT(*)
            $countKey = $this->filterCacheKey('count_');
            $this->totalDeals = Cache::remember($countKey, now()->addSeconds(30), fn () => (clone $query)->count());

            $this->totalPages = max(1, (int) ceil($this->totalDeals / $this->perPage));
            $this->currentPage = min($this->currentPage, $this->totalPages);

            $this->paginationFrom = $this->totalDeals === 0 ? 0 : ($this->currentPage - 1) * $this->perPage + 1;
            $this->paginationTo = min($this->currentPage * $this->perPage, $this->totalDeals);

            $this->deals = $query
                ->latest('updated_at')
                ->skip(($this->currentPage - 1) * $this->perPage)
                ->take($this->perPage)
                ->get()
                ->map($mapper)
                ->toArray();
        } else {
            // Kanban — lazy load
            $totalKey = $this->filterCacheKey('kanban_total_');
            $total = Cache::remember($totalKey, now()->addSeconds(30), fn () => (clone $query)->count());

            $this->kanbanHasMore = $total > $this->kanbanLoadedCount;

            $this->deals = $query
                ->latest('updated_at')
                ->take($this->kanbanLoadedCount)
                ->get()
                ->map($mapper)
                ->toArray();
        }
    }

    public function refreshDeals(): void
    {
        // Only refresh if user is actively on the page (Livewire handles this)
        $this->loadDeals();
    }

    public function loadMoreKanban(): void
    {
        $this->kanbanLoadedCount += 30;
        $this->loadDeals();
    }

    public function resetFilters(): void
    {
        $this->reset(['filterDealName', 'filterOwner', 'filterContact', 'filterCompanyName', 'filterStage', 'minAmount', 'maxAmount', 'dateTo']);
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->isDefaultDateRange = true;
        $this->currentPage = 1;
        $this->kanbanLoadedCount = 30;
        $this->persistState();
        $this->loadDeals();
        $this->resetBatchState();

        if ($this->isDefaultDateRange && $this->getTotalResultCount() < 30) {
            $this->dateFrom = null;
            $this->isDefaultDateRange = false;
            $this->loadDeals();
        }
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = max(1, min($page, $this->totalPages));
        $this->loadDeals();
        $this->resetBatchState();
    }

    public function showAllTime(): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->isDefaultDateRange = false;
        $this->currentPage = 1;
        $this->kanbanLoadedCount = 30;
        $this->persistState();
        $this->loadDeals();
        $this->resetBatchState();
    }

    public function hasActiveFilters(): bool
    {
        return ! empty($this->filterDealName) ||
            ! empty($this->filterOwner) ||
            ! empty($this->filterContact) ||
            ! empty($this->filterCompanyName) ||
            ! empty($this->filterStage) ||
            ($this->minAmount !== null && $this->minAmount !== '') ||
            ($this->maxAmount !== null && $this->maxAmount !== '') ||
            (! $this->isDefaultDateRange && ! empty($this->dateFrom)) ||
            ! empty($this->dateTo);
    }

    /**
     * Update deal stage with authorization checks.
     * Patches local array instead of full reload.
     */
    public function updateStage($dealId, $newStage): void
    {
        $user = $this->getCurrentUser();

        if (! $user) {
            $this->dispatch('error', message: 'Unauthorized');

            return;
        }

        $deal = Deal::findOrFail($dealId);
        $oldStage = $deal->stage->value;

        // ── Authorization ──
        if ($user->isSalesTeam()) {
            if ($deal->user_id !== $user->id) {
                $this->dispatch('error', message: 'You can only edit your own deals');

                return;
            }

            if (! $user->canMoveToStage($deal->stage->value)) {
                $this->dispatch('error', message: 'This deal is managed by the Compliance Team.');

                return;
            }

            if (! $user->canMoveToStage($newStage)) {
                $allowed = implode(', ', $user->getAllowedDealStages());
                $this->dispatch('error', message: "You can only move to: {$allowed}");

                return;
            }
        }

        // ── Save ──
        $deal->stage = DealStage::from($newStage);
        $deal->save();

        $this->dispatch('deals-updated');

        $reason = $user->isSalesTeam() ? 'Sales Team action' : 'Compliance Team action';
        $deal->logStageChange($oldStage, $newStage, $reason);

        // ── Patch local array (no full reload) ──
        foreach ($this->deals as &$existingDeal) {
            if ($existingDeal['id'] == $dealId) {
                $existingDeal['stage'] = $newStage;
                break;
            }
        }
        unset($existingDeal);

        $this->dispatch('success', message: 'Deal moved successfully');
    }

    // ─── BATCH OPERATIONS ───

    public function resetBatchState(): void
    {
        $this->selectedDeals = [];
        $this->selectAll = false;
        $this->batchOperation = '';
        $this->batchOwnerValue = '';
        $this->batchStageValue = '';
        $this->showBatchModal = false;
        $this->showConfirmModal = false;

        $this->dispatch('deals-updated');
    }

    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;

        if ($this->selectAll) {
            if ($this->hasActiveFilters()) {
                $this->showConfirmModal = true;
                $this->confirmMessage = 'Select all '.count($this->deals).' deals from the filtered results? This will apply the batch operation to all matching records.';
            } else {
                $this->selectedDeals = array_map(fn ($deal) => $deal['id'], $this->deals);
            }
        } else {
            $this->selectedDeals = [];
        }
    }

    public function confirmSelectAll(): void
    {
        $this->selectedDeals = array_map(fn ($deal) => $deal['id'], $this->deals);
        $this->showConfirmModal = false;
    }

    public function cancelSelectAll(): void
    {
        $this->selectAll = false;
        $this->showConfirmModal = false;
    }

    public function toggleDealSelection($dealId): void
    {
        if (in_array($dealId, $this->selectedDeals)) {
            $this->selectedDeals = array_filter($this->selectedDeals, fn ($id) => $id !== $dealId);
            $this->selectAll = false;
        } else {
            $this->selectedDeals[] = $dealId;
            $visibleIds = array_map(fn ($deal) => $deal['id'], $this->deals);
            if (count($this->selectedDeals) === count($visibleIds) && empty(array_diff($visibleIds, $this->selectedDeals))) {
                $this->selectAll = true;
            }
        }
    }

    public function getSelectedCount(): int
    {
        return count($this->selectedDeals);
    }

    public function openBatchModal($operation): void
    {
        if (empty($this->selectedDeals)) {
            $this->dispatch('error', message: 'Please select at least one deal');

            return;
        }

        $this->batchOperation = $operation;
        $this->batchOwnerValue = '';
        $this->batchStageValue = '';
        $this->showBatchModal = true;
    }

    public function confirmBatchUpdateOwner(): void
    {
        if (empty($this->batchOwnerValue)) {
            $this->dispatch('error', message: 'Please select an owner');

            return;
        }

        $this->confirmMessage = 'Update owner for '.count($this->selectedDeals).' deal(s)?';
        $this->showBatchModal = false;
        $this->showConfirmModal = true;
    }

    public function confirmBatchUpdateStage(): void
    {
        if (empty($this->batchStageValue)) {
            $this->dispatch('error', message: 'Please select a stage');

            return;
        }

        $this->confirmMessage = 'Update stage for '.count($this->selectedDeals).' deal(s)?';
        $this->showBatchModal = false;
        $this->showConfirmModal = true;
    }

    public function confirmBatchDelete(): void
    {
        $this->confirmMessage = 'Delete '.count($this->selectedDeals).' deal(s)? This action cannot be undone.';
        $this->showBatchModal = false;
        $this->showConfirmModal = true;
    }

    public function executeBatchUpdateOwner(): void
    {
        $user = $this->getCurrentUser();

        if (! $user || ! $this->isComplianceTeam()) {
            $this->dispatch('error', message: 'Only Compliance Team can perform batch updates');

            return;
        }

        Deal::whereIn('id', $this->selectedDeals)->update([
            'user_id' => $this->batchOwnerValue,
        ]);

        $this->loadDeals();
        $this->resetBatchState();
        $this->dispatch('success', message: 'Owner updated for '.count($this->selectedDeals).' deal(s)');
    }

    public function executeBatchUpdateStage(): void
    {
        $user = $this->getCurrentUser();

        if (! $user || ! $this->isComplianceTeam()) {
            $this->dispatch('error', message: 'Only Compliance Team can perform batch updates');

            return;
        }

        Deal::whereIn('id', $this->selectedDeals)->update([
            'stage' => $this->batchStageValue,
        ]);

        $this->loadDeals();
        $this->resetBatchState();
        $this->dispatch('success', message: 'Stage updated for '.count($this->selectedDeals).' deal(s)');
    }

    public function executeBatchDelete(): void
    {
        $user = $this->getCurrentUser();

        if (! $user || ! $this->isComplianceTeam()) {
            $this->dispatch('error', message: 'Only Compliance Team can delete deals');

            return;
        }

        $count = count($this->selectedDeals);
        Deal::whereIn('id', $this->selectedDeals)->delete();

        $this->loadDeals();
        $this->resetBatchState();
        $this->dispatch('success', message: "{$count} deal(s) deleted successfully");
    }

    public function closeBatchModal(): void
    {
        $this->showBatchModal = false;
        $this->showConfirmModal = false;
    }

    public function confirmBatchAction(): void
    {
        match ($this->batchOperation) {
            'owner' => $this->executeBatchUpdateOwner(),
            'stage' => $this->executeBatchUpdateStage(),
            'delete' => $this->executeBatchDelete(),
        };

        $this->showConfirmModal = false;
    }

    public function setView($view): void
    {
        $this->view = $view;
        $this->currentPage = 1;
        $this->kanbanLoadedCount = 30;
        $this->persistState();
        $this->loadDeals();
        // Notify Alpine to clear cache and reload
        $this->dispatch('view-changed', view: $view);
    }

    public function toggleColumn(string $column): void
    {
        if (in_array($column, $this->visibleColumns)) {
            if (count($this->visibleColumns) > 1) {
                $this->visibleColumns = array_values(array_filter($this->visibleColumns, fn ($c) => $c !== $column));
            }
        } else {
            $this->visibleColumns[] = $column;
        }

        $this->persistState();
    }

    public function exportUrl(): string
    {
        return route(
            'deals.export',
            array_filter([
                'filterDealName' => $this->filterDealName ?: null,
                'filterOwner' => $this->filterOwner ?: null,
                'filterContact' => $this->filterContact ?: null,
                'filterCompanyName' => $this->filterCompanyName ?: null,
                'filterStage' => $this->filterStage ?: null,
                'minAmount' => $this->minAmount ?: null,
                'maxAmount' => $this->maxAmount ?: null,
                'dateFrom' => $this->dateFrom ?: null,
                'dateTo' => $this->dateTo ?: null,
            ]),
        );
    }

    public function getDealsByStage($stage)
    {
        return collect($this->deals)->where('stage', $stage)->values();
    }

    public function getStageSum($stage)
    {
        return collect($this->deals)->where('stage', $stage)->sum('amount');
    }
};

?>

<div class="space-y-6 w-full mx-auto p-4 sm:p-6 lg:p-8 antialiased text-slate-900 dark:text-slate-100">
    <div
        x-data="{
            draggingId: null,
            draggingStage: null,
            onDragStart(dealId, stage) {
                this.draggingId = dealId;
                this.draggingStage = stage;
            },
            onDrop(targetStage) {
                if (this.draggingId !== null && this.draggingStage !== targetStage) {
                    $wire.updateStage(this.draggingId, targetStage);
                }
                this.draggingId = null;
                this.draggingStage = null;
            },
            onDragOver(e) { e.preventDefault(); }
        }"
    >
        {{-- Loading bar --}}
        <div wire:loading.delay class="fixed top-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400 z-50 animate-pulse"></div>

        {{-- ── Header ── --}}
        <div class="w-full border-b border-slate-200 dark:border-slate-800 pb-5 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white tracking-tight">
                        {{ __('Deals Pipeline') }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Manage your pipeline tracking, stage workflows, and incoming financial volumes.
                    </p>
                </div>

                {{-- View toggle + Refresh + Export --}}
                <div class="flex items-center gap-2 shrink-0">
                    <div class="inline-flex rounded-lg shadow-sm bg-slate-100 dark:bg-slate-800 p-1 gap-0.5">
                        <button wire:click="setView('kanban')"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-medium rounded-md transition-all duration-150
                                {{ $view === 'kanban' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm ring-1 ring-slate-200/60 dark:ring-slate-600/40' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="currentColor">
                                <rect x="1" y="1" width="4" height="14" rx="1.5"/>
                                <rect x="6" y="1" width="4" height="14" rx="1.5"/>
                                <rect x="11" y="1" width="4" height="14" rx="1.5"/>
                            </svg>
                            Kanban
                        </button>
                        <button wire:click="setView('table')"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-medium rounded-md transition-all duration-150
                                {{ $view === 'table' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm ring-1 ring-slate-200/60 dark:ring-slate-600/40' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="currentColor">
                                <rect x="1" y="1" width="14" height="3" rx="1.5"/>
                                <rect x="1" y="6" width="14" height="3" rx="1.5"/>
                                <rect x="1" y="11" width="14" height="3" rx="1.5"/>
                            </svg>
                            Table
                        </button>
                    </div>
                    <button wire:click="refreshDeals" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition"
                        title="Refresh">
                        <svg wire:loading.remove wire:target="refreshDeals" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg wire:loading wire:target="refreshDeals" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                        </svg>
                    </button>
                    @include('components.deals.partials.⚡export', ['exportUrl' => $this->exportUrl()])
                </div>
            </div>
        </div>

        @include('components.deals.partials.⚡filters')

        {{-- ══════════════════════════════════════
             KANBAN BOARD VIEW
        ══════════════════════════════════════ --}}
        @if ($view === 'kanban')
            <div wire:key="kanban-board-{{ $kanbanLoadedCount }}-{{ $totalDeals }}">
                @php
                    $stageConfig = [
                        'doc sent' => ['accent' => '#4f46e5', 'accentLight' => 'rgba(79,70,229,0.12)', 'accentText' => '#3730a3', 'icon' => '📄', 'label' => 'Doc Sent'],
                        'doc signed' => ['accent' => '#0891b2', 'accentLight' => 'rgba(8,145,178,0.12)', 'accentText' => '#155e75', 'icon' => '✍️', 'label' => 'Doc Signed'],
                        'compliant' => ['accent' => '#4ed386', 'accentLight' => 'rgba(217,119,6,0.12)', 'accentText' => '#1b8b41', 'icon' => '✅', 'label' => 'Compliant'],
                        'ready for payment' => ['accent' => '#ea580c', 'accentLight' => 'rgba(234,88,12,0.12)', 'accentText' => '#9a3412', 'icon' => '💳', 'label' => 'Ready for Payment'],
                        'paid' => ['accent' => '#16a34a', 'accentLight' => 'rgba(22,163,74,0.12)', 'accentText' => '#14532d', 'icon' => '💰', 'label' => 'Paid'],
                    ];
                @endphp
                @include('components.deals.partials.⚡kanban', ['stageConfig' => $stageConfig])

                @if ($kanbanHasMore)
                    <div x-data x-intersect.threshold.10="$wire.loadMoreKanban()" class="h-10 flex items-center justify-center">
                        <svg class="w-5 h-5 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </div>
                @endif
            </div>
        @endif

        {{-- ══════════════════════════════════════
             TABLE LIST VIEW
        ══════════════════════════════════════ --}}
        @if ($view === 'table')
            @php
                $stageConfig = [
                    'doc sent' => ['accent' => '#4f46e5', 'accentLight' => 'rgba(79,70,229,0.12)', 'accentText' => '#3730a3', 'icon' => '📄', 'label' => 'Doc Sent'],
                    'doc signed' => ['accent' => '#0891b2', 'accentLight' => 'rgba(8,145,178,0.12)', 'accentText' => '#155e75', 'icon' => '✍️', 'label' => 'Doc Signed'],
                    'compliant' => ['accent' => '#4ed386', 'accentLight' => 'rgba(217,119,6,0.12)', 'accentText' => '#1b8b41', 'icon' => '✅', 'label' => 'Compliant'],
                    'ready for payment' => ['accent' => '#ea580c', 'accentLight' => 'rgba(234,88,12,0.12)', 'accentText' => '#9a3412', 'icon' => '💳', 'label' => 'Ready for Payment'],
                    'paid' => ['accent' => '#16a34a', 'accentLight' => 'rgba(22,163,74,0.12)', 'accentText' => '#14532d', 'icon' => '💰', 'label' => 'Paid'],
                ];
            @endphp
            <div wire:key="table-view-{{ $currentPage }}-{{ $totalDeals }}">
                @include('components.deals.partials.⚡table-view', ['stageConfig' => $stageConfig])
            </div>
        @endif

        {{-- BATCH ACTIONS FLOATING TOOLBAR --}}
        @if ($this->getSelectedCount() > 0)
            <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-xl z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                        {{ $this->getSelectedCount() }} deal(s) selected
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button type="button" @click="open = !open"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors">
                                Batch Operations
                                <svg class="w-4 h-4 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute right-0 bottom-full mb-2 w-48 rounded-lg shadow-lg bg-white dark:bg-slate-800 ring-1 ring-black ring-opacity-5 divide-y divide-slate-100 dark:divide-slate-700 z-50">
                                <div class="py-1">
                                    <button wire:click="openBatchModal('owner')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">👤 Update Owner</button>
                                    <button wire:click="openBatchModal('stage')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">📊 Update Stage</button>
                                </div>
                                <div class="py-1">
                                    <button wire:click="openBatchModal('delete')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">🗑️ Delete Records</button>
                                </div>
                            </div>
                        </div>
                        <button wire:click="resetBatchState" class="px-4 py-2.5 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white text-sm font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">Clear Selection</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Batch Modal --}}
        @if ($showBatchModal)
            <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closeBatchModal">
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">
                        @if ($batchOperation === 'owner') Update Deal Owner @endif
                        @if ($batchOperation === 'stage') Update Deal Stage @endif
                        @if ($batchOperation === 'delete') Delete Deals @endif
                    </h3>

                    @if ($batchOperation === 'owner')
                        <select wire:model="batchOwnerValue" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">
                            <option value="">Choose an owner...</option>
                            @foreach ($allUsers as $u)
                                <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                            @endforeach
                        </select>
                    @elseif ($batchOperation === 'stage')
                        <select wire:model="batchStageValue" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white">
                            <option value="">Choose a stage...</option>
                            @php
                                $stageLabels = [
                                    'doc sent' => 'Doc Sent', 'doc signed' => 'Doc Signed',
                                    'compliant' => 'Compliant', 'ready for payment' => 'Ready for Payment',
                                    'paid' => 'Paid',
                                ];
                            @endphp
                            @foreach ($stages as $s)
                                <option value="{{ $s }}">{{ $stageLabels[$s] ?? ucwords($s) }}</option>
                            @endforeach
                        </select>
                    @elseif ($batchOperation === 'delete')
                        <p class="text-sm text-red-600 dark:text-red-400">⚠️ This action cannot be undone.</p>
                    @endif

                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="closeBatchModal" class="px-4 py-2 rounded-lg border border-slate-300 text-sm">Cancel</button>
                        <button wire:click="@if ($batchOperation === 'owner') confirmBatchUpdateOwner @elseif ($batchOperation === 'stage') confirmBatchUpdateStage @else confirmBatchDelete @endif" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm">Continue</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Confirm Modal --}}
        @if ($showConfirmModal)
            <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closeBatchModal">
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold mb-2">Confirm Action</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">{{ $confirmMessage }}</p>
                    <div class="flex justify-end gap-3">
                        @if ($selectAll && ! $batchOperation)
                            <button wire:click="cancelSelectAll" class="px-4 py-2 text-sm border rounded-lg">Page Only</button>
                            <button wire:click="confirmSelectAll" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg">Select System-wide</button>
                        @else
                            <button wire:click="closeBatchModal" class="px-4 py-2 text-sm border rounded-lg">Cancel</button>
                            <button wire:click="confirmBatchAction" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg">Confirm</button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
