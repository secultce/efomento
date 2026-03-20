<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Education;
use App\Enums\SexualOrientation;
use App\Enums\RaceColor;
use App\Enums\DisabilityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Project extends Model  implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $fillable = [
        'registration_id',
        'number',
        'category_id',
        'agent_id',
        'notice_id',
        'education',
        'gender',
        'sexual_orientation',
        'race',
        'has_disability',
        'create_timestamp',
        'sent_timestamp',
        'consolidated_result',
        'data_registration',
    ];

    protected $casts = [
        'education_level' => Education::class,
        'gender' => Gender::class,
        'sexual_orientation' => SexualOrientation::class,
        'race' => RaceColor::class,
        'has_disability' => DisabilityType::class,
        'create_timestamp' => 'datetime',
        'sent_timestamp' => 'datetime',
        'data_registration' => 'array',
    ];

    protected $appends = ['phase', 'opening_nup'];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function openings()
    {
        return $this->hasMany(Opening::class);
    }

    public function opening()
    {
        return $this->hasOne(Opening::class)
            ->latestOfMany('created_at');
    }

    public function getPhaseAttribute()
    {
        if (array_key_exists('openings_count', $this->attributes)) {
            return $this->openings_count > 0
                ? 'Abertura'
                : 'Não Iniciado';
        }

        return $this->openings()->exists()
            ? 'Abertura'
            : 'Não Iniciado';
    }

    public function getOpeningNupAttribute()
    {
        return $this->opening?->opening_nup;
    }
}
