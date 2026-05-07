<?php

namespace App\Services\Documents;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class DocumentPdfService
{
    private const RELATIONS = ['project.agent', 'project.notice', 'project.opening.activeSupervisor.user'];

    public function download(Document $document): \Illuminate\Http\Response
    {
        $document->loadMissing(self::RELATIONS);
        $document->body = $this->replacePlaceholders($document);

        return Pdf::loadView('pdf.ci', ['document' => $document])
            ->setPaper('a4', 'portrait')
            ->download($this->generateFilename($document));
    }

    public function buildZip(array $projectIds): string
    {
        $documents = Document::with(self::RELATIONS)
            ->whereIn('project_id', $projectIds)
            ->get();

        $tempFile = tempnam(sys_get_temp_dir(), 'docs_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($documents as $document) {
            $document->body = $this->replacePlaceholders($document);
            $pdf = Pdf::loadView('pdf.ci', ['document' => $document])->setPaper('a4', 'portrait');
            $zip->addFromString($this->generateFilename($document), $pdf->output());
        }

        $zip->close();

        return $tempFile;
    }

    public function generateFilename(Document $document): string
    {
        $agentName = $document->project?->agent?->name ?? 'documento';

        return 'CI_' . str($agentName)->slug('_') . '_' . $document->created_at->format('Y-m-d') . '_' . $document->created_at->timestamp . '.pdf';
    }

    public function replacePlaceholders(Document $document): string
    {
        $replacements = [
            '[notice_name]'      => $document->project?->notice?->name,
            '[nup_mother]'       => $document->project?->notice?->nup,
            '[agent_name]'       => $document->project?->agent?->name,
            '[finality]'         => $document->project?->notice?->instrument_type,
            '[fiscal_matricula]' => $document->project?->opening?->activeSupervisor?->user?->registration_number ?? 'sem matrícula',
            '[fiscal_name]'      => $document->project?->opening?->activeSupervisor?->user?->name ?? 'sem fiscal',
            '[project_name]'     => $document->project?->title_project ?? 'sem projeto',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $document->body
        );
    }
}