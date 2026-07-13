<?php

namespace App\Http\Controllers\Api\Deals;

use App\Enums\DealStage;
use App\Http\Controllers\Controller;
use App\Jobs\RefreshKanbanCacheJob;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Lightweight API endpoint for Kanban board.
 * Returns minimal data for fast rendering.
 */
class KanbanController extends Controller
{
    private const CACHE_TTL_SECONDS = 60; // 1 minute

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = $this->buildCacheKey($user?->id, $request->all());

        // Stale-while-revalidate pattern
        $cached = $this->getCachedData($cacheKey);

        if ($cached && ! $request->boolean('fresh')) {
            $response = response()->json($cached);

            // Background refresh if cache is older than 30 seconds
            if ($cached['_cached_at'] < now()->subSeconds(30)->timestamp) {
                $this->refreshInBackground($user, $request->all(), $cacheKey);
            }

            unset($cached['_cached_at']);

            return $response->header('X-Cache', 'HIT');
        }

        $data = $this->fetchKanbanData($user, $request->all());
        $data['_cached_at'] = now()->timestamp;

        $this->setCachedData($cacheKey, $data);

        return response()->json($data)->header('X-Cache', 'MISS');
    }

    private function buildCacheKey(?int $userId, array $params): string
    {
        return 'kanban_'.($userId ?? 'guest').'_'.md5(json_encode($params));
    }

    /**
     * Get cached data.
     */
    private function getCachedData(string $key): ?array
    {
        return Cache::get($key);
    }

    /**
     * Store data in cache.
     */
    private function setCachedData(string $key, array $data): void
    {
        Cache::put($key, $data, now()->addSeconds(self::CACHE_TTL_SECONDS));
    }

    private function fetchKanbanData(?User $user, array $filters): array
    {
        $query = Deal::query()
            ->select([
                'id', 'name', 'amount', 'stage', 'user_id',
                'created_at',
            ])
            ->with([
                'contacts:id,first_name,last_name',
                'companies:id,name',
                'user:id,name',
            ]);

        // Apply user visibility scope
        if ($user?->isSalesTeam()) {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        $this->applyFilters($query, $filters);

        // Order by stage (for kanban grouping) then by most recent
        $query->orderBy('stage', 'asc')
            ->orderBy('updated_at', 'desc');

        $deals = $query->get();

        // Group by stage and compute summaries
        $stages = [];
        $totalAmount = 0;

        foreach (DealStage::cases() as $stage) {
            $stageDeals = $deals->where('stage', $stage->value)->values();

            $stages[$stage->value] = [
                'deals' => $stageDeals->map(fn ($deal) => $this->serializeDeal($deal))->all(),
                'count' => $stageDeals->count(),
                'total_amount' => $stageDeals->sum('amount'),
            ];

            $totalAmount += $stages[$stage->value]['total_amount'];
        }

        return [
            'stages' => $stages,
            'total_deals' => $deals->count(),
            'total_amount' => $totalAmount,
        ];
    }

    private function serializeDeal(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'amount' => (float) $deal->amount,
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
                    'pivot' => ['is_primary' => (bool) $c->pivot?->is_primary],
                ])->all()
                : [],
            'companies' => $deal->relationLoaded('companies')
                ? $deal->companies->take(1)->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'pivot' => [
                        'is_primary' => (bool) $c->pivot?->is_primary,
                        'agency_deal_value' => $c->pivot?->agency_deal_value,
                        'margin_agreed' => $c->pivot?->margin_agreed,
                    ],
                ])->all()
                : [],
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['filterDealName'])) {
            $query->where('name', 'like', '%'.$filters['filterDealName'].'%');
        }

        if (! empty($filters['filterStage'])) {
            $query->where('stage', $filters['filterStage']);
        }

        if (! empty($filters['minAmount'])) {
            $query->where('amount', '>=', $filters['minAmount']);
        }

        if (! empty($filters['maxAmount'])) {
            $query->where('amount', '<=', $filters['maxAmount']);
        }

        if (! empty($filters['dateFrom'])) {
            $query->whereDate('created_at', '>=', $filters['dateFrom']);
        }

        if (! empty($filters['dateTo'])) {
            $query->whereDate('created_at', '<=', $filters['dateTo']);
        }

        if (! empty($filters['filterOwner'])) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$filters['filterOwner'].'%'));
        }

        if (! empty($filters['filterCompanyName'])) {
            $query->whereHas('companies', fn ($q) => $q->where('name', 'like', '%'.$filters['filterCompanyName'].'%'));
        }

        if (! empty($filters['filterContact'])) {
            $query->whereHas('contacts', fn ($q) => $q->where('first_name', 'like', '%'.$filters['filterContact'].'%')
                ->orWhere('last_name', 'like', '%'.$filters['filterContact'].'%'));
        }
    }

    private function refreshInBackground(?User $user, array $filters, string $cacheKey): void
    {
        // Dispatch job for background refresh (works with database queue)
        RefreshKanbanCacheJob::dispatch(
            userId: $user?->id,
            filters: $filters,
            cacheKey: $cacheKey,
            ttlSeconds: self::CACHE_TTL_SECONDS,
        );
    }

    /**
     * Update deal stage - lightweight endpoint for drag-drop.
     */
    public function updateStage(Request $request, Deal $deal): JsonResponse
    {
        $user = $request->user();

        // Authorization
        if ($user->isSalesTeam() && $deal->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'stage' => 'required|string|in:doc sent,doc signed,compliant,ready for payment,paid',
        ]);

        $oldStage = $deal->stage->value;
        $deal->update(['stage' => DealStage::from($validated['stage'])]);

        // Invalidate cache
        $this->invalidateCache($user?->id);

        return response()->json([
            'success' => true,
            'deal' => $this->serializeDeal($deal->fresh()),
            'old_stage' => $oldStage,
            'new_stage' => $validated['stage'],
        ]);
    }

    /**
     * Invalidate kanban caches.
     * For file/database cache, clears all kanban caches by prefix.
     */
    private function invalidateCache(?int $userId): void
    {
        // Flush all kanban caches - for file-based cache, this clears stale data
        // In production with many users, consider implementing prefix-based clearing
        Cache::flush();
    }
}
