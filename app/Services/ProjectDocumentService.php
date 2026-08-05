<?php

namespace App\Services;

use App\Enums\DocumentImagePosition;
use App\Enums\DocumentImageSection;
use App\Enums\DocumentType;
use App\Exceptions\Domain\BusinessRuleException;
use App\Models\Document;
use App\Models\Project;
use App\Support\ImageSync;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectDocumentService
{
    public function createDocument(
        DocumentType $type,
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
                throw new BusinessRuleException('O conteúdo do documento não pode ser vazio.');
            }

            $projects = Project::whereIn('id', $selectedProjects)->get();
            $phase = $type->phase();

            if ($projects->isEmpty()) {
                throw new BusinessRuleException('Nenhum projeto selecionado.');
            }

            $documents = Document::whereIn('project_id', $projects->pluck('id'))
                ->where('type', $type)
                ->where('phase', $phase)
                ->with('images')
                ->get()
                ->keyBy('project_id');

            foreach ($projects as $project) {

                $document = $documents->get($project->id);

                if ($document) {

                    $document->update([
                        'body' => $content,
                    ]);

                    $processedHeader = ImageSync::handle($headerImages, fn () => $document->images);
                    $processedFooter = ImageSync::handle($footerImages, fn () => $document->images);

                    $existingImages = $document->images
                        ->groupBy(fn ($img) => $img->section->value)
                        ->map(fn ($group) => $group->keyBy('position'));

                    $this->persistImages(
                        $document,
                        $processedHeader,
                        DocumentImageSection::HEADER,
                        $existingImages,
                        $headerLayout === 'full'
                    );

                    $this->persistImages(
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
                    'type' => $type,
                    'phase' => $phase,
                    'body' => $content,
                    'created_by' => auth()->id(),
                ]);

                $processedHeader = ImageSync::handle($headerImages, fn () => $document->images);
                $processedFooter = ImageSync::handle($footerImages, fn () => $document->images);

                $this->persistImages(
                    $document,
                    $processedHeader,
                    DocumentImageSection::HEADER,
                    null,
                    $headerLayout === 'full'
                );

                $this->persistImages(
                    $document,
                    $processedFooter,
                    DocumentImageSection::FOOTER,
                    null,
                    $footerLayout === 'full'
                );
            }
        });
    }

    private function persistImages(
        Document $document,
        array $items,
        DocumentImageSection $section,
        ?Collection $existingGrouped = null,
        bool $isFullWidth = false
    ): void {
        $isSync = $existingGrouped !== null;

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $position = DocumentImagePosition::fromIndex($index);

            if (! $position) {
                continue;
            }

            $path = $item['path'] ?? null;
            $delete = ! empty($item['_delete']);

            if ($isSync) {
                $existing = $existingGrouped[$section->value][$position->value] ?? null;

                if ($delete) {
                    $existing?->delete();

                    continue;
                }

                if ($path) {
                    if ($existing && $existing->path === $path) {
                        $existing->update(['is_full_width' => $isFullWidth]);

                        continue;
                    }

                    if ($existing) {
                        $existing->delete();
                    }

                    $document->images()->create([
                        'section' => $section,
                        'position' => $position,
                        'path' => $path,
                        'is_full_width' => $isFullWidth,
                    ]);
                }

                continue;
            }

            if (! $path) {
                continue;
            }

            $document->images()->create([
                'section' => $section,
                'position' => $position,
                'path' => $path,
                'is_full_width' => $isFullWidth,
            ]);
        }
    }
}
