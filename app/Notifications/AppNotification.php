<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $message,
        public string $type = 'default', // success, error, warning, info, default
        public ?string $title = null,
        public ?array $meta = null
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title ?? ucfirst($this->type),
            'message' => $this->message,
            'type' => $this->type,
            'meta' => $this->meta,
        ];
    }
}
