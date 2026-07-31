<?php

namespace App\Services;

use App\Contracts\InstallmentCycleStrategy;
use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Exceptions\Domain\BusinessRuleException;
use App\Exceptions\Domain\DomainAuthorizationException;
use App\Exceptions\Domain\StageTransitionException;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;

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
        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new StageTransitionException(
                'A etapa precisa estar em andamento para ser aprovada.'
            );
        }

        $this->ensureUserHasRole($stage, $user, 'Você não tem permissão para tramitar esta etapa.');

        if ($stage->slug === ProjectStageSlug::MONITORAMENTO) {
            $this->ensureIsPrincipalSupervisor($stage->project, $user);
        }

        if (! $stage->canAdvance()) {
            throw new StageTransitionException(
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
        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new StageTransitionException(
                'A etapa precisa estar em andamento para ser rejeitada.'
            );
        }

        $this->ensureUserHasRole($stage, $user, 'Você não tem permissão para tramitar esta etapa.');

        $stage->markRejected($reason);

        $stage->project->stages()
            ->where('order', '>', $stage->order)
            ->update(['status' => ProjectStageStatus::BLOQUEADO->value]);
    }

    public function requestNextInstallment(Project $project, User $user): void
    {
        $this->ensureIsPrincipalSupervisor($project, $user);

        $notice = $project->notice;

        if (! $notice || $notice->installments <= 1) {
            throw new BusinessRuleException('Projeto não possui múltiplas parcelas.');
        }

        if ($project->current_installment_cycle >= $notice->installments) {
            throw new BusinessRuleException('Todos os ciclos de parcelas já foram concluídos.');
        }

        $monitoringStage = $project->stages()
            ->where('slug', ProjectStageSlug::MONITORAMENTO)
            ->firstOrFail();

        if ($monitoringStage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new StageTransitionException('A etapa de Monitoramento precisa estar em andamento.');
        }

        DB::transaction(function () use ($project, $monitoringStage) {
            $monitoringStage->markApproved();

            $project->increment('current_installment_cycle');

            $project->stages()
                ->whereIn('slug', $this->cycleStrategy->stagesToReset())
                ->update(['status' => ProjectStageStatus::BLOQUEADO]);

            $project->stages()
                ->where('slug', $this->cycleStrategy->activationStage())
                ->update(['status' => ProjectStageStatus::EM_ANDAMENTO]);
        });
    }

    public function returnStage(ProjectStage $stage, string $reason, User $user): ProjectStage
    {
        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new StageTransitionException('A etapa precisa estar em andamento para ser devolvida.');
        }

        $this->ensureUserHasRole($stage, $user, 'Você não tem permissão para devolver esta etapa.');

        $previousStage = $stage->getPreviousStage();
        if (! $previousStage) {
            throw new BusinessRuleException('Não há etapa anterior para devolução.');
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

    private function ensureIsPrincipalSupervisor(Project $project, User $user): void
    {
        if (! $project->opening?->isPrincipalSupervisor($user)) {
            throw new DomainAuthorizationException(
                'Apenas o fiscal titular pode executar esta ação.'
            );
        }
    }

    private function ensureUserHasRole(ProjectStage $stage, User $user, string $message): void
    {
        if (! $user->hasAnyRole($stage->responsible_sector)) {
            throw new DomainAuthorizationException($message);
        }
    }
}
