<?php

namespace Tests\Unit;

use App\Services\Documents\Docx\DocumentDocxHtmlConverter;
use App\Services\Documents\Docx\DocumentDocxProfile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentDocxHtmlConverterTest extends TestCase
{
    #[Test]
    public function it_keeps_display_block_placeholders_on_separate_lines_inside_a_paragraph(): void
    {
        $html = '<p><span style="display: block; line-height: 1.35; text-align: left;">'
            .'<span style="display: block;"><strong>Gestão/Unidade:</strong> 27200004 - FUNDO ESTADUAL DA CULTURA</span>'
            .'<span style="display: block;"><strong>Programa de Trabalho:</strong> 131 - Promoção e Desenvolvimento da Arte</span>'
            .'<span style="display: block;"><strong>Objetivo:</strong> Democratizar, fomentar e ampliar o acesso</span>'
            .'</span></p>';

        $xml = (new DocumentDocxHtmlConverter(DocumentDocxProfile::Standard))->convert($html);

        $this->assertSame(3, substr_count($xml, '<w:p>'));
        $this->assertMatchesRegularExpression('/FUNDO ESTADUAL DA CULTURA<\/w:t><\/w:r><\/w:p><w:p>/', $xml);
        $this->assertMatchesRegularExpression('/Desenvolvimento da Arte<\/w:t><\/w:r><\/w:p><w:p>/', $xml);
        $this->assertSame(3, substr_count($xml, '<w:spacing w:line="324" w:lineRule="auto"/>'));
        $this->assertSame(3, substr_count($xml, '<w:jc w:val="left"/>'));
    }

    #[Test]
    public function it_preserves_nested_level_four_headings_as_separate_blocks(): void
    {
        $xml = (new DocumentDocxHtmlConverter(DocumentDocxProfile::Standard))
            ->convert('<div><h4>Título</h4><p>Texto</p></div>');

        $this->assertSame(2, substr_count($xml, '<w:p>'));
        $this->assertMatchesRegularExpression('/Título<\/w:t><\/w:r><\/w:p><w:p>/', $xml);
        $this->assertStringContainsString('<w:sz w:val="18"/><w:szCs w:val="18"/>', $xml);
    }

    #[Test]
    public function it_never_emits_non_positive_table_column_widths(): void
    {
        $xml = (new DocumentDocxHtmlConverter(DocumentDocxProfile::Standard))
            ->convert('<table><tr><td style="width: 100%">A</td><td style="width: 100%">B</td></tr></table>');

        preg_match_all('/<w:gridCol w:w="(-?\d+)"\/>/', $xml, $matches);

        $this->assertCount(2, $matches[1]);
        $this->assertGreaterThan(0, min(array_map('intval', $matches[1])));
    }
}
