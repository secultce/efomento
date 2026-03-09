<?php

namespace App\Services;

use App\Models\Opportunity;
use Illuminate\Support\Collection;

class OpportunityService
{
    /**
     * Retorna as oportunidades formatadas para exibição no dashboard de editais.
     * Aplica filtro de busca por nome ou NUP quando informado.
     */
    public function getOpportunitiesForDashboard(?string $search = null): Collection
    {
        return Opportunity::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nup', 'ilike', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Opportunity $opportunity) => [
                'id'      => $opportunity->id,
                'titulo'  => $opportunity->name,
                'mae'     => $opportunity->nup,
                'type_ins' => $opportunity->instrument_type,
                'status'  => $opportunity->creditor_registration_request_date !== null,
                'url'     => $opportunity->opportunity_url,
            ]);
    }

    /**
     * Retorna os totais para os cards de estatísticas do dashboard.
     */
    public function getTotals(): array
    {
        $total = Opportunity::count();
        $comCredenciamento = Opportunity::whereNotNull('creditor_registration_request_date')->count();
        $semCredenciamento = Opportunity::whereNull('creditor_registration_nup')->count();
        return [
            'oportunidades' => $total,
            'pendentes'     => $semCredenciamento,
            'concluidos'    => $comCredenciamento,
            'monitoramento' => $total - $comCredenciamento - $semCredenciamento,
        ];
    }
}
