<?php

namespace App\Observers;

use App\Models\Deal;
use App\Notifications\DealCreatedNotification;
use Illuminate\Support\Facades\Log;

class DealObserver
{
    /**
     * Handle the Deal "created" event.
     * Notifies the deal owner when a new deal is created.
     */
    public function created(Deal $deal): void
    {
        try {
            // Stamp initial stage timestamp without triggering observer loop
            $deal->timestamps = false;
            $deal->updateQuietly(['stage_updated_at' => now()]);
            $deal->timestamps = true;
        } catch (\Exception $e) {
            Log::error("Failed to stamp stage_updated_at for deal {$deal->id}: {$e->getMessage()}");
        }

        try {
            $deal->load('user');

            if ($deal->user) {
                $deal->user->notify(new DealCreatedNotification($deal));
            }
        } catch (\Exception $e) {
            Log::error("Failed to send deal created notification for deal {$deal->id}: {$e->getMessage()}");
        }
    }

    /**
     * Handle the Deal "updated" event.
     * Stamps stage_updated_at and notifies on stage changes.
     */
    public function updated(Deal $deal): void
    {
        if ($deal->wasChanged('stage')) {
            try {
                // Stamp the stage change time without triggering observer loop
                $deal->timestamps = false;
                $deal->updateQuietly(['stage_updated_at' => now()]);
                $deal->timestamps = true;
            } catch (\Exception $e) {
                Log::error("Failed to stamp stage_updated_at for deal {$deal->id}: {$e->getMessage()}");
            }
        }
    }
}
