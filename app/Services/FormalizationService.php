<?php

namespace App\Services;

use App\Contracts\StageValidatorInterface;
use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Models\File;
use App\Models\Formalization;
use App\Models\Project;
use BackedEnum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FormalizationService implements StageValidatorInterface
{
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
            'term_number' => 'Número do termo',
            'term_signed_at' => 'Data da assinatura do termo',
            'signed_by_office_at' => 'Data de assinatura do termo pelo Gabinete',
            'sacc_number' => 'Número do SACC',
            'official_gazette_published_at' => 'Data de Publicação do Diário Oficial do Estado',
            'validity_start_at' => 'Data de início da vigência do instrumento',
            'validity_end_at' => 'Data de término da vigência do instrumento',
        ];

        $missingFields = collect($requiredFields)
            ->filter(fn ($label, $field) => blank($formalization->{$field}))
            ->values();

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
