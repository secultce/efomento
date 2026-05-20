<?php

namespace App\Services;

use App\Enums\ProjectStageStatus;
use App\Models\ProjectStage;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProjectStageService
{
    public function __construct(
        private Notify $notify
    ) {}

    public function advance(ProjectStage $stage, User $user): void
    {
        if (! $user->hasAnyRole($stage->responsible_sector)) {
            throw new AuthorizationException(
                'Você não tem permissão para tramitar esta etapa.'
            );
        }

        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new InvalidArgumentException(
                'A etapa precisa estar em andamento para ser aprovada.'
            );
        }

        if (! $stage->canAdvance()) {
            throw new InvalidArgumentException(
                'Não é possível avançar: existem etapas anteriores não aprovadas.'
            );
        }
        $previousStage = $stage;

        $stage->markApproved();

        $next = $stage->getNextStage();

        if ($next) {
            $next->update([
                'status' => ProjectStageStatus::EM_ANDAMENTO,
                'started_at' => now(),
            ]);

            $previousName = str($previousStage->slug->value)
                ->replace('_', ' ')
                ->title();

            $nextName = str($next->slug->value)
                ->replace('_', ' ')
                ->title();

            $message = sprintf(
                '%s tramitou o projeto %s de %s para %s.',
                $user->name,
                $stage->project->title_project,
                $previousName,
                $nextName
            );

            $title = sprintf(
                'Projeto %s Atualizado',
                $stage->project->title_project,
            );

            $this->notify
                ->allUsers()
                ->info(
                    $message,
                    $title,
                    (object) [
                        'route' => 'notices.projects.show',
                        'params' => [
                            'notice' => $stage->project->notice_id,
                            'project' => $stage->project->id,
                        ],
                        'user' => [
                            'name' => $user->name,
                            'avatar' => $user->profile_picture,
                        ],
                    ]
                );
        }
    }

    public function reject(ProjectStage $stage, string $reason, User $user): void
    {
        if (! $user->hasAnyRole($stage->responsible_sector)) {
            throw new AuthorizationException(
                'Você não tem permissão para tramitar esta etapa.'
            );
        }

        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new InvalidArgumentException(
                'A etapa precisa estar em andamento para ser rejeitada.'
            );
        }

        $stage->markRejected($reason);

        $stage->project->stages()
            ->where('order', '>', $stage->order)
            ->update(['status' => ProjectStageStatus::BLOQUEADO->value]);
    }

    public function returnStage(ProjectStage $stage, string $reason, User $user): ProjectStage
    {

        if (! $user->hasAnyRole($stage->responsible_sector)) {
            throw new AuthorizationException('Você não tem permissão para devolver esta etapa.');
        }

        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new InvalidArgumentException('A etapa precisa estar em andamento para ser devolvida.');
        }

        $previousStage = $stage->getPreviousStage();
        if (! $previousStage) {
            throw new InvalidArgumentException('Não há etapa anterior para devolução.');
        }

        DB::transaction(function () use ($stage, $reason, $previousStage) {
            $stage->markRejected($reason);
            $stage->project->stages()
                ->where('order', '>', $stage->order)
                ->update(['status' => ProjectStageStatus::BLOQUEADO->value]);
            $previousStage->update([
                'status' => ProjectStageStatus::EM_ANDAMENTO,
                'started_at' => now(),
                'concluded_at' => null,
            ]);
        });

        return $previousStage->fresh();
    }
}
