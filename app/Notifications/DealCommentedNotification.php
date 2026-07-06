<?php

namespace App\Notifications;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class DealCommentedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ActivityLog $comment
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmailNotification('deal_commented')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel = $this->comment->activity_name ?? 'Comment';

        return (new MailMessage)
            ->subject("New {$typeLabel} Received")
            ->line($this->comment->user_email.' commented:')
            ->line(Str::limit($this->comment->message, 200))
            ->action('View Deal', url("/deals/{$this->comment->deal_id}"));
    }

    /**
     * Store formatted payload for ⚡notifications-dropdown.blade.php
     */
    public function toDatabase(object $notifiable): array
    {
        $typeLabel = $this->comment->activity_name ?? 'Comment';

        return [
            'type' => 'deal_commented',
            'title' => "New {$typeLabel} Received",
            'message' => "{$this->comment->user_email}: ".Str::limit($this->comment->message, 60),
            'deal_id' => $this->comment->deal_id,
            'url' => url("/deals/{$this->comment->deal_id}"),
        ];
    }

    /**
     * Fallback array mapping
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
