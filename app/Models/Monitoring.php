<?php

namespace App\Models;

use App\Traits\HasCreatedBy;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Monitoring extends Model implements Auditable
{
    use AuditableTrait, HasCreatedBy, HasFactory, HasFiles, SoftDeletes;

    protected $fillable = [
        'project_id',
        'created_by',
        'technical_opinions',
        'observations',
        'data_registration',
    ];

    protected $casts = [
        'technical_opinions' => 'array',
        'data_registration' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function diligenceMessages(): MorphMany
    {
        return $this->morphMany(DiligenceMessage::class, 'diligenceable')->orderBy('sent_at');
    }
}
