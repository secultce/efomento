<?php

namespace App\Services;

use App\Enums\ProjectStageStatus;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProjectStageService
{
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

        $stage->markApproved();

        $next = $stage->getNextStage();
        if ($next) {
            $next->update([
                'status' => ProjectStageStatus::EM_ANDAMENTO,
                'started_at' => now(),
            ]);
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
