<?php

namespace App\Services;

use App\Models\Notice;
use App\Models\Project;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class NoticeService
{
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

    private function resolveStatus(Notice $notice): string
    {
        if (! $notice->nup) {
            return 'Pendente de abertura';
        }

        $allFormalizations = $notice->projects->isNotEmpty()
            && $notice->projects->every(fn (Project $project) => $project->hasCompletedPayment());

        return $allFormalizations
            ? 'Processos formalizados'
            : 'Processos em andamento';
    }

    /**
     * Retorna os totais para os cards de estatísticas do dashboard.
     */
    public function getTotals(): array
    {
        $statusCounts = Notice::query()
            ->with(['projects.stages'])
            ->get()
            ->countBy(fn (Notice $notice) => $this->resolveStatus($notice));

        $monitoramento = Notice::whereNotNull('creditor_registration_request_date')
            ->whereNull('creditor_registration_nup')
            ->count();

        return [
            'oportunidades' => $statusCounts->get('Processos em andamento', 0),
            'pendentes' => $statusCounts->get('Pendente de abertura', 0),
            'concluidos' => $statusCounts->get('Processos formalizados', 0),
            'monitoramento' => $monitoramento,
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
