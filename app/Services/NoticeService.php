<?php

namespace App\Services;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Notice;
use App\Models\Project;
use App\Models\ProjectStage;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class NoticeService
{
    /**
     * Retorna as oportunidades formatadas para exibição no dashboard de editais.
     * Aplica filtro de busca por nome ou NUP quando informado.
     */
    public function getNoticesForDashboard(?string $search = null): Collection
    {
        return Notice::query()
            ->with(['projects.stages'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nup', 'ilike', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Notice $notice) => [
                'id' => $notice->id,
                'titulo' => $notice->name,
                'mae' => $notice->nup,
                'type_ins' => $notice->instrument_type,
                'status' => $this->resolveStatus($notice),
                'url' => $notice->notice_url,
            ]);
    }

    /**
     * Calcula o status do edital com base no preenchimento do NUP e no
     * progresso dos projetos vinculados até a etapa de pagamento.
     */
    private function resolveStatus(Notice $notice): string
    {
        if (! $notice->nup) {
            return 'Pendente abertura de processo';
        }

        $todosEmPagamento = $notice->projects->isNotEmpty()
            && $notice->projects->every(
                fn (Project $project) => $project->stages->contains(
                    fn (ProjectStage $stage) => $stage->slug === ProjectStageSlug::PAGAMENTO
                        && $stage->status === ProjectStageStatus::EM_ANDAMENTO
                )
            );

        return $todosEmPagamento
            ? 'Processos formalizados'
            : 'Em abertura de processo';
    }

    /**
     * Retorna os totais para os cards de estatísticas do dashboard.
     */
    public function getTotals(): array
    {
        $total = Notice::count();
        $comCredenciamento = Notice::whereNotNull('creditor_registration_request_date')->count();
        $semCredenciamento = Notice::whereNull('creditor_registration_nup')->count();

        return [
            'oportunidades' => $total,
            'pendentes' => $semCredenciamento,
            'concluidos' => $comCredenciamento,
            'monitoramento' => $total - $comCredenciamento - $semCredenciamento,
        ];
    }

    public function createFromMapasIfMissing(array $notice): Notice
    {
        $externalId = data_get($notice, 'id');

        if (! $externalId) {
            throw new InvalidArgumentException('Edital sem id externo.');
        }

        return Notice::query()->firstOrCreate(
            [
                'external_id' => $externalId,
            ],
            [
                'name' => data_get($notice, 'name') ?: 'Edital sem nome',
                'notice_url' => data_get($notice, 'singleUrl'),
            ]
        );
    }
}
