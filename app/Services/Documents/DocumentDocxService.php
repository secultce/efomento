<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Services\Documents\Docx\DocumentDocxPackageBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DocumentDocxService
{
    public const PROFILE_STANDARD = 'standard';

    public const PROFILE_CASA_CIVIL = 'casa_civil';

    public function __construct(
        private readonly DocumentDocxPackageBuilder $packageBuilder,
    ) {}

    public function download(
        Document $document,
        ?string $type = null,
        string $profile = self::PROFILE_STANDARD,
    ): BinaryFileResponse {
        $path = $this->packageBuilder->build($document, $profile);

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
        $documents = Document::with(DocumentDocxPackageBuilder::RELATIONS)
            ->whereIn('project_id', $projectIds)
            ->where('type', $type)
            ->get();

        $tempFile = tempnam(sys_get_temp_dir(), 'docs_').'.zip';
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($documents as $document) {
            $docxPath = $this->packageBuilder->build($document, $profile);
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
}
