<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentImage;
use Illuminate\Support\Collection;

class DocumentService
{
    public function __construct(
        private readonly DocumentTypeRegistry $registry
    ) {}

    public function create(array $data, int $createdBy): Document
    {
        $this->registry->resolve($data['type'], $data['phase']);

        $document = Document::create([
            'notice_id'  => $data['notice_id'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'type'       => $data['type'],
            'phase'      => $data['phase'],
            'body'       => $data['body'] ?? null,
            'status'     => $data['status'] ?? Document::STATUS_DRAFT,
            'created_by' => $createdBy,
        ]);

        if (!empty($data['images'])) {
            $this->syncImages($document, $data['images']);
        }

        return $document;
    }

    public function update(Document $document, array $data): Document
    {
        $document->update(array_filter([
            'body'   => $data['body'] ?? $document->body,
            'status' => $data['status'] ?? $document->status,
        ], fn ($value) => $value !== null));

        if (!empty($data['images'])) {
            $this->syncImages($document, $data['images']);
        }

        return $document->fresh();
    }

    public function syncImages(Document $document, array $images): void
    {
        foreach ($images as $image) {
            DocumentImage::updateOrCreate(
                [
                    'document_id' => $document->id,
                    'section'     => $image['section'],
                    'position'    => $image['position'],
                ],
                ['path' => $image['path']]
            );
        }

        $incomingSlots = collect($images)
            ->map(fn ($img) => $img['section'].'|'.$img['position'])
            ->all();

        $document->images()
            ->get()
            ->filter(fn ($img) => !in_array($img->section.'|'.$img->position, $incomingSlots))
            ->each->delete();
    }

    public function getByContext(
        int $noticeId = null,
        int $projectId = null,
        string $type = null,
        string $phase = null
    ): Collection {
        return Document::query()
            ->when($noticeId,   fn ($q) => $q->where('notice_id', $noticeId))
            ->when($projectId,  fn ($q) => $q->where('project_id', $projectId))
            ->when($type,       fn ($q) => $q->where('type', $type))
            ->when($phase,      fn ($q) => $q->where('phase', $phase))
            ->with('images')
            ->get();
    }

    public function delete(Document $document): void
    {
        $document->delete();
    }
}
