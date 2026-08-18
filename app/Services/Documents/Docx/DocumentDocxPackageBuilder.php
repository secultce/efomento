<?php

namespace App\Services\Documents\Docx;

use App\Models\Document;
use App\Services\Documents\DocumentPlaceholderResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DocumentDocxPackageBuilder
{
    public const RELATIONS = [
        'images',
        ...DocumentPlaceholderResolver::RELATIONS,
    ];

    public function __construct(
        private readonly DocumentPlaceholderResolver $placeholderResolver,
    ) {}

    public function build(Document $document, string $profileName): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        $profile = DocumentDocxProfile::resolve($profileName);
        $drawingId = 1;
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
        $zip->addFromString('word/document.xml', $this->documentXml($body, $headerImages, $footerImages, $profile));
        $zip->addFromString('word/styles.xml', $this->stylesXml($profile));
        $zip->addFromString('word/numbering.xml', $this->numberingXml($profile));
        $zip->addFromString('word/settings.xml', $this->settingsXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelationships($headerImages, $footerImages));

        $this->addHeaderOrFooter($zip, 'header', $headerImages, $profile, $drawingId);
        $this->addHeaderOrFooter($zip, 'footer', $footerImages, $profile, $drawingId);

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
                $path = Storage::disk('public')->path($image->path);
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

    private function addHeaderOrFooter(
        ZipArchive $zip,
        string $section,
        Collection $images,
        DocumentDocxProfile $profile,
        int &$drawingId,
    ): void {
        if ($images->isEmpty()) {
            return;
        }

        $zip->addFromString(
            "word/{$section}1.xml",
            $this->headerFooterXml($section, $images, $profile, $drawingId),
        );
        $zip->addFromString(
            "word/_rels/{$section}1.xml.rels",
            $this->imageRelationships($images),
        );

        foreach ($images as $image) {
            $zip->addFile($image['path'], 'word/media/'.$image['target']);
        }
    }

    private function documentXml(
        string $body,
        Collection $headerImages,
        Collection $footerImages,
        DocumentDocxProfile $profile,
    ): string {
        $headerReference = $headerImages->isNotEmpty() ? '<w:headerReference w:type="default" r:id="rId3"/>' : '';
        $footerReference = $footerImages->isNotEmpty() ? '<w:footerReference w:type="default" r:id="rId4"/>' : '';
        $pageMargins = $profile->isCasaCivil()
            ? '<w:pgMar w:top="1440" w:right="992" w:bottom="1440" w:left="993" w:header="720" w:footer="720" w:gutter="0"/>'
            : '<w:pgMar w:top="1800" w:right="750" w:bottom="1500" w:left="750" w:header="375" w:footer="0" w:gutter="0"/>';
        $bodyXml = (new DocumentDocxHtmlConverter($profile))->convert($body);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<w:body>'.$bodyXml
            .'<w:sectPr>'.$headerReference.$footerReference
            .'<w:pgSz w:w="11906" w:h="16838"/>'
            .$pageMargins
            .($profile->isCasaCivil() ? '<w:pgNumType w:start="1"/>' : '')
            .'</w:sectPr></w:body></w:document>';
    }

    private function headerFooterXml(
        string $section,
        Collection $images,
        DocumentDocxProfile $profile,
        int &$drawingId,
    ): string {
        $tag = $section === 'header' ? 'hdr' : 'ftr';
        $full = $images->firstWhere('full', true);
        $casaCivilHeader = $profile->isCasaCivil() && $section === 'header' && $images->count() === 1
            ? $images->first()
            : null;

        if ($casaCivilHeader) {
            $content = '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
                .$this->imageDrawing($casaCivilHeader, 168.35, 76.8, $drawingId, 114300).'</w:p>';
        } elseif ($full) {
            $content = '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
                .$this->imageDrawing($full, 690, $section === 'header' ? 95 : 85, $drawingId).'</w:p>';
        } else {
            $cells = '';
            foreach (['left', 'center', 'right'] as $position) {
                $image = $images->firstWhere('position', $position);
                $cells .= '<w:tc><w:tcPr><w:tcW w:w="1667" w:type="pct"/></w:tcPr>'
                    .'<w:p><w:pPr><w:jc w:val="'.$position.'"/></w:pPr>'
                    .($image ? $this->imageDrawing($image, 170, 80, $drawingId) : '').'</w:p></w:tc>';
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
        int &$drawingId,
        int $distance = 0,
    ): string {
        $scale = min($maxWidth / max(1, $image['width']), $maxHeight / max(1, $image['height']), 1);
        $width = max(1, (int) round($image['width'] * $scale * 9525));
        $height = max(1, (int) round($image['height'] * $scale * 9525));
        $id = $drawingId++;

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

    private function stylesXml(DocumentDocxProfile $profile): string
    {
        $font = $profile->isCasaCivil() ? 'Times New Roman' : 'DejaVu Sans';
        $size = $profile->isCasaCivil() ? 16 : 18;
        $color = $profile->isCasaCivil() ? '' : '<w:color w:val="1A1A1A"/>';
        $paragraphDefaults = $profile->isCasaCivil()
            ? '<w:spacing w:after="0" w:before="0" w:line="240" w:lineRule="auto"/><w:jc w:val="both"/>'
            : '<w:spacing w:after="180" w:before="0" w:line="408" w:lineRule="auto"/><w:jc w:val="both"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="'.$font.'" w:hAnsi="'.$font.'" w:eastAsia="'.$font.'" w:cs="'.$font.'"/>'
            .'<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/>'.$color.'<w:lang w:val="pt-BR"/></w:rPr></w:rPrDefault>'
            .'<w:pPrDefault><w:pPr>'.$paragraphDefaults.'</w:pPr></w:pPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            .'</w:styles>';
    }

    private function numberingXml(DocumentDocxProfile $profile): string
    {
        $baseIndent = $profile->isCasaCivil() ? 720 : 300;
        $levelIndent = $profile->isCasaCivil() ? 360 : 300;
        $hanging = $profile->isCasaCivil() ? 360 : 180;
        $levels = '';
        for ($level = 0; $level < 9; $level++) {
            $position = $baseIndent + ($level * $levelIndent);
            $levels .= '<w:lvl w:ilvl="'.$level.'"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="•"/>'
                .'<w:lvlJc w:val="left"/><w:pPr><w:tabs><w:tab w:val="num" w:pos="'.$position.'"/></w:tabs>'
                .'<w:ind w:left="'.$position.'" w:hanging="'.$hanging.'"/></w:pPr></w:lvl>';
        }
        $orderedLevels = '';
        for ($level = 0; $level < 9; $level++) {
            $position = $baseIndent + ($level * $levelIndent);
            $orderedLevels .= '<w:lvl w:ilvl="'.$level.'"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%'.($level + 1).'."/>'
                .'<w:lvlJc w:val="left"/><w:pPr><w:tabs><w:tab w:val="num" w:pos="'.$position.'"/></w:tabs>'
                .'<w:ind w:left="'.$position.'" w:hanging="'.$hanging.'"/></w:pPr></w:lvl>';
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
}
