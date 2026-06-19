<?php

namespace App\Services;

use App\Contracts\InstallmentCycleStrategy;
use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProjectStageService
{
    public function __construct(
        private Notify $notify,
        private InstallmentCycleStrategy $cycleStrategy,
    ) {}

    public function advance(
        ProjectStage $stage,
        User $user
    ): ?ProjectStage {
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

        return $next?->fresh();
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

    public function requestNextInstallment(Project $project, User $user): void
    {
        $notice = $project->notice;

        if (! $notice || $notice->installments <= 1) {
            throw new InvalidArgumentException('Projeto não possui múltiplas parcelas.');
        }

        if ($project->current_installment_cycle >= $notice->installments) {
            throw new InvalidArgumentException('Todos os ciclos de parcelas já foram concluídos.');
        }

        $monitoringStage = $project->stages()
            ->where('slug', ProjectStageSlug::MONITORAMENTO)
            ->firstOrFail();

        if ($monitoringStage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new InvalidArgumentException('A etapa de Monitoramento precisa estar em andamento.');
        }

        DB::transaction(function () use ($project, $monitoringStage) {
            $monitoringStage->markApproved();

            $project->increment('current_installment_cycle');

            $project->stages()
                ->whereIn('slug', $this->cycleStrategy->stagesToReset())
                ->update([
                    'status' => ProjectStageStatus::BLOQUEADO,
                    'started_at' => null,
                    'concluded_at' => null,
                    'rejection_reason' => null,
                ]);

            $project->stages()
                ->where('slug', $this->cycleStrategy->activationStage())
                ->update([
                    'status' => ProjectStageStatus::EM_ANDAMENTO,
                    'started_at' => now(),
                ]);
        });
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
