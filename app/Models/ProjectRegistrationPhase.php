<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Sem Auditable: o JSON de `data` é grande e não deve ir para a tabela `audits`.
class ProjectRegistrationPhase extends Model
{
    protected $fillable = [
        'project_id',
        'registration_id',
        'opportunity_id',
        'phase_order',
        'is_first',
        'status',
        'sent_at',
        'registration_from',
        'registration_to',
        'data',
        'content_hash',
        'fetched_at',
    ];

    protected $casts = [
        'is_first' => 'boolean',
        'sent_at' => 'date',
        'registration_from' => 'datetime',
        'registration_to' => 'datetime',
        'data' => 'array',
        'fetched_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
