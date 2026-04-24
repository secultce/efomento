<?php

namespace App\Services;

use App\Enums\ProfileSnapshotSource;
use App\Models\ProfileSnapshot;
use Illuminate\Database\Eloquent\Model;

class ProfileSnapshotService
{
    private const FIELDS = [
        'gender',
        'sexual_orientation',
        'race',
        'education',
        'has_disability',
        'street',
        'number',
        'complement',
        'postal_code',
        'neighborhood',
        'city',
        'state',
    ];

    public function record(Model $model, array $data, ProfileSnapshotSource $source): ProfileSnapshot
    {
        $payload = array_intersect_key($data, array_flip(self::FIELDS));

        return $model->profileSnapshots()->create([
            ...$payload,
            'source' => $source,
            'recorded_at' => now(),
        ]);
    }
}
