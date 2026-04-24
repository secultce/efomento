<?php

namespace App\Models;

use App\Enums\DisabilityType;
use App\Enums\Education;
use App\Enums\Gender;
use App\Enums\ProfileSnapshotSource;
use App\Enums\RaceColor;
use App\Enums\SexualOrientation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProfileSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'object_id',
        'object_type',
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
        'recorded_at',
        'source',
    ];

    protected $casts = [
        'gender' => Gender::class,
        'sexual_orientation' => SexualOrientation::class,
        'race' => RaceColor::class,
        'education' => Education::class,
        'has_disability' => DisabilityType::class,
        'source' => ProfileSnapshotSource::class,
        'recorded_at' => 'datetime',
    ];

    public function object(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeLatestSnapshot(Builder $query): Builder
    {
        return $query->orderByDesc('recorded_at');
    }
}
