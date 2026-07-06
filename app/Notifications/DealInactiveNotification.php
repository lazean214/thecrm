<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealInactiveNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Deal $deal,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmailNotification('deal_inactive')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Inactive Deal: {$this->deal->name}")
            ->line("Deal {$this->deal->name} ({$this->deal->stage?->value}) has not been updated in over 24 hours.")
            ->action('View Deal', route('deals.show', $this->deal->id));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'deal_inactive',
            'title' => "Inactive Deal: {$this->deal->name}",
            'message' => "Deal {$this->deal->name} ({$this->deal->stage?->value}) has not been updated in over 24 hours.",
            'deal_id' => $this->deal->id,
            'url' => route('deals.show', $this->deal->id),
        ];
    }
}
