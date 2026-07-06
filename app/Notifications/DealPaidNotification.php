<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\User;
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
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmailNotification('deal_paid')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Received: {$this->deal->name}")
            ->line('Payment of £'.number_format((float) $this->deal->amount, 2)." received for {$this->deal->name}.")
            ->action('View Deal', route('deals.show', $this->deal->id));
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
