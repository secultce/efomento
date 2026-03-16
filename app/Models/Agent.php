<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Education;
use App\Enums\SexualOrientation;
use App\Enums\RaceColor;
use App\Enums\DisabilityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'cpf',
        'director_position',
        'director_email',
        'phone',
        'email',
        'birth_date',
        'street',
        'number',
        'complement',
        'postal_code',
        'neighborhood',
        'city',
        'state',
        'gender',
        'education',
        'sexual_orientation',
        'race_or_color',
        'has_disability',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'gender' => Gender::class,
        'education' => Education::class,
        'sexual_orientation' => SexualOrientation::class,
        'race_or_color' => RaceColor::class,
        'has_disability' => DisabilityType::class,
    ];

    /**
     * Get the projects for the agent.
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class);
    }
}