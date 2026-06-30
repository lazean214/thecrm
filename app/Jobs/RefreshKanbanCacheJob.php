<?php

namespace App\Jobs;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RefreshKanbanCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 30;

    public function __construct(
        public ?int $userId,
        public array $filters,
        public string $cacheKey,
        public int $ttlSeconds = 60,
    ) {}

    public function handle(): void
    {
        $user = $this->userId ? User::find($this->userId) : null;

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
        $this->applyFilters($query, $this->filters);

        // Order by stage
        $query->orderByRaw("FIELD(stage, 'doc sent', 'doc signed', 'compliant', 'ready for payment', 'paid')")
            ->orderBy('updated_at', 'desc');

        $deals = $query->get();

        // Group by stage
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

        $data = [
            'stages' => $stages,
            'total_deals' => $deals->count(),
            'total_amount' => $totalAmount,
            '_cached_at' => now()->timestamp,
        ];

        Cache::put($this->cacheKey, $data, now()->addSeconds($this->ttlSeconds));
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
}
