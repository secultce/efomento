<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectService
{
    /**
     * Retorna os projetos formatados para exibição no dashboard de editais.
     * Aplica filtro de busca por nome ou NUP quando informado.
     */
    public function getProjetosParaDashboard(?string $search = null): Collection
    {
        return Project::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nup', 'ilike', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Project $project) => [
                'id'      => $project->id,
                'titulo'  => $project->name,
                'mae'     => $project->nup,
                'type_ins' => $project->total_project_amount,
                'status'  => $project->creditor_registration_request_date !== null,
                'url'     => $project->project_url,
            ]);
    }

    /**
     * Retorna os totais para os cards de estatísticas do dashboard.
     */
    public function getTotais(): array
    {
        $total = Project::count();
        $comCredenciamento = Project::whereNotNull('creditor_registration_request_date')->count();
        $semCredenciamento = Project::whereNull('creditor_registration_nup')->count();

        return [
            'projetos'      => $total,
            'pendentes'     => $semCredenciamento,
            'concluidos'    => $comCredenciamento,
            'monitoramento' => $total - $comCredenciamento - $semCredenciamento,
        ];
    }
}
