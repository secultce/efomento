<?php

namespace App\Services;

use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Http\Requests\Formalization\FormalizationStoreRequest;
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
        $requiredFields = FormalizationStoreRequest::REQUIRED_FIELDS;

        $missingFields = collect($requiredFields)
            ->filter(fn ($label, $field) => blank($formalization->{$field}))
            ->values();

        if ($missingFields->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'formalization' => 'Preencha os campos obrigatórios antes de tramitar: '
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
