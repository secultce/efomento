<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
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
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function profileSnapshots(): MorphMany
    {
        return $this->morphMany(ProfileSnapshot::class, 'object');
    }

    public function latestSnapshot(): MorphOne
    {
        return $this->morphOne(ProfileSnapshot::class, 'object')->latestOfMany('recorded_at');
    }
}
