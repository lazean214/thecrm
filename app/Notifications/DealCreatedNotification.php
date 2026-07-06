<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealCreatedNotification extends Notification
{
    public function __construct(
        public readonly Deal $deal
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmailNotification('deal_created')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Deal Created: {$this->deal->name}")
            ->line("A new deal '{$this->deal->name}' has been created by {$this->deal->user->name}.")
            ->action('View Deal', route('deals.show', $this->deal));
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
