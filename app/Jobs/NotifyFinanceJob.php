<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Models\User;
use App\Notifications\DealReadyForPaymentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyFinanceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public Deal $deal,
    ) {}

    public function handle(): void
    {
        $deal = $this->deal;

        Log::info('NotifyFinanceJob: Processing', [
            'deal_id' => $deal->id,
            'deal_name' => $deal->name,
            'amount' => $deal->amount,
        ]);

        // Notify admin users (finance function managed by admins)
        User::role('admin')->each(fn (User $admin) => $admin->notify(new DealReadyForPaymentNotification($deal)));

        // Also notify the deal owner
        if ($deal->user) {
            $deal->user->notify(new DealReadyForPaymentNotification($deal));
        }

        Log::info('NotifyFinanceJob: Finance team notified', [
            'deal_id' => $deal->id,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('NotifyFinanceJob: Failed permanently', [
            'deal_id' => $this->deal->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
