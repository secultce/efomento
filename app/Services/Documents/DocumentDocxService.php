<?php

namespace App\Services\Documents;

use App\Models\Document;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DocumentDocxService
{
    public const PROFILE_STANDARD = 'standard';

    public const PROFILE_CASA_CIVIL = 'casa_civil';

    private const STANDARD_CONTENT_WIDTH_TWIPS = 10206;

    private const CASA_CIVIL_CONTENT_WIDTH_TWIPS = 9921;

    private const RELATIONS = [
        'images',
        ...DocumentPlaceholderResolver::RELATIONS,
    ];

    private int $drawingId = 1;

    private string $profile = self::PROFILE_STANDARD;

    public function __construct(
        private readonly DocumentPlaceholderResolver $placeholderResolver,
    ) {}

    public function download(
        Document $document,
        ?string $type = null,
        string $profile = self::PROFILE_STANDARD,
    ): BinaryFileResponse {
        $path = $this->generate($document, $profile);

        return response()->download(
            $path,
            $this->generateFilename($document, $type, $profile),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend();
    }

    public function buildZip(
        array $projectIds,
        string $type,
        string $profile = self::PROFILE_STANDARD,
    ): string {
        $documents = Document::with(self::RELATIONS)
            ->whereIn('project_id', $projectIds)
            ->where('type', $type)
            ->get();

        $tempFile = tempnam(sys_get_temp_dir(), 'docs_').'.zip';
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($documents as $document) {
            $docxPath = $this->generate($document, $profile);
            $zip->addFromString(
                $this->generateFilename($document, $type, $profile),
                (string) file_get_contents($docxPath),
            );
            @unlink($docxPath);
        }

        $zip->close();

        return $tempFile;
    }

    public function generateFilename(
        Document $document,
        ?string $type = null,
        string $profile = self::PROFILE_STANDARD,
    ): string {
        $type ??= $document->type->value;
        $agentName = $document->project?->agent?->name ?? $document->notice?->name ?? 'documento';
        $profileSuffix = $profile === self::PROFILE_CASA_CIVIL ? '_CASA_CIVIL' : '';

        return strtoupper($type).'_'.str($agentName)->slug('_').'_'.$document->created_at->format('Y-m-d').'_'.$document->created_at->timestamp.$profileSuffix.'.docx';
    }

    private function generate(Document $document, string $profile): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        $this->drawingId = 1;
        $this->profile = $profile;
        $document->loadMissing(self::RELATIONS);

        $headerImages = $this->images($document, 'header');
        $footerImages = $this->images($document, 'footer');
        $body = $this->placeholderResolver->resolve($document);
        $tempFile = tempnam(sys_get_temp_dir(), 'document_').'.docx';
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes($headerImages, $footerImages));
        $zip->addFromString('_rels/.rels', $this->packageRelationships());
        $zip->addFromString('docProps/core.xml', $this->coreProperties($document));
        $zip->addFromString('docProps/app.xml', $this->appProperties());
        $zip->addFromString('word/document.xml', $this->documentXml($body, $headerImages, $footerImages));
        $zip->addFromString('word/styles.xml', $this->stylesXml());
        $zip->addFromString('word/numbering.xml', $this->numberingXml());
        $zip->addFromString('word/settings.xml', $this->settingsXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelationships($headerImages, $footerImages));

        $this->addHeaderOrFooter($zip, 'header', $headerImages);
        $this->addHeaderOrFooter($zip, 'footer', $footerImages);

        $zip->close();

        return $tempFile;
    }

    /**
     * @return Collection<int, array{path: string, extension: string, position: string, full: bool, width: int, height: int, relationship: string, target: string}>
     */
    private function images(Document $document, string $section): Collection
    {
        return $document->images
            ->filter(fn ($image) => $image->section?->value === $section)
            ->values()
            ->map(function ($image, int $index) use ($section) {
                $path = public_path('storage/'.$image->path);
                $size = is_file($path) ? @getimagesize($path) : false;

                if (! $size) {
                    return null;
                }

                $extension = match ($size['mime'] ?? null) {
                    'image/jpeg' => 'jpg',
                    'image/gif' => 'gif',
                    default => 'png',
                };

                return [
                    'path' => $path,
                    'extension' => $extension,
                    'position' => $image->position?->value ?? 'center',
                    'full' => (bool) $image->is_full_width,
                    'width' => (int) $size[0],
                    'height' => (int) $size[1],
                    'relationship' => 'rId'.($index + 1),
                    'target' => $section.'_'.($index + 1).'.'.$extension,
                ];
            })
            ->filter()
            ->values();
    }

    private function addHeaderOrFooter(ZipArchive $zip, string $section, Collection $images): void
    {
        if ($images->isEmpty()) {
            return;
        }

        $zip->addFromString("word/{$section}1.xml", $this->headerFooterXml($section, $images));
        $zip->addFromString(
            "word/_rels/{$section}1.xml.rels",
            $this->imageRelationships($images),
        );

        foreach ($images as $image) {
            $zip->addFile($image['path'], 'word/media/'.$image['target']);
        }
    }

    private function documentXml(string $body, Collection $headerImages, Collection $footerImages): string
    {
        $headerReference = $headerImages->isNotEmpty() ? '<w:headerReference w:type="default" r:id="rId3"/>' : '';
        $footerReference = $footerImages->isNotEmpty() ? '<w:footerReference w:type="default" r:id="rId4"/>' : '';
        $pageMargins = $this->isCasaCivil()
            ? '<w:pgMar w:top="1440" w:right="992" w:bottom="1440" w:left="993" w:header="720" w:footer="720" w:gutter="0"/>'
            : '<w:pgMar w:top="1701" w:right="850" w:bottom="1417" w:left="850" w:header="284" w:footer="284" w:gutter="0"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<w:body>'.$this->bodyXml($body)
            .'<w:sectPr>'.$headerReference.$footerReference
            .'<w:pgSz w:w="11906" w:h="16838"/>'
            .$pageMargins
            .($this->isCasaCivil() ? '<w:pgNumType w:start="1"/>' : '')
            .'</w:sectPr></w:body></w:document>';
    }

    private function bodyXml(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="docx-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('docx-root');

        if (! $root) {
            return '<w:p/>';
        }

        $xml = $this->blockChildrenXml($root);

        return $xml !== '' ? $xml : '<w:p/>';
    }

    private function blockChildrenXml(DOMNode $parent, int $listLevel = 0): string
    {
        $xml = '';
        $inlineNodes = [];

        $flushInline = function () use (&$xml, &$inlineNodes): void {
            if ($inlineNodes === []) {
                return;
            }

            $content = '';
            foreach ($inlineNodes as $node) {
                $content .= $this->inlineXml($node);
            }
            if ($content !== '') {
                $xml .= $this->paragraphXml($content);
            }
            $inlineNodes = [];
        };

        foreach ($parent->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                if (trim((string) $node->textContent) !== '') {
                    $inlineNodes[] = $node;
                }

                continue;
            }

            $tag = strtolower($node->tagName);
            $isBlockSpan = $tag === 'span' && str_contains(strtolower($node->getAttribute('style')), 'display: block');
            $isBlock = in_array($tag, ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'table', 'blockquote', 'pre', 'hr'], true) || $isBlockSpan;

            if (! $isBlock) {
                $inlineNodes[] = $node;

                continue;
            }

            $flushInline();

            if (in_array($tag, ['ul', 'ol'], true)) {
                $xml .= $this->listXml($node, $tag === 'ol' ? 2 : 1, $listLevel);
            } elseif ($tag === 'table') {
                $xml .= $this->tableXml($node);
            } elseif ($tag === 'hr') {
                $xml .= '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="B7B7B7"/></w:pBdr></w:pPr></w:p>';
            } elseif (in_array($tag, ['div', 'span'], true) && $this->hasBlockChildren($node)) {
                $xml .= $this->blockChildrenXml($node, $listLevel);
            } else {
                $xml .= $this->paragraphForNode($node);
            }
        }

        $flushInline();

        return $xml;
    }

    private function paragraphForNode(DOMElement $node, ?string $numbering = null): string
    {
        $tag = strtolower($node->tagName);
        $defaultStyle = [];

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            $level = (int) $matches[1];
            $defaultStyle = ['bold' => true, 'size' => max(24, 40 - ($level * 2))];
        }

        $content = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                continue;
            }
            $content .= $this->inlineXml($child, $defaultStyle);
        }

        $properties = $numbering ?? '';
        $alignment = $this->alignment($node);
        if ($alignment) {
            $properties .= '<w:jc w:val="'.$alignment.'"/>';
        }

        return $this->paragraphXml($content, $properties);
    }

    private function paragraphXml(string $content, string $properties = ''): string
    {
        $spacing = $this->isCasaCivil()
            ? '<w:spacing w:after="0" w:before="0" w:line="240" w:lineRule="auto"/>'
            : '<w:spacing w:after="180" w:line="360" w:lineRule="auto"/>';
        $paragraphProperties = $spacing.$properties;

        return '<w:p><w:pPr>'.$paragraphProperties.'</w:pPr>'.$content.'</w:p>';
    }

    /** @param array{bold?: bool, italic?: bool, underline?: bool, strike?: bool, size?: int, color?: string} $style */
    private function inlineXml(DOMNode $node, array $style = []): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = preg_replace('/[\t\r\n ]+/u', ' ', (string) $node->nodeValue) ?? '';

            if ($text === '') {
                return '';
            }

            $runProperties = $this->isCasaCivil()
                ? '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>'
                : '';
            $runProperties .= ($style['bold'] ?? false) ? '<w:b/>' : '';
            $runProperties .= ($style['italic'] ?? false) ? '<w:i/>' : '';
            $runProperties .= ($style['underline'] ?? false) ? '<w:u w:val="single"/>' : '';
            $runProperties .= ($style['strike'] ?? false) ? '<w:strike/>' : '';
            if ($this->isCasaCivil()) {
                $runProperties .= '<w:sz w:val="16"/><w:szCs w:val="16"/>';
            } elseif (isset($style['size'])) {
                $runProperties .= '<w:sz w:val="'.$style['size'].'"/><w:szCs w:val="'.$style['size'].'"/>';
            }
            $runProperties .= isset($style['color']) ? '<w:color w:val="'.$style['color'].'"/>' : '';

            return '<w:r>'.($runProperties !== '' ? '<w:rPr>'.$runProperties.'</w:rPr>' : '')
                .'<w:t xml:space="preserve">'.$this->escape($text).'</w:t></w:r>';
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'br') {
            return '<w:r><w:br/></w:r>';
        }

        $nextStyle = $style;
        $nextStyle['bold'] = ($style['bold'] ?? false) || in_array($tag, ['b', 'strong'], true);
        $nextStyle['italic'] = ($style['italic'] ?? false) || in_array($tag, ['i', 'em'], true);
        $nextStyle['underline'] = ($style['underline'] ?? false) || $tag === 'u';
        $nextStyle['strike'] = ($style['strike'] ?? false) || in_array($tag, ['s', 'strike', 'del'], true);
        $nextStyle = $this->mergeCssStyle($nextStyle, $node->getAttribute('style'));

        $xml = '';
        foreach ($node->childNodes as $child) {
            $xml .= $this->inlineXml($child, $nextStyle);
        }

        return $xml;
    }

    private function listXml(DOMElement $list, int $numberId, int $level): string
    {
        $xml = '';

        foreach ($list->childNodes as $item) {
            if (! $item instanceof DOMElement || strtolower($item->tagName) !== 'li') {
                continue;
            }

            $numbering = '<w:numPr><w:ilvl w:val="'.min($level, 8).'"/><w:numId w:val="'.$numberId.'"/></w:numPr>';
            $xml .= $this->paragraphForNode($item, $numbering);

            foreach ($item->childNodes as $child) {
                if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                    $xml .= $this->listXml($child, strtolower($child->tagName) === 'ol' ? 2 : 1, $level + 1);
                }
            }
        }

        return $xml;
    }

    private function tableXml(DOMElement $table): string
    {
        $tableRows = $this->tableRows($table);
        $columnCount = max(1, collect($tableRows)->map(function (DOMElement $row) {
            $columns = 0;

            foreach ($row->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    $columns += max(1, (int) $cell->getAttribute('colspan'));
                }
            }

            return $columns;
        })->max() ?? 1);
        $columnWidth = (int) floor($this->contentWidthTwips() / $columnCount);
        $rows = '';

        foreach ($tableRows as $row) {
            $cells = '';
            foreach ($row->childNodes as $cell) {
                if (! $cell instanceof DOMElement || ! in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    continue;
                }

                $columnSpan = max(1, (int) $cell->getAttribute('colspan'));
                $cellContent = $this->blockChildrenXml($cell);
                if ($cellContent === '') {
                    $cellContent = $this->paragraphForNode($cell);
                }
                if (strtolower($cell->tagName) === 'th') {
                    $cellContent = $this->boldRuns($cellContent);

                    if ($this->isCasaCivil()) {
                        $cellContent = $this->centerParagraphs($cellContent);
                    }
                }

                $cellProperties = '<w:tcW w:w="'.($columnWidth * $columnSpan).'" w:type="dxa"/>'
                    .($columnSpan > 1 ? '<w:gridSpan w:val="'.$columnSpan.'"/>' : '')
                    .'<w:vAlign w:val="'.($this->isCasaCivil() && strtolower($cell->tagName) === 'th' ? 'center' : 'top').'"/>';

                if (strtolower($cell->tagName) === 'th' && ! $this->isCasaCivil()) {
                    $cellProperties .= '<w:shd w:val="clear" w:color="auto" w:fill="E6F1E3"/>';
                }

                $cells .= '<w:tc><w:tcPr>'.$cellProperties.'</w:tcPr>'.$cellContent.'</w:tc>';
            }

            if ($cells !== '') {
                $rows .= '<w:tr>'.$cells.'</w:tr>';
            }
        }

        if ($rows === '') {
            return '';
        }

        $grid = str_repeat('<w:gridCol w:w="'.$columnWidth.'"/>', $columnCount);

        $borderSize = $this->isCasaCivil() ? 8 : 4;
        $borderColor = $this->isCasaCivil() ? '000000' : 'B7B7B7';

        return '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblBorders>'
            .'<w:top w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:left w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:bottom w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:right w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:insideH w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:insideV w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'</w:tblBorders><w:tblLayout w:type="fixed"/>'
            .'<w:tblCellMar><w:top w:w="80" w:type="dxa"/><w:left w:w="100" w:type="dxa"/>'
            .'<w:bottom w:w="80" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar>'
            .'</w:tblPr><w:tblGrid>'.$grid.'</w:tblGrid>'.$rows.'</w:tbl>';
    }

    /** @return array<int, DOMElement> */
    private function tableRows(DOMElement $table): array
    {
        $rows = [];

        foreach ($table->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (strtolower($child->tagName) === 'tr') {
                $rows[] = $child;

                continue;
            }

            if (! in_array(strtolower($child->tagName), ['thead', 'tbody', 'tfoot'], true)) {
                continue;
            }

            foreach ($child->childNodes as $row) {
                if ($row instanceof DOMElement && strtolower($row->tagName) === 'tr') {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    private function boldRuns(string $xml): string
    {
        $xml = str_replace('<w:r><w:rPr>', '<w:r><w:rPr><w:b/>', $xml);

        return preg_replace(
            '/<w:r>(?!<w:rPr>)/',
            '<w:r><w:rPr><w:b/></w:rPr>',
            $xml,
        ) ?? $xml;
    }

    private function centerParagraphs(string $xml): string
    {
        $xml = preg_replace('/<w:jc w:val="[^"]+"\/>/', '', $xml) ?? $xml;

        return str_replace('</w:pPr>', '<w:jc w:val="center"/></w:pPr>', $xml);
    }

    private function hasBlockChildren(DOMElement $node): bool
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['p', 'div', 'ul', 'ol', 'table', 'h1', 'h2', 'h3', 'blockquote', 'pre'], true)) {
                return true;
            }

            if ($child instanceof DOMElement && str_contains(strtolower($child->getAttribute('style')), 'display: block')) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $style */
    private function mergeCssStyle(array $style, string $css): array
    {
        $css = strtolower($css);
        $style['bold'] = ($style['bold'] ?? false) || preg_match('/font-weight\s*:\s*(bold|[6-9]00)/', $css) === 1;
        $style['italic'] = ($style['italic'] ?? false) || str_contains($css, 'font-style: italic');
        $style['underline'] = ($style['underline'] ?? false) || str_contains($css, 'text-decoration: underline');
        $style['strike'] = ($style['strike'] ?? false) || str_contains($css, 'line-through');

        if (preg_match('/color\s*:\s*#([0-9a-f]{6})/', $css, $matches)) {
            $style['color'] = strtoupper($matches[1]);
        }

        if (preg_match('/font-size\s*:\s*([0-9.]+)(pt|px)/', $css, $matches)) {
            $points = (float) $matches[1] * ($matches[2] === 'px' ? 0.75 : 1);
            $style['size'] = max(12, min(144, (int) round($points * 2)));
        }

        return $style;
    }

    private function alignment(DOMElement $node): ?string
    {
        $alignment = strtolower($node->getAttribute('align'));
        if (preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $node->getAttribute('style'), $matches)) {
            $alignment = strtolower($matches[1]);
        }

        return match ($alignment) {
            'left', 'right', 'center', 'justify' => $alignment,
            default => null,
        };
    }

    private function headerFooterXml(string $section, Collection $images): string
    {
        $tag = $section === 'header' ? 'hdr' : 'ftr';
        $full = $images->firstWhere('full', true);
        $casaCivilHeader = $this->isCasaCivil() && $section === 'header' && $images->count() === 1
            ? $images->first()
            : null;

        if ($casaCivilHeader) {
            $content = '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
                .$this->imageDrawing($casaCivilHeader, 168.35, 76.8, 114300).'</w:p>';
        } elseif ($full) {
            $content = '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
                .$this->imageDrawing($full, 690, $section === 'header' ? 95 : 85).'</w:p>';
        } else {
            $cells = '';
            foreach (['left', 'center', 'right'] as $position) {
                $image = $images->firstWhere('position', $position);
                $cells .= '<w:tc><w:tcPr><w:tcW w:w="1667" w:type="pct"/></w:tcPr>'
                    .'<w:p><w:pPr><w:jc w:val="'.$position.'"/></w:pPr>'
                    .($image ? $this->imageDrawing($image, 170, 80) : '').'</w:p></w:tc>';
            }
            $content = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/></w:tblPr><w:tr>'.$cells.'</w:tr></w:tbl>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:'.$tag.' xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            .'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            .'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .$content.'</w:'.$tag.'>';
    }

    /** @param array{width: int, height: int, relationship: string} $image */
    private function imageDrawing(
        array $image,
        float $maxWidth,
        float $maxHeight,
        int $distance = 0,
    ): string {
        $scale = min($maxWidth / max(1, $image['width']), $maxHeight / max(1, $image['height']), 1);
        $width = max(1, (int) round($image['width'] * $scale * 9525));
        $height = max(1, (int) round($image['height'] * $scale * 9525));
        $id = $this->drawingId++;

        return '<w:r><w:drawing><wp:inline distT="'.$distance.'" distB="'.$distance.'" distL="'.$distance.'" distR="'.$distance.'">'
            .'<wp:extent cx="'.$width.'" cy="'.$height.'"/><wp:effectExtent l="0" t="0" r="0" b="0"/>'
            .'<wp:docPr id="'.$id.'" name="Imagem '.$id.'"/><wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            .'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic>'
            .'<pic:nvPicPr><pic:cNvPr id="'.$id.'" name="Imagem '.$id.'"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$image['relationship'].'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$width.'" cy="'.$height.'"/></a:xfrm>'
            .'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
    }

    private function contentTypes(Collection $headerImages, Collection $footerImages): string
    {
        $header = $headerImages->isNotEmpty()
            ? '<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>'
            : '';
        $footer = $footerImages->isNotEmpty()
            ? '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Default Extension="png" ContentType="image/png"/><Default Extension="jpg" ContentType="image/jpeg"/>'
            .'<Default Extension="gif" ContentType="image/gif"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
            .'<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .$header.$footer.'</Types>';
    }

    private function documentRelationships(Collection $headerImages, Collection $footerImages): string
    {
        $header = $headerImages->isNotEmpty()
            ? '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/>'
            : '';
        $footer = $footerImages->isNotEmpty()
            ? '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>'
            .'<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>'
            .$header.$footer.'</Relationships>';
    }

    private function imageRelationships(Collection $images): string
    {
        $relationships = $images->map(
            fn ($image) => '<Relationship Id="'.$image['relationship'].'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$image['target'].'"/>'
        )->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'</Relationships>';
    }

    private function packageRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        $font = $this->isCasaCivil() ? 'Times New Roman' : 'Arial';
        $size = $this->isCasaCivil() ? 16 : 24;
        $paragraphDefaults = $this->isCasaCivil()
            ? '<w:spacing w:after="0" w:before="0" w:line="240" w:lineRule="auto"/><w:jc w:val="both"/>'
            : '<w:jc w:val="both"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="'.$font.'" w:hAnsi="'.$font.'" w:eastAsia="'.$font.'" w:cs="'.$font.'"/>'
            .'<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/><w:lang w:val="pt-BR"/></w:rPr></w:rPrDefault>'
            .'<w:pPrDefault><w:pPr>'.$paragraphDefaults.'</w:pPr></w:pPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            .'</w:styles>';
    }

    private function numberingXml(): string
    {
        $levels = '';
        for ($level = 0; $level < 9; $level++) {
            $levels .= '<w:lvl w:ilvl="'.$level.'"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="•"/>'
                .'<w:lvlJc w:val="left"/><w:pPr><w:tabs><w:tab w:val="num" w:pos="'.(720 + ($level * 360)).'"/></w:tabs>'
                .'<w:ind w:left="'.(720 + ($level * 360)).'" w:hanging="360"/></w:pPr></w:lvl>';
        }
        $orderedLevels = '';
        for ($level = 0; $level < 9; $level++) {
            $orderedLevels .= '<w:lvl w:ilvl="'.$level.'"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%'.($level + 1).'."/>'
                .'<w:lvlJc w:val="left"/><w:pPr><w:tabs><w:tab w:val="num" w:pos="'.(720 + ($level * 360)).'"/></w:tabs>'
                .'<w:ind w:left="'.(720 + ($level * 360)).'" w:hanging="360"/></w:pPr></w:lvl>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:abstractNum w:abstractNumId="1">'.$levels.'</w:abstractNum>'
            .'<w:abstractNum w:abstractNumId="2">'.$orderedLevels.'</w:abstractNum>'
            .'<w:num w:numId="1"><w:abstractNumId w:val="1"/></w:num>'
            .'<w:num w:numId="2"><w:abstractNumId w:val="2"/></w:num>'
            .'</w:numbering>';
    }

    private function settingsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:zoom w:percent="100"/><w:defaultTabStop w:val="720"/><w:compat/></w:settings>';
    }

    private function coreProperties(Document $document): string
    {
        $title = $this->escape($document->type->fullLabel());
        $created = $document->created_at->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.$title.'</dc:title><dc:creator>e-Fomento</dc:creator>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:modified></cp:coreProperties>';
    }

    private function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            .'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>e-Fomento</Application><AppVersion>1.0</AppVersion></Properties>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentWidthTwips(): int
    {
        return $this->isCasaCivil()
            ? self::CASA_CIVIL_CONTENT_WIDTH_TWIPS
            : self::STANDARD_CONTENT_WIDTH_TWIPS;
    }

    private function isCasaCivil(): bool
    {
        return $this->profile === self::PROFILE_CASA_CIVIL;
    }
}
