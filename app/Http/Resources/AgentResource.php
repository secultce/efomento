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
            'cpf' => $this->cpf,

            'director_position' => $this->director_position,
            'director_email' => $this->director_email,

            'phone' => $this->phone,
            'email' => $this->email,

            'birth_date' => $this->birth_date?->format('Y-m-d'),
            
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'postal_code' => $this->postal_code,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            
            'gender' => $this->gender,
            'education' => $this->education,

            'sexual_orientation' => $this->sexual_orientation?->value,
            'race_or_color' => $this->race_or_color?->value,
            'has_disability' => $this->has_disability?->value,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}