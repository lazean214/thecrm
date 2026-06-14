<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Notifications\DealInactiveNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckInactiveDeals extends Command
{
    protected $signature = 'deals:check-inactive';

    protected $description = 'Notify deal owners when their deal has not been touched in 24 hours';

    public function handle(): void
    {
        $inactiveDeals = Deal::query()
            ->with('user')
            ->where('stage_updated_at', '<=', Carbon::now()->subHours(24))
            ->get();

        $notified = 0;

        foreach ($inactiveDeals as $deal) {
            if (! $deal->user) {
                continue;
            }

            // Deduplication: skip if already notified within the last 24 hours
            $recentNotification = $deal->user
                ->notifications()
                ->where('type', 'deal_inactive')
                ->where('data->deal_id', (string) $deal->id)
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->exists();

            if ($recentNotification) {
                continue;
            }

            $deal->user->notify(new DealInactiveNotification($deal));
            $notified++;
        }

        $this->info("Notified {$notified} deal owner(s) of inactive deals.");
    }
}
