<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NoticeService
{
    public function getUserNotifications(
        User $user,
        array $filters = [],
        int $pagination = 5
    ): array {
        $query = $user->notifications()->latest();

        if (isset($filters['read'])) {
            $filters['read']
                ? $query->whereNotNull('read_at')
                : $query->whereNull('read_at');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $notifications = $query->paginate($pagination);

        return [
            'userNotifications' => $notifications->items(),
            'page' => $notifications->currentPage(),
            'pageCount' => $notifications->lastPage(),
            'total' => $notifications->total(),
            'unreadNotificationsCount' => $user->unreadNotifications()->count(),
        ];
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $this->findNotification($user, $notificationId);

        if (!$notification) {
            return false;
        }

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return true;
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    private function findNotification(
        User $user,
        string $notificationId
    ): ?DatabaseNotification {
        return $user->notifications()
            ->where('id', $notificationId)
            ->first();
    }
}