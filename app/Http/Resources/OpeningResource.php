<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpeningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'supervisor_id' => $this->supervisor_id,

            'opening_nup' => $this->opening_nup,
            'opening_date' => $this->opening_date?->format('Y-m-d'),

            'agent_status' => $this->agent_status?->value,
            'opened_by' => $this->opened_by,
            'created_by' => $this->created_by,

            'creditor_number' => $this->creditor_number,

            'bank' => $this->bank,
            'account_type' => $this->account_type?->value,
            'branch' => $this->branch,
            'account' => $this->account,

            'is_draft' => $this->is_draft,
            'status' => $this->status?->value,

            'certificate_date' => $this->certificate_date?->format('Y-m-d H:i:s'),

            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'concluded_at' => $this->concluded_at?->format('Y-m-d H:i:s'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
