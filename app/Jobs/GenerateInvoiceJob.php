<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Models\User;
use App\Notifications\DealReadyForPaymentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateInvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Deal $deal,
    ) {}

    public function handle(): void
    {
        $deal = $this->deal;

        Log::info('GenerateInvoiceJob: Processing', [
            'deal_id' => $deal->id,
            'deal_name' => $deal->name,
            'amount' => $deal->amount,
        ]);

        // Notify the deal owner that an invoice needs to be generated
        if ($deal->user) {
            $deal->user->notify(new DealReadyForPaymentNotification($deal));
        }

        // Notify admin users
        User::role('admin')->each(fn (User $admin) => $admin->notify(new DealReadyForPaymentNotification($deal)));

        Log::info('GenerateInvoiceJob: Notifications sent for invoice generation', [
            'deal_id' => $deal->id,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('GenerateInvoiceJob: Failed permanently', [
            'deal_id' => $this->deal->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
