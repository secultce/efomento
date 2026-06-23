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
            DocumentType::D->value => 'D',
            DocumentType::PO->value => 'PO',
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
            DocumentType::D->value => 'Despacho',
            DocumentType::PO->value => 'Parecer Orçamentário',
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
        $this->assertSame(DocumentPhase::PAYMENT, DocumentType::D->phase());
    }

    public function test_required_for_formalization_advance_returns_tc_et_pj(): void
    {
        $this->assertSame(
            [DocumentType::TC, DocumentType::ET, DocumentType::PJ],
            DocumentType::requiredForFormalizationAdvance()
        );
    }
}
