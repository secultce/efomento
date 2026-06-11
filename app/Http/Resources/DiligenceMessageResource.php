<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiligenceMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction->value,
            'from_email' => $this->from_email,
            'to_email' => $this->to_email,
            'subject' => $this->subject,
            'body' => $this->body,
            'sent_at' => $this->sent_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'creator' => $this->creator?->name,
        ];
    }
}
