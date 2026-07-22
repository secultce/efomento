<?php

namespace App\Enums;

enum DocumentType: string
{
    case CI = 'ci';
    case TC = 'tc';
    case ET = 'et';
    case PJ = 'pj';
    case DO = 'do';
    case DP = 'dp';

    public function label(): string
    {
        return match ($this) {
            self::CI => 'CI',
            self::TC => 'TC',
            self::ET => 'ET',
            self::PJ => 'PJ',
            self::DO => 'DO',
            self::DP => 'DP',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::CI => 'Comunicação Interna',
            self::TC => 'Termo de Execução Cultural',
            self::ET => 'Extrato',
            self::PJ => 'Parecer Jurídico',
            self::DO => 'Despacho Orçamentário',
            self::DP => 'Despacho de Pagamento',
        };
    }

    public function phase(): DocumentPhase
    {
        return match ($this) {
            self::CI => DocumentPhase::OPENING,
            self::TC,
            self::ET,
            self::PJ => DocumentPhase::FORMALIZATION,
            self::DO => DocumentPhase::BUDGET,
            self::DP => DocumentPhase::PAYMENT,
        };
    }

    public static function requiredForFormalizationAdvance(): array
    {
        return [
            self::TC,
            self::ET,
            self::PJ,
        ];
    }
}
