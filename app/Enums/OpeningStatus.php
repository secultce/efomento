<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum OpeningStatus: string implements HasLabel
{
    use HasOptions;

    case PENDENTE = 'PENDENTE';
    case EM_ANDAMENTO = 'EM_ANDAMENTO';
    case EM_ANALISE = 'EM_ANALISE';
    case CONCLUIDO = 'CONCLUIDO';
    case REJEITADO = 'REJEITADO';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::EM_ANDAMENTO => 'Em andamento',
            self::EM_ANALISE => 'Em análise',
            self::CONCLUIDO => 'Concluído',
            self::REJEITADO => 'Rejeitado',
        };
    }
}
