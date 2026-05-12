<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'project_id' => $this->project_id,

            'creditor_requested_at' => $this->creditor_requested_at?->format('Y-m-d'),
            'creditor_registration_number' => $this->creditor_registration_number,
            'communication_sent_at' => $this->communication_sent_at?->format('Y-m-d'),
            'contact_notes' => $this->contact_notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'project' => $this->whenLoaded('project', fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
        ];
    }
}
