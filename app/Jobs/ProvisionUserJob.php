<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Services\ProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProvisionUserJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Deal $deal,
    ) {}

    public function handle(ProvisioningService $provisioning): void
    {
        Log::info('ProvisionUserJob: Processing', [
            'deal_id' => $this->deal->id,
            'deal_name' => $this->deal->name,
        ]);

        $provisioning->provisionWorker($this->deal);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ProvisionUserJob: Failed permanently', [
            'deal_id' => $this->deal->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
