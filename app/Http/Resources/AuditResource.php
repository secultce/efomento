<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'user' => $this->user?->name ?? 'Sistema',
            'date' => $this->created_at,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
        ];
    }
}
