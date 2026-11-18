<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'cpf_cnpj' => $this->latestSnapshot?->cpf_cnpj,

            'director_position' => $this->director_position,
            'director_email' => $this->director_email,

            'profile' => $this->whenLoaded(
                'latestSnapshot',
                fn () => new ProfileSnapshotResource($this->latestSnapshot),
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
