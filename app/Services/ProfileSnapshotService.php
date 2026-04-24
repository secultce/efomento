<?php

namespace App\Services;

use App\Enums\ProfileSnapshotSource;
use App\Models\ProfileSnapshot;
use BackedEnum;
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

    public function recordIfChanged(Model $model, array $data, ProfileSnapshotSource $source): ?ProfileSnapshot
    {
        $payload = array_intersect_key($data, array_flip(self::FIELDS));
        $latest = $model->latestSnapshot;

        if ($latest !== null && $this->isSameData($latest, $payload)) {
            return null;
        }

        return $model->profileSnapshots()->create([
            ...$payload,
            'source' => $source,
            'recorded_at' => now(),
        ]);
    }

    private function isSameData(ProfileSnapshot $snapshot, array $incoming): bool
    {
        foreach ($incoming as $field => $value) {
            $stored = $snapshot->getRawOriginal($field);
            $normalized = $value instanceof BackedEnum ? $value->value : $value;

            if ($stored !== $normalized) {
                return false;
            }
        }

        return true;
    }
}
