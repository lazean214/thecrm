<?php

namespace App\Notifications;

use App\Models\Deal;
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deal = $this->deal;
        $stageLabel = ucwords($deal->stage?->value ?? 'unknown');
        $lastUpdate = $deal->stage_updated_at?->diffForHumans() ?? 'unknown';
        $amount = number_format((float) $deal->amount, 2);

        return (new MailMessage)
            ->subject("Action Required: Deal \"{$deal->name}\" Has Not Been Updated")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your deal **{$deal->name}** has not been updated in over 24 hours.")
            ->line("**Stage:** {$stageLabel}")
            ->line("**Amount:** £{$amount}")
            ->line("**Last updated:** {$lastUpdate}")
            ->action('Review Deal', route('deals.show', $deal->id))
            ->line('Please review and take action to keep this deal moving through the pipeline.');
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
