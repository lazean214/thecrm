<?php

namespace App\Observers;

use App\Models\Deal;
use App\Notifications\DealCreatedNotification;

class DealObserver
{
    /**
     * Handle the Deal "created" event.
     * Notifies the deal owner when a new deal is created.
     */
    public function created(Deal $deal): void
    {
        // Stamp initial stage timestamp without triggering observer loop
        $deal->timestamps = false;
        $deal->updateQuietly(['stage_updated_at' => now()]);
        $deal->timestamps = true;

        $deal->load('user');

        if ($deal->user) {
            $deal->user->notify(new DealCreatedNotification($deal));
        }
    }

    /**
     * Handle the Deal "updated" event.
     * Stamps stage_updated_at and notifies on stage changes.
     */
    public function updated(Deal $deal): void
    {
        if ($deal->wasChanged('stage')) {
            // Stamp the stage change time without triggering observer loop
            $deal->timestamps = false;
            $deal->updateQuietly(['stage_updated_at' => now()]);
            $deal->timestamps = true;
        }
    }
}
