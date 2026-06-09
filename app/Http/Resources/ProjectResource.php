<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        if ($this->relationLoaded('documents')) {
            $data['documents'] = DocumentResource::collection($this->documents)->resolve();
        }

        if ($this->relationLoaded('monitoring')) {
            $data['monitoring'] = $this->monitoring;
        }

        return $data;
    }
}
