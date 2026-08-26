<?php

namespace Tests\Unit;

use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use PHPUnit\Framework\TestCase;

class DocumentTypeTest extends TestCase
{
    public function test_label_returns_short_label_for_every_case(): void
    {
        $expected = [
            DocumentType::TC->value => 'TC',
            DocumentType::ET->value => 'ET',
            DocumentType::PJ->value => 'PJ',
            DocumentType::PI->value => 'PI',
            DocumentType::PF->value => 'PF',
            DocumentType::DP->value => 'DP',
            DocumentType::DO->value => 'DO',
            DocumentType::CI->value => 'CI',
        ];

        foreach (DocumentType::cases() as $type) {
            $this->assertSame($expected[$type->value], $type->label());
        }
    }

    public function test_full_label_returns_descriptive_label_for_every_case(): void
    {
        $expected = [
            DocumentType::TC->value => 'Termo de Execução Cultural',
            DocumentType::ET->value => 'Extrato',
            DocumentType::PJ->value => 'Parecer Jurídico',
            DocumentType::PI->value => 'Parecer Orçamentário Inicial',
            DocumentType::PF->value => 'Parecer Orçamentário Final',
            DocumentType::DP->value => 'Despacho de Pagamento',
            DocumentType::DO->value => 'Despacho Orçamentário',
            DocumentType::CI->value => 'Comunicação Interna',
        ];

        foreach (DocumentType::cases() as $type) {
            $this->assertSame($expected[$type->value], $type->fullLabel());
        }
    }

    public function test_phase_maps_each_type_to_expected_phase(): void
    {
        $this->assertSame(DocumentPhase::OPENING, DocumentType::CI->phase());
        $this->assertSame(DocumentPhase::FORMALIZATION, DocumentType::TC->phase());
        $this->assertSame(DocumentPhase::FORMALIZATION, DocumentType::ET->phase());
        $this->assertSame(DocumentPhase::FORMALIZATION, DocumentType::PJ->phase());
        $this->assertSame(DocumentPhase::BUDGET, DocumentType::PI->phase());
        $this->assertSame(DocumentPhase::BUDGET, DocumentType::PF->phase());
        $this->assertSame(DocumentPhase::BUDGET, DocumentType::DO->phase());
        $this->assertSame(DocumentPhase::PAYMENT, DocumentType::DP->phase());
    }

    public function test_required_for_formalization_advance_returns_tc_et_pj(): void
    {
        $this->assertSame(
            [DocumentType::TC, DocumentType::ET, DocumentType::PJ],
            DocumentType::requiredForFormalizationAdvance()
        );
    }
}
