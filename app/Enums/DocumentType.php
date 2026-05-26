<?php

namespace App\Enums;

enum DocumentType: string
{
    case TC = 'tc';
    case EXTRACT = 'extract';
    case JURIDICAL_OPINION = 'juridical_opinion';
    case DISPATCH = 'dispatch';
    case CI = 'ci';

    public function label(): string
    {
        return match ($this) {
            self::TC => 'TC',
            self::EXTRACT => 'EX',
            self::JURIDICAL_OPINION => 'PJ',
            self::DISPATCH => 'PO',
            self::CI => 'CI',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::TC => 'Termo de Execução Cultural',
            self::EXTRACT => 'Extrato',
            self::JURIDICAL_OPINION => 'Parecer Jurídico',
            self::DISPATCH => 'Despacho',
            self::CI => 'Comunicação Interna',
        };
    }
}
