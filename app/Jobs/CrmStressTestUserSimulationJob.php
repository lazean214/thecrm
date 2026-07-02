<?php

namespace App\Jobs;

use App\Enums\DealStage;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CrmStressTestUserSimulationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Action weights for random selection
     */
    private const ACTION_WEIGHTS = [
        'fetch_deals' => 40,
        'update_deal' => 25,
        'search_contacts' => 20,
        'batch_operations' => 10,
        'export' => 5,
    ];

    /**
     * Cache prefix for metrics
     */
    private const CACHE_PREFIX = 'stress_test:';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public int $batchSize = 1
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $user = User::find($this->userId);

        if (! $user) {
            $this->logError('user_not_found', "User ID {$this->userId} not found");

            return;
        }

        $this->logInfo("User {$user->id} starting simulation");

        // Perform multiple actions per simulation
        $actionCount = random_int(3, 8);
        for ($i = 0; $i < $actionCount; $i++) {
            $this->performRandomAction($user);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->logInfo("User {$user->id} completed simulation in {$duration}ms");
        $this->recordMetrics($duration);
    }

    /**
     * Perform a random CRM action based on weighted probabilities
     */
    private function performRandomAction(User $user): void
    {
        $action = $this->selectWeightedAction();

        $this->logInfo("  Performing action: {$action}");

        match ($action) {
            'fetch_deals' => $this->fetchDeals($user),
            'update_deal' => $this->updateRandomDeal($user),
            'search_contacts' => $this->searchContacts($user),
            'batch_operations' => $this->performBatchOperations($user),
            'export' => $this->generateExport($user),
            default => $this->logError('unknown_action', "Unknown action: {$action}"),
        };
    }

    /**
     * Fetch deals with paginated results and random filters
     */
    private function fetchDeals(User $user): void
    {
        $startTime = microtime(true);

        $query = Deal::query()
            ->with(['contacts', 'companies', 'user'])
            ->visibleTo($user);

        // Apply random filters
        $filterType = random_int(1, 5);
        switch ($filterType) {
            case 1:
                // Filter by stage
                $stages = array_column(DealStage::cases(), 'value');
                $query->where('stage', fake()->randomElement($stages));
                break;

            case 2:
                // Filter by amount range
                $query->whereBetween('amount', [
                    fake()->numberBetween(5000, 10000),
                    fake()->numberBetween(20000, 50000),
                ]);
                break;

            case 3:
                // Filter by owner
                $query->where('user_id', $user->id);
                break;

            case 4:
                // Filter by recruitment agency
                $query->where('recruitment_agency', fake()->randomElement(['Inbound', 'Referral']));
                break;

            case 5:
                // No filter - all deals
                break;
        }

        // Paginated results
        $perPage = fake()->randomElement([10, 15, 25, 50]);
        $page = random_int(1, 5);

        $deals = $query->paginate($perPage, ['*'], 'page', $page);

        $this->recordActionDuration('fetch_deals', $startTime);
        $this->incrementActionCounter('fetch_deals');

        $this->logInfo("    Fetched {$deals->count()} deals (page {$page}/{$deals->lastPage()})");
    }

    /**
     * Update a random deal's stage, value, or owner
     */
    private function updateRandomDeal(User $user): void
    {
        $startTime = microtime(true);

        $deal = Deal::query()
            ->visibleTo($user)
            ->inRandomOrder()
            ->first();

        if (! $deal) {
            $this->logInfo('    No deal found to update');

            return;
        }

        $updateType = fake()->numberBetween(1, 3);
        $updates = [];

        switch ($updateType) {
            case 1:
                // Update stage (if user has permission)
                if ($user->canMoveToStage($deal->stage)) {
                    $updates['stage'] = $user->getAllowedDealStages()[array_rand($user->getAllowedDealStages())];
                    $updates['stage_updated_at'] = now();
                }
                break;

            case 2:
                // Update amount
                $updates['amount'] = fake()->numberBetween(5000, 50000);
                break;

            case 3:
                // Update margin
                $updates['margin_agreed'] = fake()->randomFloat(2, 5, 40);
                break;
        }

        if (! empty($updates)) {
            $deal->update($updates);
            $this->logInfo("    Updated deal ID {$deal->id}: ".json_encode($updates));
        }

        $this->recordActionDuration('update_deal', $startTime);
        $this->incrementActionCounter('update_deal');
    }

    /**
     * Search contacts with random query
     */
    private function searchContacts(User $user): void
    {
        $startTime = microtime(true);

        $searchType = random_int(1, 4);
        $query = Contact::query()->with('companies');

        switch ($searchType) {
            case 1:
                // Search by first name
                $query->where('first_name', 'like', '%'.fake()->firstName().'%');
                break;

            case 2:
                // Search by email
                $query->where('email', 'like', '%@%');
                break;

            case 3:
                // Search by city
                $query->where('city', 'like', '%'.fake()->city().'%');
                break;

            case 4:
                // No search term - all contacts
                break;
        }

        // Paginated results
        $contacts = $query->limit(50)->get();

        $this->recordActionDuration('search_contacts', $startTime);
        $this->incrementActionCounter('search_contacts');

        $this->logInfo("    Found {$contacts->count()} contacts");
    }

    /**
     * Perform batch operations on multiple deals
     */
    private function performBatchOperations(User $user): void
    {
        $startTime = microtime(true);

        $dealCount = random_int(5, 10);
        $deals = Deal::query()
            ->visibleTo($user)
            ->inRandomOrder()
            ->limit($dealCount)
            ->get();

        if ($deals->isEmpty()) {
            $this->logInfo('    No deals found for batch operation');
            $this->incrementActionCounter('batch_operations');

            return;
        }

        // Simulate batch operations
        $operationType = random_int(1, 3);
        $processedCount = 0;

        foreach ($deals as $deal) {
            switch ($operationType) {
                case 1:
                    // Bulk stage update
                    if ($user->canMoveToStage($deal->stage)) {
                        $deal->update([
                            'stage' => $user->getAllowedDealStages()[array_rand($user->getAllowedDealStages())],
                            'stage_updated_at' => now(),
                        ]);
                        $processedCount++;
                    }
                    break;

                case 2:
                    // Bulk amount update
                    $deal->update([
                        'amount' => fake()->numberBetween(5000, 50000),
                    ]);
                    $processedCount++;
                    break;

                case 3:
                    // Bulk margin update
                    $deal->update([
                        'margin_agreed' => fake()->randomFloat(2, 5, 40),
                    ]);
                    $processedCount++;
                    break;
            }
        }

        $this->recordActionDuration('batch_operations', $startTime);
        $this->incrementActionCounter('batch_operations');

        $this->logInfo("    Batch operation completed: {$processedCount} deals processed");
    }

    /**
     * Generate export (simulated without actual file download)
     */
    private function generateExport(User $user): void
    {
        $startTime = microtime(true);

        // Build filters similar to actual export
        $filters = [
            'filterDealName' => fake()->optional(0.3)->word(),
            'filterOwner' => fake()->optional(0.3)->name(),
            'filterStage' => fake()->optional(0.5)->randomElement(
                array_column(DealStage::cases(), 'value')
            ),
            'minAmount' => fake()->optional(0.3)->numberBetween(5000, 10000),
            'maxAmount' => fake()->optional(0.3)->numberBetween(20000, 50000),
        ];

        // Build query (don't actually export in test)
        $query = Deal::query()
            ->with(['contacts', 'companies', 'user'])
            ->visibleTo($user);

        // Apply filters
        if (! empty($filters['filterDealName'])) {
            $query->where('name', 'like', '%'.$filters['filterDealName'].'%');
        }

        if (! empty($filters['filterOwner'])) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$filters['filterOwner'].'%'));
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

        $exportCount = $query->count();

        // Simulate export processing time
        usleep(random_int(10000, 50000)); // 10-50ms

        $this->recordActionDuration('export', $startTime);
        $this->incrementActionCounter('export');

        $this->logInfo("    Export generated: {$exportCount} deals (filters: ".json_encode($filters).')');
    }

    /**
     * Select action based on weighted probabilities
     */
    private function selectWeightedAction(): string
    {
        $roll = random_int(1, 100);
        $cumulative = 0;

        foreach (self::ACTION_WEIGHTS as $action => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return $action;
            }
        }

        return 'fetch_deals';
    }

    /**
     * Record metrics for monitoring
     */
    private function recordMetrics(float $durationMs): void
    {
        Cache::increment(self::CACHE_PREFIX.'total_requests');
        Cache::increment(self::CACHE_PREFIX.'total_duration_ms', (int) $durationMs);
    }

    /**
     * Record action duration
     */
    private function recordActionDuration(string $action, float $startTime): void
    {
        $durationMs = (microtime(true) - $startTime) * 1000;
        // Duration tracking could be expanded per-action if needed
    }

    /**
     * Increment action counter
     */
    private function incrementActionCounter(string $action): void
    {
        Cache::increment(self::CACHE_PREFIX."action:{$action}");
    }

    /**
     * Log info message
     */
    private function logInfo(string $message): void
    {
        Log::channel('single')->info("[CRM Stress Test] {$message}");
    }

    /**
     * Log error message
     */
    private function logError(string $type, string $message): void
    {
        Log::channel('single')->error("[CRM Stress Test] [{$type}] {$message}");
        Cache::increment(self::CACHE_PREFIX.'total_errors');
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->logError('job_failed', "Job failed for user {$this->userId}: ".$exception->getMessage());
        Cache::increment(self::CACHE_PREFIX.'total_errors');
    }
}
