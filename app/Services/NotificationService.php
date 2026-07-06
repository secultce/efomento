<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use App\Support\Mask;
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
            '%s tramitou o projeto %s de NUP %s da fase de %s para a fase de %s.',
            $user->name,
            $previousStage->project->title_project,
            Mask::nup($previousStage->project->opening?->opening_nup),
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

    public function notifyProcessReturned(
        Project $project,
        string $reason,
        array $roles,
        User $user
    ): void {
        $users = User::role($roles)->get();

        $message = sprintf(
            '%s devolveu o processo "%s" de NUP %s para ajustes.',
            $user->name,
            $project->title_project,
            Mask::nup($project->opening?->opening_nup)
        );

        $title = sprintf(
            'Processo %s devolvido',
            $project->title_project
        );

        $this->notify
            ->users($users)
            ->warning(
                $message,
                $title,
                (object) [
                    'reason' => $reason,
                    'route' => 'notices.projects.show',
                    'params' => [
                        'notice' => $project->notice_id,
                        'project' => $project->id,
                    ],
                    'user' => [
                        'name' => $user->name,
                        'avatar' => $user->profile_picture,
                    ],
                ]
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
