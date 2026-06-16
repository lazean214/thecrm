<?php

namespace App\Console\Commands;

use App\Jobs\CrmStressTestUserSimulationJob;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CrmStressTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:stress-test
                            {--users=50 : Number of concurrent users to simulate}
                            {--duration=300 : Test duration in seconds}
                            {--batch-size=10 : Number of operations per batch}
                            {--sync : Run synchronously without queue worker (for shared hosting)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stress test the CRM by simulating concurrent user activity';

    /**
     * Cache keys for tracking test metrics
     */
    private const CACHE_PREFIX = 'stress_test:';

    /**
     * Metrics storage
     *
     * @var array<string, mixed>
     */
    private array $metrics = [
        'total_requests' => 0,
        'total_errors' => 0,
        'total_duration_ms' => 0,
        'actions' => [
            'fetch_deals' => 0,
            'update_deal' => 0,
            'search_contacts' => 0,
            'batch_operations' => 0,
            'export' => 0,
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $numUsers = (int) $this->option('users');
        $duration = (int) $this->option('duration');
        $batchSize = (int) $this->option('batch-size');
        $sync = (bool) $this->option('sync');

        $this->banner('CRM Stress Test Starting');
        $this->table(
            ['Parameter', 'Value'],
            [
                ['Simulated Users', $numUsers],
                ['Duration', "{$duration} seconds"],
                ['Batch Size', $batchSize],
                ['Mode', $sync ? 'SYNCHRONOUS (no queue worker needed)' : 'QUEUE-BASED'],
            ]
        );

        // Initialize metrics
        $this->initializeMetrics();

        $users = User::all();

        if ($users->isEmpty()) {
            $this->error('No users found in database. Please run seeders first.');
            $this->line('  php artisan db:seed --class=StressTestSeeder');

            return self::FAILURE;
        }

        $this->info("Found {$users->count()} users in database.");

        $startTime = Carbon::now();
        $endTime = $startTime->copy()->addSeconds($duration);

        $this->info("Test started at: {$startTime->toDateTimeString()}");
        $this->info("Test will end at: {$endTime->toDateTimeString()}");

        if ($sync) {
            $this->runSyncTest($numUsers, $duration, $batchSize, $users, $startTime, $endTime);
        } else {
            $this->runQueueTest($numUsers, $batchSize, $users, $endTime);
        }

        // Generate report
        $this->generateReport($startTime);

        return self::SUCCESS;
    }

    /**
     * Run stress test synchronously (for shared hosting)
     */
    private function runSyncTest(
        int $numUsers,
        int $duration,
        int $batchSize,
        $users,
        Carbon $startTime,
        Carbon $endTime
    ): void {
        $this->newLine();
        $this->info('Running SYNCHRONOUS stress test...');
        $this->warn('This will run in the foreground for '.$duration.' seconds.');
        $this->line(str_repeat('-', 60));

        $iteration = 0;
        while (Carbon::now()->lt($endTime)) {
            $this->newLine();
            $this->line('Batch #'.++$iteration);
            $this->line(str_repeat('-', 40));

            // Simulate multiple users in this batch
            $operationsThisBatch = 0;

            for ($i = 0; $i < $batchSize; $i++) {
                $user = $users->random();
                $actionsThisUser = random_int(3, 8);

                for ($j = 0; $j < $actionsThisUser; $j++) {
                    $this->simulateUserAction($user);
                    $operationsThisBatch++;
                }
            }

            // Progress display
            $elapsed = Carbon::now()->diffInSeconds($startTime);
            $remaining = Carbon::now()->diffInSeconds($endTime);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Elapsed', $elapsed.'s ('.$remaining.'s remaining)'],
                    ['Total Operations', number_format($this->metrics['total_requests'])],
                    ['Total Errors', number_format($this->metrics['total_errors'])],
                    ['Avg Response Time', round($this->metrics['avg_response_time'], 2).' ms'],
                    ['Ops/Second', round($operationsThisBatch / 5, 1)],
                ]
            );

            sleep(5);
        }

        $this->newLine();
        $this->info('Synchronous test complete!');
    }

    /**
     * Simulate a single user action (for sync mode)
     */
    private function simulateUserAction(User $user): void
    {
        $startTime = microtime(true);
        $action = $this->selectWeightedAction();

        try {
            match ($action) {
                'fetch_deals' => $this->simulateFetchDeals($user),
                'update_deal' => $this->simulateUpdateDeal($user),
                'search_contacts' => $this->simulateSearchContacts($user),
                'batch_operations' => $this->simulateBatchOperations($user),
                'export' => $this->simulateExport($user),
            };

            $this->metrics['total_requests']++;
            $this->metrics['actions'][$action]++;
        } catch (\Throwable $e) {
            $this->metrics['total_errors']++;
            $this->line("  <error>Error in {$action}: {$e->getMessage()}</error>");
        }

        $duration = (microtime(true) - $startTime) * 1000;
        $this->metrics['total_duration_ms'] += $duration;
        $this->metrics['avg_response_time'] = $this->metrics['total_duration_ms'] / max($this->metrics['total_requests'], 1);
    }

    /**
     * Run stress test with queue (for servers with queue worker)
     */
    private function runQueueTest(int $numUsers, int $batchSize, $users, Carbon $endTime): void
    {
        $this->newLine();
        $this->info('Running QUEUE-BASED stress test...');
        $this->warn('Make sure queue worker is running: php artisan queue:work');
        $this->line(str_repeat('-', 60));

        // Dispatch initial batch
        $this->dispatchInitialBatch($numUsers, $batchSize);

        // Monitor loop
        $this->monitorQueueTest($numUsers, $endTime, $batchSize, $users);
    }

    /**
     * Initialize metrics in cache
     */
    private function initializeMetrics(): void
    {
        $this->metrics = [
            'total_requests' => 0,
            'total_errors' => 0,
            'total_duration_ms' => 0,
            'avg_response_time' => 0,
            'actions' => [
                'fetch_deals' => 0,
                'update_deal' => 0,
                'search_contacts' => 0,
                'batch_operations' => 0,
                'export' => 0,
            ],
        ];

        $keys = ['total_requests', 'total_errors', 'total_duration_ms', 'avg_response_time'];
        foreach ($keys as $key) {
            Cache::put(self::CACHE_PREFIX.$key, 0);
        }

        foreach (array_keys($this->metrics['actions']) as $action) {
            Cache::put(self::CACHE_PREFIX."action:{$action}", 0);
        }
    }

    /**
     * Dispatch initial batch of user simulation jobs
     */
    private function dispatchInitialBatch(int $numUsers, int $batchSize): void
    {
        $this->newLine();
        $this->info("Dispatching {$numUsers} user simulation jobs...");

        $batches = ceil($numUsers / $batchSize);

        for ($i = 0; $i < $batches; $i++) {
            $batchEnd = min(($i + 1) * $batchSize, $numUsers);

            for ($j = $i * $batchSize; $j < $batchEnd; $j++) {
                CrmStressTestUserSimulationJob::dispatch(
                    userId: $j + 1,
                    batchSize: $batchSize,
                );
            }

            $this->line('  Dispatched batch '.($i + 1)."/{$batches} ({$batchEnd} jobs total)");
        }

        $this->info('All jobs dispatched successfully.');
    }

    /**
     * Monitor queue-based test
     */
    private function monitorQueueTest(int $numUsers, Carbon $endTime, int $batchSize, $users): void
    {
        $this->newLine();
        $this->info('Monitoring test progress...');
        $this->line(str_repeat('-', 60));

        $iteration = 0;
        while (Carbon::now()->lt($endTime)) {
            $stats = $this->gatherStats();
            $backlog = $this->getQueueBacklog();

            $this->newLine();
            $this->line(str_repeat('-', 60));
            $this->line('Progress Update #'.++$iteration);
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Elapsed', Carbon::now()->diffForHumans($endTime, true).' remaining'],
                    ['Queue Backlog', $backlog],
                    ['Total Requests', number_format($stats['total_requests'])],
                    ['Total Errors', number_format($stats['total_errors'])],
                    ['Avg Response Time', round($stats['avg_response_time'], 2).' ms'],
                    ['Requests/User', round($stats['total_requests'] / max($numUsers, 1), 1)],
                ]
            );

            // Dispatch more jobs to maintain concurrency
            if ($backlog < $numUsers / 2) {
                $dispatchCount = min($batchSize, $numUsers);
                for ($i = 0; $i < $dispatchCount; $i++) {
                    $user = $users->random();
                    CrmStressTestUserSimulationJob::dispatch(
                        userId: $user->id,
                        batchSize: 1,
                    );
                }
            }

            sleep(5);
        }

        $this->newLine();
        $this->info('Test duration complete. Waiting for queue to drain...');
        $this->waitForQueueDrain();
    }

    /**
     * Gather statistics from cache (queue mode)
     *
     * @return array<string, mixed>
     */
    private function gatherStats(): array
    {
        $totalRequests = Cache::get(self::CACHE_PREFIX.'total_requests', 0);
        $totalErrors = Cache::get(self::CACHE_PREFIX.'total_errors', 0);
        $totalDuration = Cache::get(self::CACHE_PREFIX.'total_duration_ms', 0);

        return [
            'total_requests' => $totalRequests,
            'total_errors' => $totalErrors,
            'avg_response_time' => $totalRequests > 0 ? $totalDuration / $totalRequests : 0,
            'actions' => [
                'fetch_deals' => Cache::get(self::CACHE_PREFIX.'action:fetch_deals', 0),
                'update_deal' => Cache::get(self::CACHE_PREFIX.'action:update_deal', 0),
                'search_contacts' => Cache::get(self::CACHE_PREFIX.'action:search_contacts', 0),
                'batch_operations' => Cache::get(self::CACHE_PREFIX.'action:batch_operations', 0),
                'export' => Cache::get(self::CACHE_PREFIX.'action:export', 0),
            ],
        ];
    }

    /**
     * Get current queue backlog count
     */
    private function getQueueBacklog(): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', config('queue.default'))
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Wait for queue to drain
     */
    private function waitForQueueDrain(): void
    {
        $maxWait = 60;
        $waited = 0;

        while ($this->getQueueBacklog() > 0 && $waited < $maxWait) {
            sleep(2);
            $waited += 2;
            $this->line('  Queue backlog: '.$this->getQueueBacklog());
        }

        if ($waited >= $maxWait) {
            $this->warn("Queue did not drain within {$maxWait} seconds.");
        }
    }

    /**
     * Generate final test report
     */
    private function generateReport(Carbon $startTime): void
    {
        $endTime = Carbon::now();
        $duration = $startTime->diffInSeconds($endTime);

        // For sync mode, use local metrics; for queue mode, use cache
        if ($this->option('sync')) {
            $stats = $this->metrics;
        } else {
            $cacheStats = $this->gatherStats();
            $stats = [
                'total_requests' => $cacheStats['total_requests'],
                'total_errors' => $cacheStats['total_errors'],
                'avg_response_time' => $cacheStats['avg_response_time'],
                'actions' => $cacheStats['actions'],
            ];
        }

        $this->newLine();
        $this->banner('STRESS TEST RESULTS');

        // Summary table
        $this->table(
            ['Metric', 'Value'],
            [
                ['Test Duration', "{$duration} seconds"],
                ['Total Requests', number_format($stats['total_requests'])],
                ['Total Errors', number_format($stats['total_errors'])],
                ['Error Rate', $stats['total_requests'] > 0
                    ? round(($stats['total_errors'] / $stats['total_requests']) * 100, 2).'%'
                    : '0%'],
                ['Avg Response Time', round($stats['avg_response_time'], 2).' ms'],
                ['Requests/Second', round($stats['total_requests'] / max($duration, 1), 2)],
            ]
        );

        // Action breakdown
        $this->newLine();
        $this->info('Action Breakdown:');
        $this->table(
            ['Action', 'Count', 'Percentage'],
            $this->formatActionBreakdown($stats['actions'], $stats['total_requests'])
        );

        // Database stats
        $this->newLine();
        $this->info('Database Query Statistics:');
        $this->table(
            ['Query Type', 'Count'],
            [
                ['SELECT queries (estimated)', number_format($stats['total_requests'] * 3)],
                ['UPDATE queries (estimated)', number_format($stats['actions']['update_deal'] ?? 0)],
            ]
        );

        if (! $this->option('sync')) {
            // Queue stats (not applicable in sync mode)
            $this->newLine();
            $this->info('Queue Statistics:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Final Queue Backlog', number_format($this->getQueueBacklog())],
                    ['Queue Connection', config('queue.default')],
                ]
            );
        }

        $this->newLine();
        $this->info("Stress test completed at: {$endTime->toDateTimeString()}");
        $this->newLine();

        $this->cleanupMetrics();
    }

    /**
     * Format action breakdown for table display
     *
     * @param  array<string, int>  $actions
     */
    private function formatActionBreakdown(array $actions, int $totalRequests): array
    {
        $labels = [
            'fetch_deals' => 'Fetch Deals (paginated)',
            'update_deal' => 'Update Deal',
            'search_contacts' => 'Search Contacts',
            'batch_operations' => 'Batch Operations',
            'export' => 'Generate Export',
        ];

        $rows = [];
        foreach ($actions as $key => $count) {
            $percentage = $totalRequests > 0
                ? round(($count / $totalRequests) * 100, 1)
                : 0;
            $rows[] = [$labels[$key] ?? $key, number_format($count), "{$percentage}%"];
        }

        return $rows;
    }

    /**
     * Clean up cache keys
     */
    private function cleanupMetrics(): void
    {
        $keys = [
            'total_requests',
            'total_errors',
            'total_duration_ms',
            'avg_response_time',
            'action:fetch_deals',
            'action:update_deal',
            'action:search_contacts',
            'action:batch_operations',
            'action:export',
        ];

        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }

    /**
     * Print a banner message
     */
    private function banner(string $message): void
    {
        $border = str_repeat('=', 60);
        $this->newLine();
        $this->line($border);
        $this->line($message);
        $this->line($border);
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Mode Simulation Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Select action based on weighted probabilities
     */
    private function selectWeightedAction(): string
    {
        $weights = [
            'fetch_deals' => 40,
            'update_deal' => 25,
            'search_contacts' => 20,
            'batch_operations' => 10,
            'export' => 5,
        ];

        $roll = random_int(1, 100);
        $cumulative = 0;

        foreach ($weights as $action => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return $action;
            }
        }

        return 'fetch_deals';
    }

    /**
     * Simulate fetching deals
     */
    private function simulateFetchDeals(User $user): void
    {
        $query = Deal::query()
            ->with(['contacts', 'companies', 'user'])
            ->visibleTo($user);

        // Apply random filters
        $filterType = random_int(1, 5);
        switch ($filterType) {
            case 1:
                $query->where('stage', fake()->randomElement(['doc sent', 'doc signed', 'compliant']));
                break;
            case 2:
                $query->whereBetween('amount', [5000, 50000]);
                break;
            case 3:
                $query->where('user_id', $user->id);
                break;
            case 4:
                $query->where('recruitment_agency', fake()->randomElement(['Inbound', 'Referral']));
                break;
        }

        $perPage = fake()->randomElement([10, 15, 25, 50]);
        $page = random_int(1, 5);

        $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Simulate updating a deal
     */
    private function simulateUpdateDeal(User $user): void
    {
        $deal = Deal::query()
            ->visibleTo($user)
            ->inRandomOrder()
            ->first();

        if (! $deal) {
            return;
        }

        $updateType = random_int(1, 3);
        $updates = [];

        switch ($updateType) {
            case 1:
                if ($user->canMoveToStage($deal->stage)) {
                    $allowed = $user->getAllowedDealStages();
                    $updates['stage'] = $allowed[array_rand($allowed)];
                    $updates['stage_updated_at'] = now();
                }
                break;
            case 2:
                $updates['amount'] = fake()->numberBetween(5000, 50000);
                break;
            case 3:
                $updates['margin_agreed'] = fake()->randomFloat(2, 5, 40);
                break;
        }

        if (! empty($updates)) {
            $deal->update($updates);
        }
    }

    /**
     * Simulate searching contacts
     */
    private function simulateSearchContacts(User $user): void
    {
        $query = Contact::query()->with('companies');

        $searchType = random_int(1, 4);
        switch ($searchType) {
            case 1:
                $query->where('first_name', 'like', '%'.fake()->firstName().'%');
                break;
            case 2:
                $query->where('email', 'like', '%@%');
                break;
            case 3:
                $query->where('city', 'like', '%'.fake()->city().'%');
                break;
        }

        $query->limit(50)->get();
    }

    /**
     * Simulate batch operations
     */
    private function simulateBatchOperations(User $user): void
    {
        $dealCount = random_int(5, 10);
        $deals = Deal::query()
            ->visibleTo($user)
            ->inRandomOrder()
            ->limit($dealCount)
            ->get();

        foreach ($deals as $deal) {
            $deal->update([
                'margin_agreed' => fake()->randomFloat(2, 5, 40),
            ]);
        }
    }

    /**
     * Simulate export
     */
    private function simulateExport(User $user): void
    {
        $query = Deal::query()
            ->with(['contacts', 'companies', 'user'])
            ->visibleTo($user);

        if (fake()->boolean(50)) {
            $query->where('stage', fake()->randomElement(['doc sent', 'doc signed', 'compliant']));
        }

        $query->count();

        // Simulate export processing time
        usleep(random_int(10000, 50000));
    }
}
