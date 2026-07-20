<?php

namespace App\Services\Documents;

use App\Enums\DocumentPhase;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\DocumentImage;
use Illuminate\Support\Collection;

class DocumentService
{
    private const PREVIEW_RELATIONS = [
        'images',
        'project.agent.latestSnapshot',
        'project.notice',
        'project.opening.principalSupervisor.user',
    ];

    public function __construct(
        private readonly DocumentTypeRegistry $registry
    ) {}

    public function create(array $data, int $createdBy): Document
    {
        $this->registry->resolve(
            DocumentType::from($data['type']),
            DocumentPhase::from($data['phase']),
        );

        $document = Document::create([
            'notice_id' => $data['notice_id'],
            'project_id' => $data['project_id'],
            'type' => $data['type'],
            'phase' => $data['phase'],
            'body' => $data['body'],
            'status' => $data['status'] ?? DocumentStatus::DRAFT,
            'created_by' => $createdBy,
        ]);

        if (! empty($data['images'])) {
            $this->syncImages($document, $data['images']);
        }

        return $document;
    }

    public function update(Document $document, array $data): Document
    {
        $document->update([
            'body' => $data['body'] ?? $document->body,
            'status' => $data['status'] ?? $document->status,
        ]);

        if (! empty($data['images'])) {
            $this->syncImages($document, $data['images']);
        }

        return $document->fresh();
    }

    public function getByContext(
        ?int $noticeId = null,
        ?int $projectId = null,
        ?string $type = null,
        ?string $phase = null
    ): Collection {
        return Document::query()
            ->when($noticeId, fn ($q) => $q->where('notice_id', $noticeId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($phase, fn ($q) => $q->where('phase', $phase))
            ->with(self::PREVIEW_RELATIONS)
            ->get();
    }

    private function syncImages(Document $document, array $images): void
    {
        foreach ($images as $image) {
            DocumentImage::updateOrCreate(
                [
                    'document_id' => $document->id,
                    'section' => $image['section'],
                    'position' => $image['position'],
                ],
                ['path' => $image['path']]
            );
        }

        $incomingSlots = collect($images)
            ->map(fn ($img) => $img['section'].'|'.$img['position'])
            ->all();

        $document->images()
            ->get()
            ->filter(fn ($img) => ! in_array($img->section->value.'|'.$img->position->value, $incomingSlots))
            ->each->delete();
    }
}
