<?php

namespace App\Notifications;

use App\Models\Deal;
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hours = (int) now()->diffInHours($this->deal->stage_updated_at);

        return (new MailMessage)
            ->subject("Action Required: Deal \"{$this->deal->name}\" Stuck in Doc Sent")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your deal has been sitting in the **Doc Sent** stage for over **{$hours} hours** without progressing.")
            ->line("**Deal:** {$this->deal->name}")
            ->line('**Amount:** £'.number_format($this->deal->amount ?? 0, 0))
            ->line('**Stage:** Doc Sent')
            ->line("**Last Stage Change:** {$this->deal->stage_updated_at->format('d M Y, H:i')}")
            ->action('View Deal', route('deals.show', $this->deal->id))
            ->line('Please follow up with the contact to progress this deal.');
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
