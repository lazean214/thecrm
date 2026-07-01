{{-- components/deals/partials/⚡kanban.blade.php --}}

@php
    // These are passed from the parent Livewire component
    $isSalesUser = $isSalesUser ?? false;
    $editableStages = $editableStages ?? [];
@endphp

<div x-data="kanbanBoard({
    kanbanData: {{ Js::from($kanbanData) }},
    stages: {{ Js::from($stages) }},
    stageConfig: {{ Js::from($stageConfig) }},
    isSalesUser: {{ $isSalesUser ? 'true' : 'false' }},
    editableStages: {{ Js::from($editableStages) }},
})" x-init="init()"
    class="flex gap-3 overflow-x-auto overflow-y-hidden pb-4 px-2 min-h-[650px] snap-x snap-mandatory scrollbar-thin">

    <template x-for="stage in stages" :key="stage">
        <div :data-stage="stage"
            class="
                flex flex-col shrink-0
                rounded-2xl border-2
                transition-all duration-200
                snap-start

                w-[280px]
                md:w-[320px]
                lg:w-[340px]
                xl:w-[360px]

                h-[calc(100vh-210px)]
                min-h-[620px]
                max-h-[900px]
            "
            :class="{
                'ring-2 ring-indigo-400 dark:ring-indigo-500 ring-offset-1 border-indigo-300 dark:border-indigo-600': dragOverStage === stage,
                'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30': dragOverStage !== stage
            }"
            @dragover.prevent="canEditStage(stage) && onDragOver(stage)" @dragleave="onDragLeave()" @dragenter.prevent
            @drop.prevent="onDrop(stage)">

            {{-- Header --}}
            <div class="px-4 py-3 rounded-t-2xl font-semibold flex items-center justify-between sticky top-0 z-10 shadow-sm"
                :style="'background-color:' + (stageConfig[stage]?.accent ?? '#6b7280')">
                <div class="flex items-center gap-2 text-white min-w-0">
                    <span class="text-base shrink-0" x-text="stageConfig[stage]?.icon ?? '•'"></span>
                    <span class="text-sm font-semibold truncate" x-text="stageConfig[stage]?.label ?? stage"></span>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="text-white/80 text-xs tabular-nums font-normal bg-black/20 rounded-full px-2 py-0.5"
                        x-text="getStageCount(stage)"></span>
                    <template x-if="isSalesUser && !canEditStage(stage)">
                        <span class="text-[10px] bg-white/20 text-white px-2 py-0.5 rounded-full">🔒</span>
                    </template>
                </div>
            </div>

            {{-- Total TSV --}}
            <div class="px-4 py-2 text-xs font-semibold border-b border-slate-200 dark:border-slate-700 bg-white/70 dark:bg-slate-800/40 backdrop-blur-sm sticky top-[60px] z-10">
                <span :style="'color:' + (stageConfig[stage]?.accentText ?? 'inherit')"
                    x-text="'£' + getStageSum(stage).toLocaleString('en-GB', {maximumFractionDigits:0}) + ' total TSV'"></span>
            </div>

            {{-- Cards --}}
            <div :id="'kanban-col-' + stage" class="flex-1 overflow-y-auto p-2 md:p-3 space-y-2">
                <template x-if="getDealsByStage(stage).length === 0">
                    <div class="flex flex-col items-center justify-center h-full text-slate-400 dark:text-slate-600 select-none">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-xs">No deals</p>
                        <template x-if="canEditStage(stage)">
                            <p class="text-[10px] mt-1 opacity-60">Drop here to move</p>
                        </template>
                    </div>
                </template>

                 <template x-for="deal in getDealsByStage(stage)" :key="deal.id">
                     <div :id="'kanban-card-' + deal.id" class="transition-transform duration-200 relative"
                         :draggable="canEditStage(stage)"
                         @dragstart="onDragStart(deal.id, stage, $event)" @dragend="onDragEnd()" @drag="onDrag($event)">

                        {{-- Card --}}
                        <div class="relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                                   hover:border-slate-300 dark:hover:border-slate-600 rounded-xl p-3.5
                                   flex items-start gap-3 shadow-sm cursor-pointer active:cursor-grabbing
                                   group transition-all duration-150 hover:shadow-md"
                            @click="handleCardClick($event, deal.id)">

                            {{-- Left accent bar --}}
                            <div class="absolute left-0 top-3 bottom-3 w-[3px] rounded-r-full"
                                :style="'background-color:' + stageConfig[deal.stage]?.accent"></div>

                            {{-- Drag handle --}}
                            <div class="text-slate-300 dark:text-slate-600 group-hover:text-slate-500 dark:group-hover:text-slate-400 transition text-sm pt-0.5 select-none shrink-0">
                                ⠿</div>

                            <div class="flex-1 min-w-0 pl-1">
                                {{-- Name --}}
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100
                                          group-hover:text-indigo-600 dark:group-hover:text-indigo-400
                                          transition truncate mb-2"
                                    x-text="deal.name"></p>

                                {{-- Amount --}}
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <span class="text-sm font-bold text-slate-800 dark:text-white tabular-nums"
                                        x-text="'£' + (Number(deal.amount) || 0).toLocaleString('en-GB', {maximumFractionDigits:0})"></span>
                                    <template x-if="deal.contacts && deal.contacts[0]">
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full
                                                     bg-slate-100 dark:bg-slate-700
                                                     text-slate-600 dark:text-slate-300 shrink-0 truncate max-w-[100px]"
                                            x-text="deal.contacts[0].first_name + ' ' + deal.contacts[0].last_name"></span>
                                    </template>
                                </div>

                                {{-- Company + Owner --}}
                                <div class="space-y-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{-- Company --}}
                                    <template x-if="deal.companies && deal.companies[0]">
                                        <div class="flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M19 3v18h-6v-3.5h-2V21H5V3zm-4 4h2V5h-2zm-4 0h2V5h-2zM7 7h2V5H7zm8 4h2V9h-2zm-4 0h2V9h-2zm-4 0h2V9H7zm8 4h2v-2h-2zm-4 0h2v-2h-2zm-4 0h2v-2H7zm8 4h2v-2h-2zm-8 0h2v-2H7zM21 1H3v22h18z"/>
                                            </svg>
                                            <span class="truncate" x-text="deal.companies[0].name"></span>
                                        </div>
                                    </template>

                                    {{-- Owner --}}
                                    <div class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 16 16">
                                            <path d="M0 0h16v16H0z" fill="none"/>
                                            <path fill="currentColor" d="M11 7c0 1.66-1.34 3-3 3S5 8.66 5 7s1.34-3 3-3s3 1.34 3 3"/>
                                            <path fill="currentColor" fill-rule="evenodd" d="M16 8c0 4.42-3.58 8-8 8s-8-3.58-8-8s3.58-8 8-8s8 3.58 8 8M4 13.75C4.16 13.484 5.71 11 7.99 11c2.27 0 3.83 2.49 3.99 2.75A6.98 6.98 0 0 0 14.99 8c0-3.87-3.13-7-7-7s-7 3.13-7 7c0 2.38 1.19 4.49 3.01 5.75" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="truncate font-medium" x-text="deal.user ? deal.user.name : 'Unassigned'"></span>
                                    </div>

                                    {{-- Created --}}
                                    <div class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 opacity-70" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7 2h1a1 1 0 0 1 1 1v1h5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3V3a1 1 0 0 1 1-1m8 2h1V3h-1zM8 4V3H7v1zM6 5a2 2 0 0 0-2 2v1h15V7a2 2 0 0 0-2-2zM4 18a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V9H4zm8-5h5v5h-5zm1 1v3h3v-3z"/>
                                        </svg>
                                        <span x-text="timeAgo(deal.created_at)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Load more button --}}
                <template x-if="kanbanData[stage]?.has_more">
                    <button @click="$wire.loadMoreInStage(stage)"
                        class="w-full py-2.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition flex items-center justify-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <span x-text="'Load ' + Math.min(kanbanData[stage].count - (kanbanData[stage].offset || 20), 20) + ' more'"></span>
                        <span class="text-slate-400" x-text="'(' + (kanbanData[stage].count - (kanbanData[stage].offset || 20)) + ' remaining)'"></span>
                    </button>
                </template>
            </div>

            {{-- Compliance footer --}}
            <template x-if="isSalesUser && !canEditStage(stage)">
                <div class="px-3 py-2 text-[11px] text-center text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 rounded-b-2xl border-t border-amber-100 dark:border-amber-900/30">
                    Managed by Compliance</div>
            </template>
        </div>
    </template>

</div>

<script>
    /**
     * Kanban Board Alpine Component
     *
     * Strategy:
     *  1. Serve pre-rendered server data immediately
     *  2. Cache to localStorage for instant return visits
     *  3. Optimistic updates on drag-drop
     *  4. Background sync with server
     */
    function kanbanBoard({
        kanbanData,
        stages,
        stageConfig,
        isSalesUser,
        editableStages
    }) {
        const CACHE_KEY = `kanban_v1_{{ auth()->id() }}`;
        const MAX_CACHE_AGE_MS = 15 * 60 * 1000; // 15 min

        // Initialize kanbanData from server - data is already keyed by stage with full metadata
        // Format: { 'stage-name': { deals: [], count: 0, total_amount: 0, has_more: false } }
        let kanbanDataMap = {};

        if (kanbanData && typeof kanbanData === 'object') {
            // Check if it's already in the correct format (server-keyed)
            if (kanbanData['doc sent'] || kanbanData['doc signed']) {
                // Already properly formatted - use directly
                kanbanDataMap = kanbanData;
            } else {
                // Legacy array format conversion (shouldn't happen with current server)
                Object.keys(kanbanData).forEach(stage => {
                    if (kanbanData[stage]) {
                        kanbanDataMap[stage] = kanbanData[stage];
                    }
                });
            }
        }

        function readCache() {
            try {
                const raw = localStorage.getItem(CACHE_KEY);
                if (!raw) return null;
                const { v, ts, data } = JSON.parse(raw);
                if (v !== 1) return null;
                if (Date.now() - ts > MAX_CACHE_AGE_MS) return null;
                return data;
            } catch {
                return null;
            }
        }

        function writeCache(data) {
            try {
                localStorage.setItem(CACHE_KEY, JSON.stringify({
                    v: 1,
                    ts: Date.now(),
                    data,
                }));
            } catch { /* quota */ }
        }

        return {
            kanbanData: kanbanDataMap,
            stages,
            stageConfig,
            isSalesUser,
            isComplianceUser: !isSalesUser,
            editableStages: new Set(editableStages),
            draggingId: null,
            draggingStage: null,
            dragOverStage: null,

            init() {
                // Use server data passed via props as primary source
                // Only fall back to localStorage if no server data is available
                if (kanbanDataMap && Object.keys(kanbanDataMap).length > 0) {
                    this.kanbanData = kanbanDataMap;
                    // Update localStorage with fresh server data
                    writeCache(this.kanbanData);
                } else {
                    // No server data, try localStorage cache
                    const cached = readCache();
                    if (cached && Object.keys(cached).length > 0) {
                        this.kanbanData = cached;
                    }
                }

                // Watch for Livewire updates
                this.$watch('$wire.kanbanData', (serverData) => {
                    if (serverData && Object.keys(serverData).length > 0) {
                        this.syncWithServer(serverData);
                    }
                });

                // Listen for refresh events
                this.$wire.$on('deals-updated', () => {
                    this.saveState();
                });
            },

            syncWithServer(serverData) {
                // Server data is already in correct format: { stage: { deals: [], count, total_amount, has_more } }
                if (serverData && typeof serverData === 'object') {
                    this.kanbanData = serverData;
                    this.saveState();
                }
            },

            saveState() {
                if (Object.keys(this.kanbanData).length > 0) {
                    writeCache(this.kanbanData);
                }
            },

            canEditStage(stage) {
                return this.editableStages.has(stage);
            },

            getDealsByStage(stage) {
                return this.kanbanData[stage]?.deals || [];
            },

            getStageCount(stage) {
                return this.kanbanData[stage]?.count || 0;
            },

            getStageSum(stage) {
                // Use pre-computed total_amount from server
                return this.kanbanData[stage]?.total_amount || 0;
            },

            timeAgo(dateStr) {
                if (!dateStr) return '';
                const diff = Date.now() - new Date(dateStr).getTime();
                const mins = Math.floor(diff / 60000);
                const hours = Math.floor(diff / 3600000);
                const days = Math.floor(diff / 86400000);
                if (mins < 1) return 'just now';
                if (mins < 60) return mins + ' min ago';
                if (hours < 24) return hours + 'h ago';
                if (days < 30) return days + 'd ago';
                return new Date(dateStr).toLocaleDateString('en-GB');
            },

            // Context menu state
            contextMenu: null,



            handleCardClick(event, dealId) {
                if (event.target.closest('a')) return;
                if (this.draggingId) return;
                window.location.href = `/deals/${dealId}`;
            },

            handleContextMenuAction(action, deal, stage = null) {
                this.hideCardContextMenu();
                this.$wire.$set('contextMenuAction', {
                    action: action,
                    deal: deal,
                    stage: stage
                });
            },

            onDragStart(dealId, stage, event) {
                if (!this.canEditStage(stage)) {
                    event.preventDefault();
                    return;
                }
                this.draggingId = dealId;
                this.draggingStage = stage;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(dealId));
            },

            onDragOver(targetStage) {
                this.dragOverStage = targetStage;
            },

            onDragLeave() {
                this.dragOverStage = null;
            },

            async onDrop(targetStage) {
                this.dragOverStage = null;

                const dealId = this.draggingId;
                const fromStage = this.draggingStage;

                if (!dealId || fromStage === targetStage) {
                    this.onDragEnd();
                    return;
                }

                // Find the deal
                const deal = this.findDeal(dealId);
                if (!deal) {
                    this.onDragEnd();
                    return;
                }

                // Check permission BEFORE allowing drop
                // canEditStage returns true for editable (unrestricted) stages
                // Show modal when trying to move to a restricted stage (!canEditStage)
                if (!this.canEditStage(targetStage)) {
                    // Call Livewire method to show the permission modal
                    const stageName = this.stageConfig[targetStage]?.label || targetStage;
                    this.$wire.showPermissionModal(deal.name, stageName);
                    this.onDragEnd();
                    return;
                }

                this.onDragEnd();

                // Optimistic update - move locally first
                this.moveDealLocally(dealId, fromStage, targetStage);

                try {
                    await this.$wire.updateStage(dealId, targetStage);
                } catch (err) {
                    // Revert on error
                    this.moveDealLocally(dealId, targetStage, fromStage);
                    this.shakeCard(dealId);
                }
            },

            findDeal(dealId) {
                for (const stage of this.stages) {
                    const stageData = this.kanbanData[stage];
                    if (!stageData || !stageData.deals) continue;
                    const deal = stageData.deals.find(d => d.id === dealId);
                    if (deal) return deal;
                }
                return null;
            },

            moveDealLocally(dealId, fromStage, toStage) {
                const fromData = this.kanbanData[fromStage];
                const toData = this.kanbanData[toStage];
                if (!fromData?.deals || !toData?.deals) return;

                const fromDeals = fromData.deals;
                const toDeals = toData.deals;

                const dealIndex = fromDeals.findIndex(d => d.id === dealId);
                if (dealIndex === -1) return;

                const [deal] = fromDeals.splice(dealIndex, 1);
                deal.stage = toStage;
                toDeals.unshift(deal);

                // Update counts
                fromData.count = (fromData.count || 0) - 1;
                toData.count = (toData.count || 0) + 1;

                // Update totals if amount is available
                if (deal.amount) {
                    fromData.total_amount = (fromData.total_amount || 0) - deal.amount;
                    toData.total_amount = (toData.total_amount || 0) + deal.amount;
                }

                this.saveState();
            },

            shakeCard(dealId) {
                const card = document.querySelector(`[data-deal-id='${dealId}']`);
                if (card) {
                    card.style.transition = 'transform .1s ease';
                    [0, -6, 6, -4, 4, 0].forEach((x, i) => {
                        setTimeout(() => card.style.transform = `translateX(${x}px)`, i * 60);
                    });
                    setTimeout(() => card.style.transform = '', 6 * 60 + 50);
                }
            },

            onDragEnd() {
                this.draggingId = null;
                this.draggingStage = null;
                this.dragOverStage = null;
                this.stopAutoScroll();
            },

            // Auto-scroll when dragging near viewport edges
            scrollInterval: null,

            onDrag(event) {
                if (!this.draggingId) return;

                const edgeThreshold = 80; // px from viewport edge to start scrolling
                const scrollSpeed = 10; // px per interval
                const scrollInterval = 15; // ms between scrolls

                const clientX = event.clientX;
                const viewportWidth = window.innerWidth;

                // Clear any existing interval
                this.stopAutoScroll();

                if (clientX < edgeThreshold) {
                    // Scroll left
                    this.scrollInterval = setInterval(() => {
                        const board = this.$el;
                        board.scrollLeft -= scrollSpeed;
                    }, scrollInterval);
                } else if (clientX > viewportWidth - edgeThreshold) {
                    // Scroll right
                    this.scrollInterval = setInterval(() => {
                        const board = this.$el;
                        board.scrollLeft += scrollSpeed;
                    }, scrollInterval);
                }
            },

            stopAutoScroll() {
                if (this.scrollInterval) {
                    clearInterval(this.scrollInterval);
                    this.scrollInterval = null;
                }
            },
        };
    }
</script>

@pushOnce('scripts')
<script>
    // Global escape key listener for context menus
    function initGlobalContextMenuHandlers() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Find all visible context menus and hide them
                document.querySelectorAll('[x-show="contextMenu"]').forEach(el => {
                    if (el._x_isShown) {
                        // Find the Alpine component and call hideCardContextMenu
                        const component = el.__x;
                        if (component) {
                            component.hideCardContextMenu();
                        }
                    }
                });
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalContextMenuHandlers);
    } else {
        initGlobalContextMenuHandlers();
    }
</script>
@endpushOnce
