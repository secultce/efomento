<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function __construct(private Notify $notify) {}

    public function notifyStageAdvanced(
        ProjectStage $previousStage,
        ?ProjectStage $nextStage,
        User $user
    ): void {
        if (! $nextStage) {
            return;
        }

        $previousName = str($previousStage->slug->value)
            ->replace('_', ' ')
            ->title();

        $nextName = str($nextStage->slug->value)
            ->replace('_', ' ')
            ->title();

        $message = sprintf(
            '%s tramitou o projeto %s de %s para %s.',
            $user->name,
            $previousStage->project->title_project,
            $previousName,
            $nextName
        );

        $title = sprintf(
            'Projeto %s Atualizado',
            $previousStage->project->title_project
        );

        $this->notify
            ->allUsers()
            ->info(
                $message,
                $title,
                (object) [
                    'route' => 'notices.projects.show',
                    'params' => [
                        'notice' => $previousStage->project->notice_id,
                        'project' => $previousStage->project->id,
                    ],
                    'user' => [
                        'name' => $user->name,
                        'avatar' => $user->profile_picture,
                    ],
                ]
            );
    }

    public function notifyProcessReturned(Project $project, string $reason, array $roles): void
    {
        $users = User::role($roles)->get();

        $this->notify->users($users)->warning(
            'O processo "'.$project->title_project.'" foi devolvido para ajustes.',
            'Processo devolvido',
            (object) ['reason' => $reason]
        );
    }

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

        if (! empty($filters['type'])) {
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

        if (! $notification) {
            return false;
        }

        if (! $notification->read_at) {
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
