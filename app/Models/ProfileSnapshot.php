<?php

namespace App\Models;

use App\Enums\ProfileSnapshotSource;
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
        'cpf_cnpj',
        'gender',
        'sexual_orientation',
        'race',
        'education',
        'has_disability',
        'phone',
        'secondary_phone',
        'email',
        'secondary_email',
        'birth_date',
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
        'birth_date' => 'date',
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
