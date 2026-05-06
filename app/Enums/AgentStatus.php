<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum AgentStatus: string implements HasLabel
{
    use HasOptions;

    case ATIVO = 'ATIVO';
    case SUPLENTE = 'SUPLENTE';
    case EM_ANALISE = 'EM_ANALISE';
    case DESCLASSIFICADO = 'DESCLASSIFICADO';
    case DESISTENTE = 'DESISTENTE';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::SUPLENTE => 'Ativo (suplente)',
            self::EM_ANALISE => 'Em análise',
            self::DESCLASSIFICADO => 'Desclassificado',
            self::DESISTENTE => 'Desistente',
        };
    }
    
}
