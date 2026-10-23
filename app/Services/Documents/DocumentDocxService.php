<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Services\Documents\Docx\DocumentDocxPackageBuilder;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
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

        $tempFile = $this->temporaryFile('docs_');
        $zip = new ZipArchive;

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempFile);

            throw new RuntimeException('Não foi possível criar o arquivo ZIP.');
        }

        try {
            foreach ($documents as $document) {
                $docxPath = $this->packageBuilder->build($document, $profile);

                try {
                    $contents = file_get_contents($docxPath);
                    if ($contents === false) {
                        throw new RuntimeException('Não foi possível ler o documento temporário.');
                    }

                    $entryName = $document->project_id.'_'.$document->id.'_'
                        .$this->generateFilename($document, $type, $profile);
                    if (! $zip->addFromString($entryName, $contents)) {
                        throw new RuntimeException('Não foi possível adicionar um documento ao arquivo ZIP.');
                    }
                } finally {
                    @unlink($docxPath);
                }
            }

            if (! $zip->close()) {
                throw new RuntimeException('Não foi possível finalizar o arquivo ZIP.');
            }
        } catch (Throwable $exception) {
            $zip->close();
            @unlink($tempFile);

            throw $exception;
        }

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

    private function temporaryFile(string $prefix): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), $prefix);

        if ($temporaryFile === false) {
            throw new RuntimeException('Não foi possível criar um arquivo temporário.');
        }

        return $temporaryFile;
    }
}
