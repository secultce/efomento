<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum ProjectStageStatus: string implements HasLabel
{
    use HasOptions;

    case PENDENTE = 'pendente';
    case EM_ANDAMENTO = 'em_andamento';
    case APROVADO = 'aprovado';
    case REJEITADO = 'rejeitado';
    case BLOQUEADO = 'bloqueado';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::EM_ANDAMENTO => 'Em andamento',
            self::APROVADO => 'Aprovado',
            self::REJEITADO => 'Rejeitado',
            self::BLOQUEADO => 'Bloqueado',
        };
    }
}
