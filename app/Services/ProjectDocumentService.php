<?php

namespace App\Services;

use App\Enums\DocumentImagePosition;
use App\Enums\DocumentImageSection;
use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectDocumentService
{
    public function createDocument(
        string $type,
        array $selectedProjects,
        string $content,
        array $headerImages = [],
        array $footerImages = [],
        string $headerLayout = 'none',
        string $footerLayout = 'none',
    ): void {
        DB::transaction(function () use (
            $type,
            $selectedProjects,
            $content,
            $headerImages,
            $footerImages,
            $headerLayout,
            $footerLayout
        ) {
            if (empty(trim($content))) {
                throw new \Exception('O conteúdo do documento não pode ser vazio.');
            }

            $processedHeader = $this->uploadFilesOnce($headerImages);
            $processedFooter = $this->uploadFilesOnce($footerImages);

            $projects = Project::whereIn('id', $selectedProjects)->get();

            if ($projects->isEmpty()) {
                throw new \Exception('Nenhum projeto selecionado.');
            }

            $documentType = match ($type) {
                'ci' => DocumentType::CI,
                'tc' => DocumentType::TC,
                default => throw new \Exception('Tipo de documento inválido.')
            };

            $phase = match ($type) {
                'ci' => DocumentPhase::OPENING,
                'tc' => DocumentPhase::FORMALIZATION,
                default => throw new \Exception('Tipo de documento inválido.')
            };

            foreach ($projects as $project) {

                $document = $project->documents()
                    ->where('type', $documentType)
                    ->where('phase', $phase)
                    ->first();

                if ($document) {

                    $document->update([
                        'body' => $content,
                    ]);

                    $existingImages = $document->images()
                        ->get()
                        ->groupBy(fn ($img) => $img->section->value)
                        ->map(fn ($group) => $group->keyBy('position'));

                    $this->syncImages(
                        $document,
                        $processedHeader,
                        DocumentImageSection::HEADER,
                        $existingImages,
                        $headerLayout === 'full'
                    );

                    $this->syncImages(
                        $document,
                        $processedFooter,
                        DocumentImageSection::FOOTER,
                        $existingImages,
                        $footerLayout === 'full'
                    );

                    continue;
                }

                $document = Document::create([
                    'notice_id' => $project->notice_id,
                    'project_id' => $project->id,
                    'type' => $documentType,
                    'phase' => $phase,
                    'body' => $content,
                    'created_by' => auth()->id(),
                ]);

                $this->storeImages(
                    $document,
                    $processedHeader,
                    DocumentImageSection::HEADER,
                    $headerLayout === 'full'
                );

                $this->storeImages(
                    $document,
                    $processedFooter,
                    DocumentImageSection::FOOTER,
                    $footerLayout === 'full'
                );
            }
        });
    }

    private function uploadFilesOnce(array $items): array
    {
        $processed = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $processed[$index] = $item;

                continue;
            }

            if (! empty($item['file'])) {
                $item['path'] = $item['file']->store('documents', 'public');
                unset($item['file']);
            }

            if (! empty($item['id']) && empty($item['_delete']) && empty($item['path'])) {
                $imageModel = (new Document)->images()->getRelated();
                $existingImgRecord = $imageModel->find($item['id']);

                if ($existingImgRecord) {
                    $item['path'] = $existingImgRecord->path;
                }
            }

            $processed[$index] = $item;
        }

        return $processed;
    }

    private function storeImages(
        Document $document,
        array $items,
        DocumentImageSection $section,
        bool $isFullWidth = false
    ): void {
        $positions = [
            0 => DocumentImagePosition::LEFT,
            1 => DocumentImagePosition::CENTER,
            2 => DocumentImagePosition::RIGHT,
        ];

        foreach ($items as $index => $item) {
            if (! is_array($item) || empty($item['path'])) {
                continue;
            }

            $position = $positions[$index] ?? null;

            if (! $position) {
                continue;
            }

            $document->images()->create([
                'section' => $section,
                'position' => $position,
                'path' => $item['path'],
                'is_full_width' => $isFullWidth,
            ]);
        }
    }

    private function syncImages(
        Document $document,
        array $items,
        DocumentImageSection $section,
        Collection $existingGrouped,
        bool $isFullWidth = false
    ): void {
        $positions = [
            0 => DocumentImagePosition::LEFT,
            1 => DocumentImagePosition::CENTER,
            2 => DocumentImagePosition::RIGHT,
        ];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $path = $item['path'] ?? null;
            $delete = ! empty($item['_delete']);
            $position = $positions[$index] ?? null;

            if (! $position) {
                continue;
            }

            $existing = $existingGrouped[$section->value][$position->value] ?? null;

            if ($delete) {
                if ($existing) {
                    $existing->delete();
                }

                continue;
            }

            if ($path) {
                if ($existing) {
                    $existing->update([
                        'is_full_width' => $isFullWidth,
                    ]);

                    if ($existing->path === $path) {
                        continue;
                    }

                    $existing->delete();
                }

                $document->images()->create([
                    'section' => $section,
                    'position' => $position,
                    'path' => $path,
                    'is_full_width' => $isFullWidth,
                ]);

                continue;
            }
        }
    }
}
