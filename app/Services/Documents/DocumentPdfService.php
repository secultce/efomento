<?php

namespace App\Services\Documents;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use ZipArchive;

class DocumentPdfService
{
    private const RELATIONS = [
        'images',
        ...DocumentPlaceholderResolver::RELATIONS,
    ];

    public function __construct(
        private readonly DocumentPlaceholderResolver $placeholderResolver,
    ) {}

    public function download(Document $document, ?string $type = null): Response
    {
        ini_set('memory_limit', '512M');

        set_time_limit(120);

        $type = $type ?? $document->type->value;

        $document->loadMissing(self::RELATIONS);
        $document->body = $this->placeholderResolver->resolve($document);

        return Pdf::loadView('pdf.document', ['document' => $document])
            ->setPaper('a4', 'portrait')
            ->download($this->generateFilename($document, $type));
    }

    public function buildZip(array $projectIds, string $type): string
    {
        ini_set('memory_limit', '512M');

        set_time_limit(120);

        $documents = Document::with(self::RELATIONS)
            ->whereIn('project_id', $projectIds)
            ->where('type', $type)
            ->get();

        $tempFile = tempnam(sys_get_temp_dir(), 'docs_').'.zip';
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($documents as $document) {
            $document->body = $this->placeholderResolver->resolve($document);

            $pdf = Pdf::loadView('pdf.document', ['document' => $document])
                ->setPaper('a4', 'portrait');

            $zip->addFromString($this->generateFilename($document, $type), $pdf->output());
        }

        $zip->close();

        return $tempFile;
    }

    public function generateFilename(Document $document, ?string $type = null): string
    {
        $type = $type ?? $document->type->value;
        $agentName = $document->project?->agent?->name ?? 'documento';

        return strtoupper($type).'_'.str($agentName)->slug('_').'_'.$document->created_at->format('Y-m-d').'_'.$document->created_at->timestamp.'.pdf';
    }
}
