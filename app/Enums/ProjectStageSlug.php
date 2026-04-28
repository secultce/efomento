<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\HasLabel;

enum ProjectStageSlug: string implements HasLabel
{
    use HasOptions;

    case ABERTURA = 'abertura';
    case ANALISE_JURIDICA = 'analise_juridica';
    case FORMALIZACAO = 'formalizacao';
    case ORCAMENTO = 'orcamento';
    case PAGAMENTO = 'pagamento';
    case MONITORAMENTO = 'monitoramento';

    public function label(): string
    {
        return match ($this) {
            self::ABERTURA => 'Abertura',
            self::ANALISE_JURIDICA => 'Análise Jurídica',
            self::FORMALIZACAO => 'Formalização',
            self::ORCAMENTO => 'Orçamento',
            self::PAGAMENTO => 'Pagamento',
            self::MONITORAMENTO => 'Monitoramento',
        };
    }
}
