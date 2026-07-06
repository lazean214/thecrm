<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealStageStaleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Deal $deal,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmailNotification('deal_stage_stale')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Deal Stuck: {$this->deal->name}")
            ->line('Deal has been in Doc Sent for over 24 hours.')
            ->action('View Deal', route('deals.show', $this->deal->id));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'deal_stage_stale',
            'title' => "Deal Stuck: {$this->deal->name}",
            'message' => 'Deal has been in Doc Sent for over 24 hours.',
            'deal_id' => $this->deal->id,
            'url' => route('deals.show', $this->deal->id),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
