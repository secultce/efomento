<?php

namespace App\Enums;

enum DocumentType: string
{
    case TC = 'tc';
    case ET = 'et';
    case PJ = 'pj';
    case DISPATCH = 'dispatch';
    case CI = 'ci';

    public function label(): string
    {
        return match ($this) {
            self::TC => 'TC',
            self::ET => 'ET',
            self::PJ => 'PJ',
            self::DISPATCH => 'PO',
            self::CI => 'CI',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::TC => 'Termo de Execução Cultural',
            self::ET => 'Extrato',
            self::PJ => 'Parecer Jurídico',
            self::DISPATCH => 'Despacho',
            self::CI => 'Comunicação Interna',
        };
    }
}
