<?php

namespace App\Notifications;

use App\Models\Deal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealPaidNotification extends Notification
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
        $amount = number_format((float) $this->deal->amount, 2);

        return (new MailMessage)
            ->subject("Payment Received: {$this->deal->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Payment has been received for deal **{$this->deal->name}**.")
            ->line("**Amount:** £{$amount}")
            ->action('View Deal', route('deals.show', $this->deal->id))
            ->line('This deal is now complete.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'deal_paid',
            'title' => "Payment Received: {$this->deal->name}",
            'message' => 'Payment of £'.number_format((float) $this->deal->amount, 2)." received for {$this->deal->name}.",
            'deal_id' => $this->deal->id,
            'url' => route('deals.show', $this->deal->id),
        ];
    }
}
