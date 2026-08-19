<?php

namespace App\Services\Documents\Docx;

use DOMDocument;
use DOMElement;
use DOMNode;

class DocumentDocxHtmlConverter
{
    private const BLOCK_TAGS = [
        'p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'table', 'blockquote', 'pre', 'hr',
    ];

    public function __construct(
        private readonly DocumentDocxProfile $profile,
    ) {}

    public function convert(string $html): string
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

        $flushInline = function () use (&$xml, &$inlineNodes, $parent): void {
            if ($inlineNodes === []) {
                return;
            }

            $content = '';
            $style = $parent instanceof DOMElement
                ? $this->computedInlineStyle($parent, includeElement: true)
                : [];
            foreach ($inlineNodes as $node) {
                $content .= $this->inlineXml($node, $style);
            }
            if ($content !== '') {
                $properties = $parent instanceof DOMElement ? $this->paragraphStyleXml($parent) : '';
                $xml .= $this->paragraphXml($content, $properties);
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
            $isBlock = in_array($tag, self::BLOCK_TAGS, true) || $isBlockSpan;

            if (! $isBlock) {
                $inlineNodes[] = $node;

                continue;
            }

            $flushInline();

            if (in_array($tag, ['ul', 'ol'], true)) {
                $xml .= $this->listXml($node, $tag === 'ol' ? 2 : 1, $listLevel);
            } elseif ($tag === 'table') {
                $xml .= $this->tableXml($node);
                if (! $this->profile->isCasaCivil()) {
                    $xml .= $this->verticalSpacer(225);
                }
            } elseif ($tag === 'hr') {
                $xml .= '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="B7B7B7"/></w:pBdr></w:pPr></w:p>';
            } elseif ($this->hasBlockChildren($node)) {
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
        $defaultStyle = $this->computedInlineStyle($node);

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            $level = (int) $matches[1];
            $defaultStyle = [
                'bold' => true,
                'size' => [1 => 36, 2 => 27, 3 => 21, 4 => 18, 5 => 15, 6 => 12][$level],
            ];
        }

        $defaultStyle = $this->mergeElementStyle($defaultStyle, $node);

        $content = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                continue;
            }
            $content .= $this->inlineXml($child, $defaultStyle);
        }

        $properties = ($numbering ?? '').$this->paragraphStyleXml($node);

        return $this->paragraphXml($content, $properties);
    }

    private function paragraphXml(string $content, string $properties = ''): string
    {
        $spacing = $this->profile->isCasaCivil()
            ? '<w:spacing w:after="0" w:before="0" w:line="240" w:lineRule="auto"/>'
            : '';
        $paragraphProperties = $spacing.$properties;

        return '<w:p><w:pPr>'.$paragraphProperties.'</w:pPr>'.$content.'</w:p>';
    }

    /** @param array{bold?: bool, italic?: bool, underline?: bool, strike?: bool, size?: int, color?: string, background?: string, font_family?: string, vertical_align?: string} $style */
    private function inlineXml(DOMNode $node, array $style = []): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $preserveWhitespace = $this->hasPreformattedWhitespace($node);
            $text = $preserveWhitespace
                ? str_replace(["\r\n", "\r", "\t"], ["\n", "\n", '    '], (string) $node->nodeValue)
                : (preg_replace('/[\t\r\n ]+/u', ' ', (string) $node->nodeValue) ?? '');

            if ($text === '') {
                return '';
            }

            $runProperties = $this->profile->isCasaCivil()
                ? '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>'
                : $this->fontFamilyXml($style['font_family'] ?? null);
            $runProperties .= ($style['bold'] ?? false) ? '<w:b/>' : '';
            $runProperties .= ($style['italic'] ?? false) ? '<w:i/>' : '';
            $runProperties .= ($style['underline'] ?? false) ? '<w:u w:val="single"/>' : '';
            $runProperties .= ($style['strike'] ?? false) ? '<w:strike/>' : '';
            if ($this->profile->isCasaCivil()) {
                $runProperties .= '<w:sz w:val="16"/><w:szCs w:val="16"/>';
            } elseif (isset($style['size'])) {
                $runProperties .= '<w:sz w:val="'.$style['size'].'"/><w:szCs w:val="'.$style['size'].'"/>';
            }
            $runProperties .= isset($style['color']) ? '<w:color w:val="'.$style['color'].'"/>' : '';
            $runProperties .= isset($style['background'])
                ? '<w:shd w:val="clear" w:color="auto" w:fill="'.$style['background'].'"/>'
                : '';
            $runProperties .= isset($style['vertical_align'])
                ? '<w:vertAlign w:val="'.$style['vertical_align'].'"/>'
                : '';

            $textXml = collect(explode("\n", $text))
                ->map(fn (string $line) => '<w:t xml:space="preserve">'.$this->escape($line).'</w:t>')
                ->implode('<w:br/>');

            return '<w:r>'.($runProperties !== '' ? '<w:rPr>'.$runProperties.'</w:rPr>' : '')
                .$textXml.'</w:r>';
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'br') {
            return '<w:r><w:br/></w:r>';
        }

        $nextStyle = $this->mergeElementStyle($style, $node);

        $xml = '';
        foreach ($node->childNodes as $child) {
            $xml .= $this->inlineXml($child, $nextStyle);
        }

        return $xml;
    }

    private function hasPreformattedWhitespace(DOMNode $node): bool
    {
        $current = $node->parentNode;

        while ($current instanceof DOMElement) {
            if (strtolower($current->tagName) === 'pre') {
                return true;
            }

            $whiteSpace = $this->cssProperty($current->getAttribute('style'), 'white-space');
            if ($whiteSpace !== null && str_starts_with(strtolower($whiteSpace), 'pre')) {
                return true;
            }

            $current = $current->parentNode;
        }

        return false;
    }

    private function listXml(DOMElement $list, int $numberId, int $level): string
    {
        $xml = '';

        foreach ($list->childNodes as $item) {
            if (! $item instanceof DOMElement || strtolower($item->tagName) !== 'li') {
                continue;
            }

            $numbering = '<w:numPr><w:ilvl w:val="'.min($level, 8).'"/><w:numId w:val="'.$numberId.'"/></w:numPr>';
            if (! $this->profile->isCasaCivil()) {
                $numbering .= '<w:spacing w:after="0"/>';
            }
            $xml .= $this->paragraphForNode($item, $numbering);

            foreach ($item->childNodes as $child) {
                if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                    $xml .= $this->listXml($child, strtolower($child->tagName) === 'ol' ? 2 : 1, $level + 1);
                }
            }
        }

        if ($xml !== '' && ! $this->profile->isCasaCivil()) {
            $xml .= $this->verticalSpacer(180);
        }

        return $xml;
    }

    private function verticalSpacer(int $twips): string
    {
        return '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="'.$twips.'" w:lineRule="exact"/></w:pPr></w:p>';
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
        $columnWidths = $this->tableColumnWidths($tableRows, $columnCount);
        $rows = '';

        foreach ($tableRows as $row) {
            $cells = '';
            $columnIndex = 0;
            foreach ($row->childNodes as $cell) {
                if (! $cell instanceof DOMElement || ! in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    continue;
                }

                $columnSpan = max(1, (int) $cell->getAttribute('colspan'));
                $hasBlockContent = $this->hasBlockChildren($cell);
                $cellContent = $this->blockChildrenXml($cell);
                if ($cellContent === '') {
                    $cellContent = $this->paragraphForNode($cell);
                }
                if (! $this->profile->isCasaCivil() && ! $hasBlockContent) {
                    $cellContent = $this->compactTableParagraphs($cellContent);
                }
                if (strtolower($cell->tagName) === 'th') {
                    $cellContent = $this->boldRuns($cellContent);
                    if ($this->profile->isCasaCivil() || $this->alignment($cell) === null) {
                        $cellContent = $this->centerParagraphs($cellContent);
                    }
                }

                $cellWidth = array_sum(array_slice($columnWidths, $columnIndex, $columnSpan));
                $columnIndex += $columnSpan;
                $verticalAlignment = match ($this->cssProperty($cell->getAttribute('style'), 'vertical-align')) {
                    'middle', 'center' => 'center',
                    'bottom' => 'bottom',
                    default => 'top',
                };
                if ($this->profile->isCasaCivil() && strtolower($cell->tagName) === 'th') {
                    $verticalAlignment = 'center';
                }
                $cellProperties = '<w:tcW w:w="'.$cellWidth.'" w:type="dxa"/>'
                    .($columnSpan > 1 ? '<w:gridSpan w:val="'.$columnSpan.'"/>' : '')
                    .'<w:vAlign w:val="'.$verticalAlignment.'"/>';

                if (! $this->profile->isCasaCivil()) {
                    $fill = $this->cssColor($this->cssProperty($cell->getAttribute('style'), 'background-color'));
                    $fill ??= strtolower($cell->tagName) === 'th' ? 'E6F1E3' : null;
                    if ($fill) {
                        $cellProperties .= '<w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/>';
                    }
                    $cellProperties .= $this->cellBordersXml($cell);
                    $cellProperties .= $this->cellMarginsXml($cell);
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

        $grid = collect($columnWidths)
            ->map(fn (int $width) => '<w:gridCol w:w="'.$width.'"/>')
            ->implode('');
        $borderSize = $this->profile->isCasaCivil() ? 8 : 6;
        $borderColor = $this->profile->isCasaCivil() ? '000000' : 'CCCCCC';
        $verticalCellMargin = $this->profile->isCasaCivil() ? 80 : 90;
        $horizontalCellMargin = $this->profile->isCasaCivil() ? 100 : 120;

        return '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblBorders>'
            .'<w:top w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:left w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:bottom w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:right w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:insideH w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'<w:insideV w:val="single" w:sz="'.$borderSize.'" w:color="'.$borderColor.'"/>'
            .'</w:tblBorders><w:tblLayout w:type="fixed"/>'
            .'<w:tblCellMar><w:top w:w="'.$verticalCellMargin.'" w:type="dxa"/><w:left w:w="'.$horizontalCellMargin.'" w:type="dxa"/>'
            .'<w:bottom w:w="'.$verticalCellMargin.'" w:type="dxa"/><w:right w:w="'.$horizontalCellMargin.'" w:type="dxa"/></w:tblCellMar>'
            .'</w:tblPr><w:tblGrid>'.$grid.'</w:tblGrid>'.$rows.'</w:tbl>';
    }

    /**
     * @param  array<int, DOMElement>  $rows
     * @return array<int, int>
     */
    private function tableColumnWidths(array $rows, int $columnCount): array
    {
        $contentWidth = $this->profile->contentWidthTwips();
        $widths = array_fill(0, $columnCount, null);

        if (! $this->profile->isCasaCivil()) {
            foreach ($rows as $row) {
                $columnIndex = 0;
                $foundWidth = false;

                foreach ($row->childNodes as $cell) {
                    if (! $cell instanceof DOMElement || ! in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                        continue;
                    }

                    $columnSpan = max(1, (int) $cell->getAttribute('colspan'));
                    $percentage = $this->cssPercentage($this->cssProperty($cell->getAttribute('style'), 'width'));
                    if ($percentage !== null) {
                        $spannedWidth = (int) round($contentWidth * $percentage / 100);
                        $widthPerColumn = (int) floor($spannedWidth / $columnSpan);
                        for ($offset = 0; $offset < $columnSpan && $columnIndex + $offset < $columnCount; $offset++) {
                            $widths[$columnIndex + $offset] = $widthPerColumn;
                        }
                        $foundWidth = true;
                    }
                    $columnIndex += $columnSpan;
                }

                if ($foundWidth) {
                    break;
                }
            }
        }

        $specifiedWidth = array_sum(array_filter($widths, fn (?int $width) => $width !== null));
        $unspecifiedColumns = count(array_filter($widths, fn (?int $width) => $width === null));
        $fallbackWidth = $unspecifiedColumns > 0
            ? max(1, (int) floor(($contentWidth - $specifiedWidth) / $unspecifiedColumns))
            : 0;
        $widths = array_map(fn (?int $width) => $width ?? $fallbackWidth, $widths);
        $widths[$columnCount - 1] = max(
            1,
            $widths[$columnCount - 1] + $contentWidth - array_sum($widths),
        );

        return $widths;
    }

    private function cssPercentage(?string $value): ?float
    {
        if ($value === null || ! preg_match('/^([0-9.]+)%$/', trim($value), $matches)) {
            return null;
        }

        return max(0, min(100, (float) $matches[1]));
    }

    private function compactTableParagraphs(string $xml): string
    {
        return preg_replace_callback(
            '/<w:pPr>(.*?)<\/w:pPr>/s',
            function (array $matches): string {
                if (str_contains($matches[1], '<w:spacing')) {
                    return $matches[0];
                }

                return '<w:pPr><w:spacing w:after="0" w:before="0" w:line="408" w:lineRule="auto"/>'
                    .$matches[1].'</w:pPr>';
            },
            $xml,
        ) ?? $xml;
    }

    private function cellBordersXml(DOMElement $cell): string
    {
        $border = $this->cssProperty($cell->getAttribute('style'), 'border');
        if ($border === null || ! preg_match('/^([0-9.]+)(px|pt)\s+(solid|dashed|dotted|double)\s+(.+)$/i', $border, $matches)) {
            return '';
        }

        $points = (float) $matches[1] * (strtolower($matches[2]) === 'px' ? 0.75 : 1);
        $size = max(1, (int) round($points * 8));
        $value = match (strtolower($matches[3])) {
            'dashed' => 'dashed',
            'dotted' => 'dotted',
            'double' => 'double',
            default => 'single',
        };
        $color = $this->cssColor(trim($matches[4])) ?? 'CCCCCC';
        $borders = '';

        foreach (['top', 'left', 'bottom', 'right'] as $side) {
            $borders .= '<w:'.$side.' w:val="'.$value.'" w:sz="'.$size.'" w:color="'.$color.'"/>';
        }

        return '<w:tcBorders>'.$borders.'</w:tcBorders>';
    }

    private function cellMarginsXml(DOMElement $cell): string
    {
        $padding = $this->cssProperty($cell->getAttribute('style'), 'padding');
        if ($padding === null) {
            return '';
        }

        $values = preg_split('/\s+/', trim($padding)) ?: [];
        $twips = array_map(fn (string $value) => $this->cssLengthTwips($value), $values);
        if ($twips === [] || in_array(null, $twips, true)) {
            return '';
        }

        [$top, $right, $bottom, $left] = match (count($twips)) {
            1 => [$twips[0], $twips[0], $twips[0], $twips[0]],
            2 => [$twips[0], $twips[1], $twips[0], $twips[1]],
            3 => [$twips[0], $twips[1], $twips[2], $twips[1]],
            default => [$twips[0], $twips[1], $twips[2], $twips[3]],
        };

        return '<w:tcMar><w:top w:w="'.$top.'" w:type="dxa"/><w:left w:w="'.$left.'" w:type="dxa"/>'
            .'<w:bottom w:w="'.$bottom.'" w:type="dxa"/><w:right w:w="'.$right.'" w:type="dxa"/></w:tcMar>';
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
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), self::BLOCK_TAGS, true)) {
                return true;
            }

            if ($child instanceof DOMElement && str_contains(strtolower($child->getAttribute('style')), 'display: block')) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function computedInlineStyle(DOMElement $node, bool $includeElement = false): array
    {
        $elements = [];
        $current = $includeElement ? $node : $node->parentNode;

        while ($current instanceof DOMElement) {
            array_unshift($elements, $current);

            if ($current->getAttribute('id') === 'docx-root') {
                break;
            }

            $current = $current->parentNode;
        }

        $style = [];
        foreach ($elements as $element) {
            $style = $this->mergeElementStyle($style, $element);
        }

        return $style;
    }

    /** @param array<string, mixed> $style */
    private function mergeElementStyle(array $style, DOMElement $element): array
    {
        $tag = strtolower($element->tagName);
        $style['bold'] = ($style['bold'] ?? false) || in_array($tag, ['b', 'strong'], true);
        $style['italic'] = ($style['italic'] ?? false) || in_array($tag, ['i', 'em'], true);
        $style['underline'] = ($style['underline'] ?? false) || in_array($tag, ['u', 'a'], true);
        $style['strike'] = ($style['strike'] ?? false) || in_array($tag, ['s', 'strike', 'del'], true);

        if ($tag === 'a' && ! isset($style['color'])) {
            $style['color'] = '0563C1';
        }
        if (in_array($tag, ['code', 'pre', 'kbd', 'samp'], true)) {
            $style['font_family'] = 'Courier New';
        }
        if ($tag === 'sup') {
            $style['vertical_align'] = 'superscript';
        } elseif ($tag === 'sub') {
            $style['vertical_align'] = 'subscript';
        }

        if ($tag === 'font') {
            if ($element->hasAttribute('face')) {
                $style['font_family'] = $element->getAttribute('face');
            }
            if ($color = $this->cssColor($element->getAttribute('color'))) {
                $style['color'] = $color;
            }
            if (preg_match('/^[1-7]$/', $element->getAttribute('size'))) {
                $style['size'] = [1 => 16, 2 => 20, 3 => 24, 4 => 28, 5 => 36, 6 => 48, 7 => 72][(int) $element->getAttribute('size')];
            }
        }

        return $this->mergeCssStyle($style, $element->getAttribute('style'));
    }

    /** @param array<string, mixed> $style */
    private function mergeCssStyle(array $style, string $css): array
    {
        $normalizedCss = strtolower($css);
        $fontWeight = $this->cssProperty($normalizedCss, 'font-weight');
        if ($fontWeight !== null) {
            $style['bold'] = preg_match('/^(bold|[6-9]00)$/', $fontWeight) === 1;
        }

        $fontStyle = $this->cssProperty($normalizedCss, 'font-style');
        if ($fontStyle !== null) {
            $style['italic'] = in_array($fontStyle, ['italic', 'oblique'], true);
        }

        $textDecoration = $this->cssProperty($normalizedCss, 'text-decoration')
            ?? $this->cssProperty($normalizedCss, 'text-decoration-line');
        if ($textDecoration !== null) {
            $style['underline'] = str_contains($textDecoration, 'underline');
            $style['strike'] = str_contains($textDecoration, 'line-through');
        }

        if ($color = $this->cssColor($this->cssProperty($css, 'color'))) {
            $style['color'] = $color;
        }

        if ($background = $this->cssColor($this->cssProperty($css, 'background-color'))) {
            $style['background'] = $background;
        }

        $fontSize = $this->cssProperty($normalizedCss, 'font-size');
        if ($fontSize !== null && preg_match('/^([0-9.]+)(pt|px|em|rem|%)$/', $fontSize, $matches)) {
            $value = (float) $matches[1];
            $inheritedSize = (int) ($style['size'] ?? 18);
            $style['size'] = match ($matches[2]) {
                'px' => (int) round($value * 1.5),
                'pt' => (int) round($value * 2),
                'em' => (int) round($inheritedSize * $value),
                'rem' => (int) round(18 * $value),
                '%' => (int) round($inheritedSize * $value / 100),
            };
            $style['size'] = max(12, min(144, $style['size']));
        }

        if ($fontFamily = $this->cssProperty($css, 'font-family')) {
            $style['font_family'] = trim(explode(',', $fontFamily)[0], " \t\n\r\0\x0B\"'");
        }

        if (preg_match('/vertical-align\s*:\s*(super|sub)/', $normalizedCss, $matches)) {
            $style['vertical_align'] = $matches[1] === 'super' ? 'superscript' : 'subscript';
        } elseif ($this->cssProperty($normalizedCss, 'vertical-align') === 'baseline') {
            unset($style['vertical_align']);
        }

        return $style;
    }

    private function cssProperty(string $css, string $property): ?string
    {
        if (! preg_match('/(?:^|;)\s*'.preg_quote($property, '/').'\s*:\s*([^;]+)/i', $css, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function cssColor(?string $color): ?string
    {
        if ($color === null) {
            return null;
        }

        if (preg_match('/^#([0-9a-f]{6})$/i', $color, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i', $color, $matches)) {
            return strtoupper($matches[1].$matches[1].$matches[2].$matches[2].$matches[3].$matches[3]);
        }

        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $color, $matches)) {
            return sprintf(
                '%02X%02X%02X',
                min(255, (int) $matches[1]),
                min(255, (int) $matches[2]),
                min(255, (int) $matches[3]),
            );
        }

        return null;
    }

    private function fontFamilyXml(?string $fontFamily): string
    {
        if (! $fontFamily) {
            return '';
        }

        $fontFamily = match (strtolower($fontFamily)) {
            'sans-serif' => 'Arial',
            'serif' => 'Times New Roman',
            'monospace' => 'Courier New',
            default => $fontFamily,
        };
        $fontFamily = $this->escape($fontFamily);

        return '<w:rFonts w:ascii="'.$fontFamily.'" w:hAnsi="'.$fontFamily.'" w:eastAsia="'.$fontFamily.'" w:cs="'.$fontFamily.'"/>';
    }

    private function alignment(DOMElement $node): ?string
    {
        $current = $node;

        while ($current instanceof DOMElement) {
            $alignment = strtolower($current->getAttribute('align'));
            if (preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $current->getAttribute('style'), $matches)) {
                $alignment = strtolower($matches[1]);
            }

            if (in_array($alignment, ['left', 'right', 'center', 'justify'], true)) {
                return $alignment;
            }

            $current = $current->parentNode;
        }

        return null;
    }

    private function paragraphStyleXml(DOMElement $node): string
    {
        $properties = '';
        $alignment = $this->alignment($node);

        if ($this->profile->isCasaCivil()) {
            return $alignment ? '<w:jc w:val="'.$alignment.'"/>' : '';
        }

        $css = strtolower($node->getAttribute('style'));
        $before = $this->cssLengthTwips($this->cssProperty($css, 'margin-top'));
        $after = $this->cssLengthTwips($this->cssProperty($css, 'margin-bottom'));
        $headingMargins = match (strtolower($node->tagName)) {
            'h1' => 240,
            'h2' => 225,
            'h3' => 210,
            'h4' => 240,
            'h5' => 250,
            'h6' => 280,
            default => null,
        };
        $before ??= $headingMargins;
        $after ??= $headingMargins;
        $lineHeight = $this->inheritedCssProperty($node, 'line-height');
        $spacing = '';

        if ($before !== null) {
            $spacing .= ' w:before="'.$before.'"';
        }
        if ($after !== null) {
            $spacing .= ' w:after="'.$after.'"';
        }
        if ($lineHeight !== null && is_numeric($lineHeight)) {
            $spacing .= ' w:line="'.max(1, (int) round((float) $lineHeight * 240)).'" w:lineRule="auto"';
        } elseif (($lineHeightTwips = $this->cssLengthTwips($lineHeight)) !== null) {
            $spacing .= ' w:line="'.max(1, $lineHeightTwips).'" w:lineRule="exact"';
        }
        if ($spacing !== '') {
            $properties .= '<w:spacing'.$spacing.'/>';
        }

        $left = 0;
        $right = 0;
        $current = $node;
        while ($current instanceof DOMElement && $current->getAttribute('id') !== 'docx-root') {
            $currentCss = strtolower($current->getAttribute('style'));
            $left += ($this->cssLengthTwips($this->cssProperty($currentCss, 'margin-left')) ?? 0)
                + ($this->cssLengthTwips($this->cssProperty($currentCss, 'padding-left')) ?? 0);
            $right += ($this->cssLengthTwips($this->cssProperty($currentCss, 'margin-right')) ?? 0)
                + ($this->cssLengthTwips($this->cssProperty($currentCss, 'padding-right')) ?? 0);
            $current = $current->parentNode;
        }
        if (strtolower($node->tagName) === 'blockquote' && $left === 0 && $right === 0) {
            $left = 600;
            $right = 600;
        }
        $firstLine = $this->cssLengthTwips($this->cssProperty($css, 'text-indent'));
        $indent = '';

        if ($left > 0) {
            $indent .= ' w:left="'.$left.'"';
        }
        if ($right > 0) {
            $indent .= ' w:right="'.$right.'"';
        }
        if ($firstLine !== null && $firstLine >= 0) {
            $indent .= ' w:firstLine="'.$firstLine.'"';
        } elseif ($firstLine !== null) {
            $indent .= ' w:hanging="'.abs($firstLine).'"';
        }
        if ($indent !== '') {
            $properties .= '<w:ind'.$indent.'/>';
        }

        if ($alignment) {
            $properties .= '<w:jc w:val="'.$alignment.'"/>';
        }
        if ($node->getAttribute('dir') === 'rtl' || $this->inheritedCssProperty($node, 'direction') === 'rtl') {
            $properties .= '<w:bidi/>';
        }

        return $properties;
    }

    private function inheritedCssProperty(DOMElement $node, string $property): ?string
    {
        $current = $node;

        while ($current instanceof DOMElement) {
            $value = $this->cssProperty($current->getAttribute('style'), $property);
            if ($value !== null) {
                return strtolower($value);
            }

            $current = $current->parentNode;
        }

        return null;
    }

    private function cssLengthTwips(?string $length): ?int
    {
        if ($length === null || ! preg_match('/^(-?[0-9.]+)(px|pt|in|cm|mm)$/', $length, $matches)) {
            return null;
        }

        $value = (float) $matches[1];
        $twipsPerUnit = match ($matches[2]) {
            'px' => 15,
            'pt' => 20,
            'in' => 1440,
            'cm' => 1440 / 2.54,
            'mm' => 1440 / 25.4,
        };

        return (int) round($value * $twipsPerUnit);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
