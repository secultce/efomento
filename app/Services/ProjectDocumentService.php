<?php

namespace App\Services;

use App\Enums\DocumentImagePosition;
use App\Enums\DocumentImageSection;
use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Project;
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

        DB::transaction(function () use (
            $selectedProjects,
            $content,
            $headerImages,
            $footerImages
        ) {

            if (empty(trim($content))) {
                throw new \Exception(
                    'O conteúdo do termo não pode ser vazio.'
                );
            }

            $projects = Project::whereIn(
                'id',
                $selectedProjects
            )->get();

            if ($projects->isEmpty()) {
                throw new \Exception(
                    'Nenhum projeto selecionado.'
                );
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

                    $hasNewImages =
                        ! empty(array_filter($headerImages)) ||
                        ! empty(array_filter($footerImages));

                    if ($hasNewImages) {

                        foreach ($document->images as $image) {
                            $image->delete();
                        }

                        $this->storeImages(
                            $document,
                            $headerImages,
                            DocumentImageSection::HEADER
                        );

                        $this->storeImages(
                            $document,
                            $footerImages,
                            DocumentImageSection::FOOTER
                        );
                    }

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

                $this->storeImages(
                    $document,
                    $headerImages,
                    DocumentImageSection::HEADER
                );

                $this->storeImages(
                    $document,
                    $footerImages,
                    DocumentImageSection::FOOTER
                );
            }
        });
    }

    private function storeImages(
        Document $document,
        array $files,
        DocumentImageSection $section
    ): void {

        $positions = [
            DocumentImagePosition::LEFT,
            DocumentImagePosition::CENTER,
            DocumentImagePosition::RIGHT,
        ];

        foreach ($files as $index => $file) {

            if (! $file) {
                continue;
            }

            $path = $file->store(
                'documents',
                'public'
            );

            $document->images()->create([
                'section' => $section,
                'position' => $positions[$index],
                'path' => $path,
            ]);
        }
    }
}
