<?php

namespace App\Enums;

enum DocumentType: string
{
    case TC = 'tc';
    case ET = 'et';
    case PJ = 'pj';
    case PO = 'po';
    case CI = 'ci';

    public function label(): string
    {
        return match ($this) {
            self::TC => 'TC',
            self::ET => 'ET',
            self::PJ => 'PJ',
            self::PO => 'PO',
            self::CI => 'CI',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::TC => 'Termo de Execução Cultural',
            self::ET => 'Extrato',
            self::PJ => 'Parecer Jurídico',
            self::PO => 'Despacho',
            self::CI => 'Comunicação Interna',
        };
    }

    public function phase(): DocumentPhase
    {
        return match ($this) {
            self::CI => DocumentPhase::OPENING,
            self::TC,
            self::ET,
            self::PJ => DocumentPhase::FORMALIZATION,
            self::PO => DocumentPhase::JURIDICAL,
        };
    }
}
