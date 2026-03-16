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

            'opening_nup' => $this->opening_nup,
            'opening_date' => $this->opening_date?->format('Y-m-d'),

            'agent_status' => $this->agent_status,
            'opened_by' => $this->opened_by,

            'bank' => $this->bank,
            'account_type' => $this->account_type,
            'branch' => $this->branch,
            'account' => $this->account,

            'is_draft' => $this->is_draft,
            'status' => $this->status,

            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'concluded_at' => $this->concluded_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}