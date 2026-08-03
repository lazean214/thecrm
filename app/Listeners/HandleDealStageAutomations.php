<?php

namespace App\Listeners;

use App\Events\DealStageChanged;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\NotifyFinanceJob;
use App\Jobs\ProvisionUserJob;
use App\Jobs\SendSignableContractJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleDealStageAutomations implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(DealStageChanged $event): void
    {
        $deal = $event->deal;
        $newStage = $event->newStage;

        Log::info('DealStageAutomations: Processing stage change', [
            'deal_id' => $deal->id,
            'old_stage' => $event->oldStage,
            'new_stage' => $newStage,
        ]);

        match ($newStage) {
            'doc sent' => SendSignableContractJob::dispatch($deal),
            'compliant' => GenerateInvoiceJob::dispatch($deal),
            'ready for payment' => NotifyFinanceJob::dispatch($deal),
            'paid' => ProvisionUserJob::dispatch($deal),
            default => null,
        };
    }
}
