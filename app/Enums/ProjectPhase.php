<?php

namespace App\Enums;

enum ProjectPhase: string
{
    case ABERTURA = 'abertura';
    case ANALISE_JURIDICA = 'analise_juridica';
    case FORMALIZACAO = 'formalizacao';
    case ORCAMENTO_PARCELA = 'orcamento_parcela';
    case PAGAMENTOS = 'pagamentos';
    case MONITORAMENTO = 'monitoramento';

    public static function fromRequest(?string $value): ?self
    {
        return self::tryFrom(strtolower($value ?? ''));
    }

    public function applyFilter($query)
    {
        return match ($this) {
            self::ABERTURA => $query->has('openings'),
            self::ANALISE_JURIDICA,
            self::FORMALIZACAO,
            self::ORCAMENTO_PARCELA,
            self::PAGAMENTOS,
            self::MONITORAMENTO => $query,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ABERTURA => 'Abertura',
            self::ANALISE_JURIDICA => 'Análise jurídica',
            self::FORMALIZACAO => 'Formalização',
            self::ORCAMENTO_PARCELA => 'Orçamento e parcela',
            self::PAGAMENTOS => 'Pagamentos',
            self::MONITORAMENTO => 'Monitoramento',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ABERTURA => 'green',
            self::NAO_INICIADO => 'grey',
        };
    }

    public function count($query): int
    {
        return match ($this) {
            self::ABERTURA => (clone $query)->has('openings')->count(),
            self::ANALISE_JURIDICA,
            self::FORMALIZACAO,
            self::ORCAMENTO_PARCELA,
            self::PAGAMENTOS,
            self::MONITORAMENTO => 0,

        };
    }
}
