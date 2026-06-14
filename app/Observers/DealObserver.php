<?php

namespace App\Observers;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use App\Notifications\DealCreatedNotification;
use App\Notifications\DealPaidNotification;
use App\Notifications\DealReadyForPaymentNotification;
use App\Notifications\DealStageMovedNotification;
use Illuminate\Support\Facades\Notification;

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

            $oldStage = $deal->getOriginal('stage');
            $newStage = $deal->stage;

            // Convert enum to string safely
            $oldStage = $oldStage instanceof DealStage
                ? $oldStage->value
                : $oldStage;

            $newStage = $newStage instanceof DealStage
                ? $newStage->value
                : $newStage;

            // Get compliance team users (matching the actual team name)
            $complianceUsers = User::whereHas('teams', function ($query) {
                $query->where('name', 'Compliance Team');
            })->get();

            // Build recipients: compliance team + deal owner
            $recipients = $complianceUsers;

            if ($deal->user) {
                $recipients->push($deal->user);
            }

            $recipients = $recipients->unique('id');

            // Always send stage moved notification (database only)
            Notification::send(
                $recipients,
                new DealStageMovedNotification($deal, $oldStage, $newStage),
            );

            // Additional notifications for specific stages
            if ($newStage === DealStage::READY_FOR_PAYMENT->value) {
                if ($deal->user) {
                    $deal->user->notify(new DealReadyForPaymentNotification($deal));
                }
            }

            if ($newStage === DealStage::PAID->value) {
                if ($deal->user) {
                    $deal->user->notify(new DealPaidNotification($deal));
                }
            }
        }
    }
}
