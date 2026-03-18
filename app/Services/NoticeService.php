<?php

namespace App\Services;

use App\Models\Notice;
use Illuminate\Support\Collection;

class NoticeService
{
    /**
     * Retorna as oportunidades formatadas para exibição no dashboard de editais.
     * Aplica filtro de busca por nome ou NUP quando informado.
     */
    public function getNoticesForDashboard(?string $search = null): Collection
    {
        return Notice::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nup', 'ilike', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Notice $notice) => [
                'id'      => $notice->id,
                'titulo'  => $notice->name,
                'mae'     => $notice->nup,
                'type_ins' => $notice->instrument_type,
                'status'  => $notice->nup? 'Em abertura de processo': 'Pendente abertura de processo',
                'url'     => $notice->notice_url,
            ]);
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
            'pendentes'     => $semCredenciamento,
            'concluidos'    => $comCredenciamento,
            'monitoramento' => $total - $comCredenciamento - $semCredenciamento,
        ];
    }
}
