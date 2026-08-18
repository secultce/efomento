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
}
