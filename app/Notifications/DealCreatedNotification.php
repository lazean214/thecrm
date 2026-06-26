<?php

namespace App\Notifications;

use App\Models\Deal;
use Illuminate\Notifications\Notification;

class DealCreatedNotification extends Notification
{
    public function __construct(
        public readonly Deal $deal
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Database notification payload
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'deal_created',
            'title' => 'New Deal Created',
            'message' => "A new deal '{$this->deal->name}' has been created by {$this->deal->user->name}.",
            'deal_id' => $this->deal->id,
            'url' => route('deals.show', $this->deal),
        ];
    }

    /**
     * Array representation
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
