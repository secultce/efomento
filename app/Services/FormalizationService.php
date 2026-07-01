<?php

namespace App\Services;

use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Models\File;
use App\Models\Formalization;
use App\Models\Project;
use BackedEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FormalizationService
{
    private const OFFICIAL_GAZETTE_FILE_GROUP = 'official_gazette';

    public function saveOfficialGazetteFile(
        UploadedFile $uploadedFile,
        Project $project,
        Formalization $formalization
    ): void {
        $disk = config('filesystems.default', 'local');

        $formalization->files()
            ->where('grp', self::OFFICIAL_GAZETTE_FILE_GROUP)
            ->get()
            ->each(fn (File $file) => $this->deleteFile($file));

        $path = $uploadedFile->store(
            "projects/{$project->id}/formalizations/{$formalization->id}/official-gazette",
            $disk
        );

        $formalization->files()->create([
            'mime_type' => $uploadedFile->getClientMimeType(),
            'name' => $uploadedFile->getClientOriginalName(),
            'source' => 'upload',
            'grp' => self::OFFICIAL_GAZETTE_FILE_GROUP,
            'title' => 'Anexo do documento do Diário Oficial do Estado',
            'path' => $path,
            'private' => true,
        ]);
    }

    public function deleteFile(File $file): void
    {
        $disk = config('filesystems.default', 'local');

        if ($file->path) {
            Storage::disk($disk)->delete($file->path);
        }

        $file->delete();
    }

    public function ensureCanAdvance(Project $project): void
    {
        $formalization = $project->formalizations;

        if (! $formalization) {
            throw ValidationException::withMessages([
                'formalization' => 'Preencha e salve os dados da formalização antes de tramitar.',
            ]);
        }

        $this->validateRequiredFields($formalization);

        $this->validateRequiredDocuments($project);
    }

    private function validateRequiredFields(Formalization $formalization): void
    {
        $requiredFields = [
            'asjur_finalistic_processing_date' => 'Data de tramitação da finalística para a ASJUR',
            'asjur_received_at' => 'Data de recebimento do processo pela ASJUR',
            'process_assigned_to' => 'Processo distribuído para',
            'report_status' => 'Informe regularidade e inadimplência',
            'eparcerias_certificate_date' => 'Data da certidão',

            'asjur_processing_date' => 'Data de tramitação na ASJUR',
            'responsible_at_asjur' => 'Responsável (Distribuido para)',
            'term_number' => 'Número do termo',

            'term_signature_sent_at' => 'Data do envio para assinatura do termo',
            'term_signed_at' => 'Data da assinatura do termo',
            'sent_to_office_at' => 'Data de envio para Gabinete',
            'signed_by_office_at' => 'Data de assinatura do termo pelo Gabinete',

            'sacc_number' => 'Número do SACC',
            'cge_atende_ticket' => 'Chamado CGE atende',
            'deliberation' => 'Deliberação',

            'sent_to_chief_of_staff_at' => 'Data de envio para Casa Civil',
            'official_gazette_published_at' => 'Data de Publicação do Diário Oficial do Estado',

            'validity_start_at' => 'Data de início da vigência do instrumento',
            'validity_end_at' => 'Data de término da vigência do instrumento',

            'legal_opinion_date' => 'Data do parecer jurídico',
        ];

        $missingFields = collect($requiredFields)
            ->filter(fn ($label, $field) => blank($formalization->{$field}))
            ->values();

        // Verifica se o anexo do diário oficial foi salvo
        $hasOfficialGazetteFile = $formalization->files()
            ->where('grp', self::OFFICIAL_GAZETTE_FILE_GROUP)
            ->exists();

        if (! $hasOfficialGazetteFile) {
            $missingFields->push('Anexo do documento do Diário Oficial do Estado');
        }

        if ($missingFields->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'formalization' => 'Preencha todos os campos obrigatórios antes de tramitar: '
                .$missingFields->join(', ')
                .'.',
        ]);
    }

    private function validateRequiredDocuments(Project $project): void
    {
        $requiredDocuments = collect(
            DocumentType::requiredForFormalizationAdvance()
        );

        $requiredDocumentTypes = $requiredDocuments
            ->map(fn (DocumentType $type) => $type->value)
            ->all();

        $generatedDocumentTypes = $project->documents()
            ->where('phase', DocumentPhase::FORMALIZATION->value)
            ->whereIn('type', $requiredDocumentTypes)
            ->pluck('type')
            ->map(
                fn ($type) => $type instanceof BackedEnum
                    ? $type->value
                    : trim((string) $type)
            )
            ->unique();

        $missingDocuments = $requiredDocuments
            ->reject(
                fn (DocumentType $type) => $generatedDocumentTypes
                    ->containsStrict($type->value)
            )
            ->map(fn (DocumentType $type) => $type->fullLabel())
            ->values();

        if ($missingDocuments->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'documents' => 'Gere os documentos obrigatórios antes de tramitar: '
                .$missingDocuments->join(', ')
                .'.',
        ]);
    }
}
