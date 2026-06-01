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
        'project.agent',
        'project.notice',
        'project.opening.activeSupervisor.user',
    ];

    public function download(Document $document, ?string $type = null): Response
    {
        ini_set('memory_limit', '512M');

        set_time_limit(120);

        $type = $type ?? $document->type->value;

        $document->loadMissing(self::RELATIONS);
        $document->body = $this->replacePlaceholders($document);

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
            $document->body = $this->replacePlaceholders($document);

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

    public function replacePlaceholders(Document $document): string
    {
        $replacements = [
            '[notice_name]' => $document->project?->notice?->name,
            '[nup_mother]' => $document->project?->notice?->nup,
            '[project_nup]' => $document->project?->opening->opening_nup,
            '[agent_name]' => $document->project?->agent?->name,
            '[agent_cpf]' => $document->project?->agent?->latestSnapshot?->cpf,
            '[agent_address]' => $document->project?->agent?->latestSnapshot?->street.', '.
                $document->project?->agent?->latestSnapshot?->number.' - '.
                $document->project?->agent?->latestSnapshot?->neighborhood.' - '.
                $document->project?->agent?->latestSnapshot?->city.'/'.
                $document->project?->agent?->latestSnapshot?->state,
            '[agent_email]' => $document->project?->agent?->latestSnapshot?->email,
            '[agent_phone]' => $document->project?->agent?->latestSnapshot?->phone,
            '[finality]' => $document->project?->notice?->instrument_type,
            '[fiscal_matricula]' => $document->project?->opening?->activeSupervisor?->user?->registration_number,
            '[fiscal_name]' => $document->project?->opening?->activeSupervisor?->user?->name,
            '[project_name]' => $document->project?->title_project,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $document->body
        );
    }
}
