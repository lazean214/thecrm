<?php

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    // ── View & Pagination ────────────────────────────────
    public string $view = 'kanban';
    public int $perPage = 25;
    public int $currentPage = 1;
    public int $totalDeals = 0;
    public int $totalPages = 1;
    public int $paginationFrom = 0;
    public int $paginationTo = 0;

    // ── Kanban State ───────────────────────────────────
    /** @var array<string, array{deals: array, count: int, total_amount: float}> */
    public array $kanbanData = [];
    public bool $kanbanLoading = false;
    public ?string $kanbanETag = null;
    public int $kanbanPerStage = 50; // Limit deals shown per stage
    public array $kanbanExpandedStages = []; // Track which stages are expanded

    // ── Loading States ─────────────────────────────────
    public bool $tableLoading = false;
    public bool $filtering = false;

    // ── Cursor Pagination ────────────────────────────────
    public ?string $cursor = null;
    public ?string $previousCursor = null;
    public ?string $nextCursor = null;
    public bool $hasMorePages = false;

    // ── Filters ─────────────────────────────────────────
    public string $filterDealName = '';
    public string $filterOwner = '';
    public string $filterContact = '';
    public string $filterCompanyName = '';
    public string $filterStage = '';
    public ?float $minAmount = null;
    public ?float $maxAmount = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public bool $isDefaultDateRange = false;

    // ── Column Visibility ───────────────────────────────
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

    /** @var array<string> */
    public array $stages = [];

    // ── Batch Operations ────────────────────────────────
    public array $selectedDeals = [];
    public bool $selectAll = false;
    public string $batchOperation = '';
    public string $batchOwnerValue = '';
    public string $batchStageValue = '';
    public bool $showBatchModal = false;
    public bool $showConfirmModal = false;
    public string $confirmMessage = '';

    // ── Deferred Lookups ────────────────────────────────
    public array $allUsers = [];
    public array $allCompanies = [];
    public array $allContacts = [];
    public bool $lookupsLoaded = false;

    // ─────────────────────────────────────────────────────
    // COMPUTED: Table Deals (paginated)
    // ─────────────────────────────────────────────────────

    #[Computed(persist: false)]
    public function dealsForTable(): array
    {
        $this->tableLoading = false;

        // Use paginate for total count
        $query = $this->buildTableQuery();
        $paginated = $query
            ->latest('updated_at')
            ->paginate($this->perPage);

        $this->totalDeals = $paginated->total();
        $this->totalPages = max(1, (int) ceil($this->totalDeals / $this->perPage));
        $this->currentPage = $paginated->currentPage();
        $this->paginationFrom = $paginated->firstItem() ?? 0;
        $this->paginationTo = $paginated->lastItem() ?? 0;

        $this->hasMorePages = $paginated->hasMorePages();

        return collect($paginated->items())->map(fn ($d) => $this->serializeDealFull($d))->all();
    }

    // ─────────────────────────────────────────────────────
    // LIFECYCLE
    // ─────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->stages = array_map(fn ($s) => $s->value, DealStage::cases());

        // Load lookups eagerly so they're available for Alpine autocomplete
        $this->allUsers = User::orderBy('name')->get(['id', 'name', 'email'])->toArray();
        $this->allCompanies = Company::orderBy('name')->get(['id', 'name'])->toArray();
        $this->allContacts = Contact::orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim($c->first_name . ' ' . $c->last_name),
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
            ])
            ->toArray();
        $this->lookupsLoaded = true;

        // Restore session state
        $state = Session::get('deals_view_state', []);

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
            $this->isDefaultDateRange = true;
        }

        // Stale-while-revalidate: use cache if fresh, fetch fresh in background
        $this->loadWithStaleCache();
    }

    public function loadWithStaleCache(): void
    {
        $cacheKey = $this->kanbanCacheKey();
        $cached = Cache::get($cacheKey);

        // Use cached data if available (max 30 seconds stale)
        if ($cached && isset($cached['stages']) && ! $this->hasActiveFilters()) {
            $this->kanbanData = $cached['stages'];
            $staleAge = isset($cached['cached_at']) ? now()->timestamp - $cached['cached_at'] : 999;

            // Refresh in background if stale (only for kanban view, no filters)
            if ($staleAge > 30 && $this->view === 'kanban') {
                $this->dispatch('backgroundRefreshKanban');
            }

            return;
        }

        // No cache or has filters - fetch fresh
        $this->refreshKanbanData();
    }

    // ─────────────────────────────────────────────────────
    // KANBAN METHODS
    // ─────────────────────────────────────────────────────

    #[On('loadLookups')]
    public function loadLookups(): void
    {
        if (! $this->lookupsLoaded) {
            $this->allUsers = User::orderBy('name')->get(['id', 'name', 'email'])->toArray();
            $this->allCompanies = Company::orderBy('name')->get(['id', 'name'])->toArray();
            $this->allContacts = Contact::orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => trim($c->first_name . ' ' . $c->last_name),
                    'first_name' => $c->first_name,
                    'last_name' => $c->last_name,
                ])
                ->toArray();
            $this->lookupsLoaded = true;

            // Dispatch event so Alpine components can update their items
            $this->dispatch('lookupsLoaded');
        }
    }

    #[On('loadKanban')]
    public function loadKanbanData(): void
    {
        // Try cache first
        $cacheKey = $this->kanbanCacheKey();
        $cached = Cache::get($cacheKey);

        if ($cached && ! $this->hasActiveFilters()) {
            $this->kanbanData = $cached['stages'] ?? [];
            $this->kanbanETag = md5(json_encode($cached));

            // Background refresh if stale
            if (isset($cached['cached_at']) && $cached['cached_at'] < now()->subSeconds(30)->timestamp) {
                $this->dispatch('backgroundRefreshKanban');
            }

            return;
        }

        // Fetch fresh data
        $data = $this->fetchKanbanData();
        $this->kanbanData = $data['stages'] ?? [];

        // Cache it
        Cache::put($cacheKey, $data, now()->addMinutes(2));
    }

    #[On('backgroundRefreshKanban')]
    public function refreshKanbanInBackground(): void
    {
        $data = $this->fetchKanbanData();
        $this->kanbanData = $data['stages'] ?? [];
        Cache::put($this->kanbanCacheKey(), $data, now()->addMinutes(2));
    }

    public function refreshKanbanData(): void
    {
        // Force refresh by bypassing cache
        $data = $this->fetchKanbanData();
        $this->kanbanData = $data['stages'] ?? [];
        Cache::put($this->kanbanCacheKey(), $data, now()->addMinutes(2));
    }

    #[On('echo:deals,DealCreated')]
    public function handleNewDealBroadcast(array $data): void
    {
        $user = $this->getCurrentUser();
        if (! $user || ($data['target_user_id'] ?? null) !== $user->id) {
            return;
        }

        // Optimistically add to kanban (will be synced on next full refresh)
        $newDeal = $data['deal'] ?? $data;

        foreach ($this->kanbanData as $stage => &$stageData) {
            if ($stage === $newDeal['stage']) {
                $stageData['deals'] = array_values(array_filter($stageData['deals'], fn ($d) => $d['id'] !== $newDeal['id']));
                array_unshift($stageData['deals'], $newDeal);
                $stageData['count']++;
                $stageData['total_amount'] += (float) ($newDeal['amount'] ?? 0);
                break;
            }
        }
        unset($stageData);
    }

    // ─────────────────────────────────────────────────────
    // TABLE METHODS
    // ─────────────────────────────────────────────────────

    #[On('refreshTable')]
    public function refreshTableData(): void
    {
        $this->currentPage = 1;
        $this->cursor = null;
        unset($this->dealsForTable);
    }

    public function loadDeals(): void
    {
        if ($this->view === 'kanban') {
            $this->loadKanbanData();
        } else {
            $this->currentPage = 1;
            $this->cursor = null;
            unset($this->dealsForTable);
        }

        $this->persistState();
    }

    // ─────────────────────────────────────────────────────
    // FILTER HANDLERS
    // ─────────────────────────────────────────────────────

    private function onFilterChanged(): void
    {
        $this->currentPage = 1;
        $this->cursor = null;
        unset($this->dealsForTable);
        $this->resetBatchState();
        $this->persistState();

        // Refresh kanban data when filters change
        if ($this->view === 'kanban') {
            $this->refreshKanbanData();
        }
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

    public function applyFilters(): void
    {
        $this->filtering = true;
        $this->onFilterChanged();
        $this->filtering = false;
    }

    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
        $this->cursor = null;
        unset($this->dealsForTable);
        $this->persistState();
    }

    public function updatedCursor(): void
    {
        unset($this->dealsForTable);
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterDealName', 'filterOwner', 'filterContact',
            'filterCompanyName', 'filterStage', 'minAmount', 'maxAmount', 'dateTo',
        ]);
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->isDefaultDateRange = true;
        $this->onFilterChanged();
    }

    // ─────────────────────────────────────────────────────
    // VIEW SWITCHING
    // ─────────────────────────────────────────────────────

    public function setView(string $view): void
    {
        if ($this->view === $view) {
            return;
        }

        // Cache current view data before switching
        if ($this->view === 'table') {
            $this->cacheTableData();
        } elseif ($this->view === 'kanban') {
            $this->cacheKanbanData();
        }

        $this->view = $view;
        $this->tableLoading = $view === 'table';
        $this->persistState();

        // Load data for the new view
        $this->loadDeals();

        $this->dispatch('view-changed', view: $view);
    }

    private function cacheTableData(): void
    {
        $cacheKey = 'table_data_' . auth()->id() . '_' . md5(json_encode([
            $this->filterDealName, $this->filterOwner, $this->filterContact,
            $this->filterCompanyName, $this->filterStage, $this->minAmount,
            $this->maxAmount, $this->dateFrom, $this->dateTo, $this->perPage, $this->currentPage
        ]));
        Cache::put($cacheKey, $this->dealsForTable, now()->addMinutes(5));
    }

    private function cacheKanbanData(): void
    {
        Cache::put($this->kanbanCacheKey(), [
            'stages' => $this->kanbanData,
            'cached_at' => now()->timestamp,
        ], now()->addMinutes(5));
    }

    // ─────────────────────────────────────────────────────
    // STAGE UPDATE (DRAG & DROP)
    // ─────────────────────────────────────────────────────

    public function updateStage(int $dealId, string $newStage): void
    {
        $user = $this->getCurrentUser();

        if (! $user) {
            $this->dispatch('error', message: 'Unauthorized');

            return;
        }

        $deal = Deal::findOrFail($dealId);
        $oldStage = $deal->stage->value;

        // Authorization
        if ($user->isSalesTeam() && $deal->user_id !== $user->id) {
            $this->dispatch('error', message: 'You can only edit your own deals');

            return;
        }

        if (! $user->canMoveToStage($newStage)) {
            $allowed = implode(', ', $user->getAllowedDealStages());
            $this->dispatch('error', message: "You can only move to: {$allowed}");

            return;
        }

        // Save
        $deal->update(['stage' => DealStage::from($newStage)]);
        $deal->logStageChange($oldStage, $newStage, $user->isSalesTeam() ? 'Sales Team action' : 'Compliance Team action');

        // Optimistically update kanban state
        $this->optimisticallyMoveDeal($dealId, $oldStage, $newStage, (float) $deal->amount);

        // Invalidate cache
        $this->invalidateKanbanCache();

        $this->dispatch('deals-updated');
        $this->dispatch('success', message: 'Deal moved successfully');
    }

    private function optimisticallyMoveDeal(int $dealId, string $fromStage, string $toStage, float $amount): void
    {
        if ($this->view !== 'kanban') {
            return;
        }

        // Find and remove from old stage
        $deal = null;
        foreach ($this->kanbanData[$fromStage]['deals'] as $d) {
            if ($d['id'] === $dealId) {
                $deal = $d;
                break;
            }
        }

        if (! $deal) {
            return;
        }

        $this->kanbanData[$fromStage]['deals'] = array_values(
            array_filter($this->kanbanData[$fromStage]['deals'], fn ($d) => $d['id'] !== $dealId)
        );
        $this->kanbanData[$fromStage]['count']--;
        $this->kanbanData[$fromStage]['total_amount'] -= $amount;

        // Add to new stage
        $deal['stage'] = $toStage;
        array_unshift($this->kanbanData[$toStage]['deals'], $deal);
        $this->kanbanData[$toStage]['count']++;
        $this->kanbanData[$toStage]['total_amount'] += $amount;
    }

    private function invalidateKanbanCache(): void
    {
        unset($this->kanbanCached);
    }

    // ─────────────────────────────────────────────────────
    // PAGINATION
    // ─────────────────────────────────────────────────────

    public function nextPage(): void
    {
        if (! $this->hasMorePages) {
            return;
        }

        $this->tableLoading = true;
        $this->currentPage++;
        unset($this->dealsForTable);
    }

    public function previousPage(): void
    {
        if ($this->currentPage <= 1) {
            return;
        }

        $this->tableLoading = true;
        $this->currentPage--;
        unset($this->dealsForTable);
    }

    public function goToPage(int $page): void
    {
        $this->tableLoading = true;
        $this->currentPage = max(1, min($page, $this->totalPages));
        unset($this->dealsForTable);
    }

    // ─────────────────────────────────────────────────────
    // BATCH OPERATIONS
    // ─────────────────────────────────────────────────────

    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;

        if ($this->selectAll) {
            if ($this->hasActiveFilters()) {
                $this->showConfirmModal = true;
                $this->confirmMessage = 'Select all deals from filtered results?';
            } else {
                $deals = $this->view === 'kanban' ? $this->getAllKanbanDealIds() : $this->getAllTableDealIds();
                $this->selectedDeals = $deals;
            }
        } else {
            $this->selectedDeals = [];
        }
    }

    private function getAllKanbanDealIds(): array
    {
        $ids = [];
        foreach ($this->kanbanData as $stageData) {
            foreach ($stageData['deals'] as $deal) {
                $ids[] = $deal['id'];
            }
        }

        return $ids;
    }

    private function getAllTableDealIds(): array
    {
        return array_map(fn ($d) => $d['id'], $this->dealsForTable);
    }

    public function confirmSelectAll(): void
    {
        // For select-all across all pages, we'd need to track this differently
        $this->selectedDeals = $this->getAllKanbanDealIds();
        $this->showConfirmModal = false;
    }

    public function cancelSelectAll(): void
    {
        $this->selectAll = false;
        $this->showConfirmModal = false;
    }

    public function toggleDealSelection(int $dealId): void
    {
        if (in_array($dealId, $this->selectedDeals)) {
            $this->selectedDeals = array_filter($this->selectedDeals, fn ($id) => $id !== $dealId);
            $this->selectAll = false;
        } else {
            $this->selectedDeals[] = $dealId;
        }
    }

    public function getSelectedCount(): int
    {
        return count($this->selectedDeals);
    }

    public function resetBatchState(): void
    {
        $this->selectedDeals = [];
        $this->selectAll = false;
        $this->batchOperation = '';
        $this->batchOwnerValue = '';
        $this->batchStageValue = '';
        $this->showBatchModal = false;
        $this->showConfirmModal = false;
    }

    public function openBatchModal(string $operation): void
    {
        if (empty($this->selectedDeals)) {
            $this->dispatch('error', message: 'Select at least one deal');

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
            $this->dispatch('error', message: 'Select an owner');

            return;
        }

        $this->confirmMessage = 'Update owner for ' . count($this->selectedDeals) . ' deal(s)?';
        $this->showBatchModal = false;
        $this->showConfirmModal = true;
    }

    public function confirmBatchUpdateStage(): void
    {
        if (empty($this->batchStageValue)) {
            $this->dispatch('error', message: 'Select a stage');

            return;
        }

        $this->confirmMessage = 'Update stage for ' . count($this->selectedDeals) . ' deal(s)?';
        $this->showBatchModal = false;
        $this->showConfirmModal = true;
    }

    public function confirmBatchDelete(): void
    {
        $this->confirmMessage = 'Delete ' . count($this->selectedDeals) . ' deal(s)? Cannot be undone.';
        $this->showBatchModal = false;
        $this->showConfirmModal = true;
    }

    public function executeBatchUpdateOwner(): void
    {
        $user = $this->getCurrentUser();

        if (! $user || ! $user->isComplianceTeam()) {
            $this->dispatch('error', message: 'Only Compliance Team can batch update');

            return;
        }

        Deal::whereIn('id', $this->selectedDeals)->update(['user_id' => $this->batchOwnerValue]);

        $this->loadDeals();
        $this->resetBatchState();
        $this->dispatch('success', message: 'Owner updated');
    }

    public function executeBatchUpdateStage(): void
    {
        $user = $this->getCurrentUser();

        if (! $user || ! $user->isComplianceTeam()) {
            $this->dispatch('error', message: 'Only Compliance Team can batch update');

            return;
        }

        Deal::whereIn('id', $this->selectedDeals)->update(['stage' => $this->batchStageValue]);

        $this->loadDeals();
        $this->resetBatchState();
        $this->dispatch('success', message: 'Stage updated');
    }

    public function executeBatchDelete(): void
    {
        $user = $this->getCurrentUser();

        if (! $user || ! $user->isComplianceTeam()) {
            $this->dispatch('error', message: 'Only Compliance Team can delete');

            return;
        }

        Deal::whereIn('id', $this->selectedDeals)->delete();

        $this->loadDeals();
        $this->resetBatchState();
        $this->dispatch('success', message: count($this->selectedDeals) . ' deal(s) deleted');
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

    // ─────────────────────────────────────────────────────
    // COLUMN VISIBILITY
    // ─────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────
    // EXPORT
    // ─────────────────────────────────────────────────────

    public function exportUrl(): string
    {
        return route('deals.export', array_filter([
            'filterDealName' => $this->filterDealName ?: null,
            'filterOwner' => $this->filterOwner ?: null,
            'filterContact' => $this->filterContact ?: null,
            'filterCompanyName' => $this->filterCompanyName ?: null,
            'filterStage' => $this->filterStage ?: null,
            'minAmount' => $this->minAmount ?: null,
            'maxAmount' => $this->maxAmount ?: null,
            'dateFrom' => $this->dateFrom ?: null,
            'dateTo' => $this->dateTo ?: null,
        ]));
    }

    // ─────────────────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────────────────

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

    public function getEditableStages(): array
    {
        $user = $this->getCurrentUser();

        return $user ? $user->getAllowedDealStages() : [];
    }

    public function getAllowedStagesForUser(): array
    {
        return $this->getEditableStages();
    }

    public function canEditDealStage(): bool
    {
        return count($this->getEditableStages()) > 0;
    }

    public function canEditStage(string $stage): bool
    {
        $user = $this->getCurrentUser();

        return $user && $user->canMoveToStage($stage);
    }

    public function hasActiveFilters(): bool
    {
        return ! empty($this->filterDealName)
            || ! empty($this->filterOwner)
            || ! empty($this->filterContact)
            || ! empty($this->filterCompanyName)
            || ! empty($this->filterStage)
            || ($this->minAmount !== null && $this->minAmount !== '')
            || ($this->maxAmount !== null && $this->maxAmount !== '')
            || (! $this->isDefaultDateRange && ! empty($this->dateFrom))
            || ! empty($this->dateTo);
    }

    public function showAllTime(): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->isDefaultDateRange = false;
        $this->currentPage = 1;
        $this->cursor = null;
        unset($this->dealsForTable);
        $this->persistState();
        $this->resetBatchState();
    }

    public function refreshDeals(): void
    {
        unset($this->dealsForTable);
    }

    public function getDealsByStage(string $stage): array
    {
        return $this->kanbanData[$stage]['deals'] ?? [];
    }

    public function getStageSum(string $stage): float
    {
        return $this->kanbanData[$stage]['total_amount'] ?? 0;
    }

    // ─────────────────────────────────────────────────────
    // PRIVATE: BUILD QUERIES
    // ─────────────────────────────────────────────────────

    private function buildKanbanQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->buildBaseQuery()
            ->select(['id', 'name', 'amount', 'stage', 'user_id', 'created_at', 'updated_at'])
            ->with([
                'contacts:id,first_name,last_name',
                'companies:id,name',
                'user:id,name',
            ])
            ->orderByDesc('updated_at'); // Most recently updated first
    }

    private function buildTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->buildBaseQuery()
            ->select([
                'id', 'name', 'amount', 'stage', 'user_id',
                'recruitment_agency', 'consultant_name', 'agency_deal_value', 'margin_agreed',
                'date_sent', 'date_signed', 'who_signed', 'right_to_work',
                'mda_reference_number', 'date_set_up', 'tax_code',
                'created_at', 'updated_at',
            ])
            ->with([
                'contacts:id,first_name,last_name',
                'companies:id,name,email,phone,domain',
                'user:id,name,email',
            ]);
    }

    private function buildBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Deal::query();
        $user = $this->getCurrentUser();

        // Sales team: own deals only
        if ($user?->isSalesTeam()) {
            $query->where('user_id', $user->id);
        }

        // Filters
        if (! empty($this->filterDealName)) {
            $query->where('name', 'like', '%' . $this->filterDealName . '%');
        }

        if (! empty($this->filterStage)) {
            $query->where('stage', $this->filterStage);
        }

        if (! is_null($this->minAmount) && $this->minAmount !== '') {
            $query->where('amount', '>=', $this->minAmount);
        }

        if (! is_null($this->maxAmount) && $this->maxAmount !== '') {
            $query->where('amount', '<=', $this->maxAmount);
        }

        if (! empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if (! empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if (! empty($this->filterOwner)) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $this->filterOwner . '%'));
        }

        if (! empty($this->filterCompanyName)) {
            $query->whereHas('companies', fn ($q) => $q->where('name', 'like', '%' . $this->filterCompanyName . '%'));
        }

        if (! empty($this->filterContact)) {
            $query->whereHas('contacts', fn ($q) => $q->where(function ($sub) {
                $sub->where('first_name', 'like', '%' . $this->filterContact . '%')
                    ->orWhere('last_name', 'like', '%' . $this->filterContact . '%');
            }));
        }

        return $query;
    }

    // ─────────────────────────────────────────────────────
    // PRIVATE: SERIALIZERS
    // ─────────────────────────────────────────────────────

    private function serializeDealMinimal(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'amount' => (float) ($deal->amount ?? 0),
            'stage' => $deal->stage->value,
            'created_at' => $deal->created_at?->toIso8601String(),
            'user' => $deal->relationLoaded('user') ? [
                'id' => $deal->user->id,
                'name' => $deal->user->name,
            ] : null,
            'contacts' => $deal->relationLoaded('contacts')
                ? $deal->contacts->take(1)->map(fn ($c) => [
                    'id' => $c->id,
                    'first_name' => $c->first_name,
                    'last_name' => $c->last_name,
                ])->all()
                : [],
            'companies' => $deal->relationLoaded('companies')
                ? $deal->companies->take(1)->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])->all()
                : [],
        ];
    }

    private function serializeDealFull(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'amount' => (float) ($deal->amount ?? 0),
            'stage' => $deal->stage instanceof \BackedEnum ? $deal->stage->value : (string) $deal->stage,
            'user_id' => $deal->user_id,
            'recruitment_agency' => $deal->recruitment_agency,
            'consultant_name' => $deal->consultant_name,
            'agency_deal_value' => $deal->agency_deal_value,
            'margin_agreed' => $deal->margin_agreed,
            'date_sent' => $deal->date_sent,
            'date_signed' => $deal->date_signed,
            'who_signed' => $deal->who_signed,
            'right_to_work' => $deal->right_to_work,
            'mda_reference_number' => $deal->mda_reference_number,
            'date_set_up' => $deal->date_set_up,
            'tax_code' => $deal->tax_code,
            'created_at' => $deal->created_at?->toIso8601String(),
            'updated_at' => $deal->updated_at?->toIso8601String(),
            'user' => $deal->relationLoaded('user') ? [
                'id' => $deal->user->id,
                'name' => $deal->user->name,
                'email' => $deal->user->email,
            ] : null,
            'contacts' => $deal->relationLoaded('contacts')
                ? $deal->contacts->map(fn ($c) => [
                    'id' => $c->id,
                    'first_name' => $c->first_name,
                    'last_name' => $c->last_name,
                ])->all()
                : [],
            'companies' => $deal->relationLoaded('companies')
                ? $deal->companies->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'domain' => $c->domain,
                ])->all()
                : [],
        ];
    }

    // ─────────────────────────────────────────────────────
    // PRIVATE: SESSION & CACHE
    // ─────────────────────────────────────────────────────

    private function kanbanCacheKey(): string
    {
        $parts = [
            auth()->id(),
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

        return 'kanban_' . md5(serialize($parts));
    }

    private function fetchKanbanData(): array
    {
        $query = $this->buildKanbanQuery();
        $deals = $query->get();

        // Group by stage - limit initial load per stage
        $stages = [];
        foreach (DealStage::cases() as $stage) {
            $stageDeals = $deals->where('stage', $stage->value)->values();
            $totalCount = $stageDeals->count();
            $visibleDeals = $stageDeals->take($this->kanbanPerStage);
            $stages[$stage->value] = [
                'deals' => $visibleDeals->map(fn ($d) => $this->serializeDealMinimal($d))->all(),
                'count' => $totalCount,
                'total_amount' => (float) $stageDeals->sum('amount'),
                'has_more' => $totalCount > $this->kanbanPerStage,
                'offset' => $this->kanbanPerStage,
            ];
        }

        return [
            'stages' => $stages,
            'total_deals' => $deals->count(),
            'total_amount' => (float) $deals->sum('amount'),
            'cached_at' => now()->timestamp,
        ];
    }

    public function loadMoreInStage(string $stage): void
    {
        $offset = $this->kanbanExpandedStages[$stage] ?? $this->kanbanPerStage;
        $newLimit = $offset + $this->kanbanPerStage;

        // Fetch fresh to get accurate slice
        $query = $this->buildKanbanQuery()
            ->where('stage', $stage);
        $allStageDeals = $query->get();
        $totalCount = $allStageDeals->count();
        $visibleDeals = $allStageDeals->take($newLimit);

        $this->kanbanData[$stage] = [
            'deals' => $visibleDeals->map(fn ($d) => $this->serializeDealMinimal($d))->all(),
            'count' => $totalCount,
            'total_amount' => (float) $allStageDeals->sum('amount'),
            'has_more' => $totalCount > $newLimit,
            'offset' => $newLimit,
        ];

        $this->kanbanExpandedStages[$stage] = $newLimit;
    }

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

    private function persistState(): void
    {
        Session::put('deals_view_state', [
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
        ]);
    }
};

?>

<div class="space-y-6 w-full mx-auto p-4 sm:p-6 lg:p-8 antialiased text-slate-900 dark:text-slate-100"
    x-data="{
        view: @entangle('view').defer,
    }">

    {{-- Loading indicator --}}
    <div wire:loading.delay class="fixed top-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400 z-50 animate-pulse"></div>

    {{-- Filter loading overlay --}}
    @if($filtering)
        <div class="fixed inset-0 bg-white/30 dark:bg-slate-900/30 z-40 flex items-center justify-center pointer-events-none">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg px-6 py-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                </svg>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Applying filters...</span>
            </div>
        </div>
    @endif

    {{-- Header --}}
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

            <div class="flex items-center gap-2 shrink-0">
                {{-- View toggle --}}
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

    {{-- Kanban View --}}
    @if ($view === 'kanban')
        <div wire:key="kanban-board" wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity duration-200">
            @php
                $stageConfig = [
                    'doc sent' => ['accent' => '#4f46e5', 'accentLight' => 'rgba(79,70,229,0.12)', 'accentText' => '#3730a3', 'icon' => '📄', 'label' => 'Doc Sent'],
                    'doc signed' => ['accent' => '#0891b2', 'accentLight' => 'rgba(8,145,178,0.12)', 'accentText' => '#155e75', 'icon' => '✍️', 'label' => 'Doc Signed'],
                    'compliant' => ['accent' => '#4ed386', 'accentLight' => 'rgba(217,119,6,0.12)', 'accentText' => '#1b8b41', 'icon' => '✅', 'label' => 'Compliant'],
                    'ready for payment' => ['accent' => '#ea580c', 'accentLight' => 'rgba(234,88,12,0.12)', 'accentText' => '#9a3412', 'icon' => '💳', 'label' => 'Ready for Payment'],
                    'paid' => ['accent' => '#16a34a', 'accentLight' => 'rgba(22,163,74,0.12)', 'accentText' => '#14532d', 'icon' => '💰', 'label' => 'Paid'],
                ];
            @endphp
            @include('components.deals.partials.⚡kanban', [
                'stageConfig' => $stageConfig,
                'kanbanData' => $this->kanbanData,
                'isSalesUser' => $this->isSalesTeam(),
                'editableStages' => $this->getEditableStages(),
            ])
        </div>
    @endif

    {{-- Table View --}}
    @if ($view === 'table')
        <div wire:key="table-view-{{ $currentPage }}" wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity duration-200">
            @php
                $stageConfig = [
                    'doc sent' => ['accent' => '#4f46e5', 'accentLight' => 'rgba(79,70,229,0.12)', 'accentText' => '#3730a3', 'icon' => '📄', 'label' => 'Doc Sent'],
                    'doc signed' => ['accent' => '#0891b2', 'accentLight' => 'rgba(8,145,178,0.12)', 'accentText' => '#155e75', 'icon' => '✍️', 'label' => 'Doc Signed'],
                    'compliant' => ['accent' => '#4ed386', 'accentLight' => 'rgba(217,119,6,0.12)', 'accentText' => '#1b8b41', 'icon' => '✅', 'label' => 'Compliant'],
                    'ready for payment' => ['accent' => '#ea580c', 'accentLight' => 'rgba(234,88,12,0.12)', 'accentText' => '#9a3412', 'icon' => '💳', 'label' => 'Ready for Payment'],
                    'paid' => ['accent' => '#16a34a', 'accentLight' => 'rgba(22,163,74,0.12)', 'accentText' => '#14532d', 'icon' => '💰', 'label' => 'Paid'],
                ];
            @endphp
            @include('components.deals.partials.⚡table-view', [
                'stageConfig' => $stageConfig,
                'deals' => $this->dealsForTable,
                'tableLoading' => $this->tableLoading,
            ])
        </div>
    @endif

    {{-- Batch Actions Toolbar --}}
    @include('components.deals.partials.⚡batch-toolbar')

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
                        <button wire:click="confirmSelectAll" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg">Select All</button>
                    @else
                        <button wire:click="closeBatchModal" class="px-4 py-2 text-sm border rounded-lg">Cancel</button>
                        <button wire:click="confirmBatchAction" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg">Confirm</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

