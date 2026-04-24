<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'gender' => $this->gender,
            'sexual_orientation' => $this->sexual_orientation?->value,
            'race' => $this->race?->value,
            'education' => $this->education?->value,
            'has_disability' => $this->has_disability?->value,

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

            'source' => $this->source->value,
            'recorded_at' => $this->recorded_at?->toISOString(),
        ];
    }
}
