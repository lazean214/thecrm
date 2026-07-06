<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealReadyForPaymentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Deal $deal,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmailNotification('deal_ready_for_payment')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Ready: {$this->deal->name}")
            ->line("Deal {$this->deal->name} (£".number_format((float) $this->deal->amount, 2).') is now ready for payment.')
            ->action('View Deal', route('deals.show', $this->deal->id));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'deal_ready_for_payment',
            'title' => "Payment Ready: {$this->deal->name}",
            'message' => "Deal {$this->deal->name} (£".number_format((float) $this->deal->amount, 2).') is now ready for payment.',
            'deal_id' => $this->deal->id,
            'url' => route('deals.show', $this->deal->id),
        ];
    }
}
