<?php

namespace App\Http\Resources;

use App\Services\Documents\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notice_id' => $this->notice_id,
            'project_id' => $this->project_id,
            'type' => $this->type,
            'type_label' => $this->type->label(),
            'type_name' => $this->type->fullLabel(),
            'phase' => $this->phase,
            'body' => $this->body,
            'resolved_body' => app(DocumentPdfService::class)->replacePlaceholders($this->resource),
            'status' => $this->status,
            'created_by' => $this->created_by,
            'images' => DocumentImageResource::collection($this->images)->resolve(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
