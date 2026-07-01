<?php

namespace App\Notifications;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
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
        return ['database'];
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
