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
    public function createDocumentCI(array $selectedProjects, string $content)
    {
        DB::transaction(function () use ($selectedProjects, $content) {
            $projects = Project::with('opening')
                ->whereIn('id', $selectedProjects)
                ->get();

            if (empty($content)) {
                throw new \Exception(
                    'O conteúdo da comunicação interna não pode ser vazio.'
                );
            }

            if ($projects->isEmpty()) {
                throw new \Exception(
                    'Nenhum projeto selecionado.'
                );
            }

            foreach ($projects as $project) {

                if (
                    $project->documents()
                        ->where('type', 'ci')
                        ->where('phase', 'opening')
                        ->exists()
                ) {
                    $project->opening->updateCI($content);

                    continue;
                }

                $project->opening->createCI($content);
            }
        });
    }

    public function createDocumentTC(
        array $selectedProjects,
        string $content,
        array $headerImages = [],
        array $footerImages = []
    ): void {
        $processedHeader = $this->uploadFilesOnce($headerImages);
        $processedFooter = $this->uploadFilesOnce($footerImages);

        DB::transaction(function () use (
            $selectedProjects,
            $content,
            $processedHeader,
            $processedFooter
        ) {

            if (empty(trim($content))) {
                throw new \Exception('O conteúdo do termo não pode ser vazio.');
            }

            $projects = Project::whereIn('id', $selectedProjects)->get();

            if ($projects->isEmpty()) {
                throw new \Exception('Nenhum projeto selecionado.');
            }

            foreach ($projects as $project) {

                $document = $project->documents()
                    ->where('type', DocumentType::TC)
                    ->where('phase', DocumentPhase::FORMALIZATION)
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
                        $existingImages
                    );

                    $this->syncImages(
                        $document,
                        $processedFooter,
                        DocumentImageSection::FOOTER,
                        $existingImages
                    );

                    continue;
                }

                $document = Document::create([
                    'notice_id' => $project->notice_id,
                    'project_id' => $project->id,
                    'type' => DocumentType::TC,
                    'phase' => DocumentPhase::FORMALIZATION,
                    'body' => $content,
                    'created_by' => auth()->id(),
                ]);

                $this->storeImages($document, $processedHeader, DocumentImageSection::HEADER);
                $this->storeImages($document, $processedFooter, DocumentImageSection::FOOTER);
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
        DocumentImageSection $section
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
            ]);
        }
    }

    private function syncImages(
        Document $document,
        array $items,
        DocumentImageSection $section,
        Collection $existingGrouped
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
                    if ($existing->path === $path) {
                        continue;
                    }
                    $existing->delete();
                }

                $document->images()->create([
                    'section' => $section,
                    'position' => $position,
                    'path' => $path,
                ]);

                continue;
            }
        }
    }
}
