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
        'effective_date_of_the_instrument',
        'expiration_date_of_the_instrument',
        'deadline_for_completing_report',
        'deadline_for_analysis_and_issuance_of_the_opinion',
        'date_of_processing_of_the_opinion_via_suite',
        'date_of_notification_to_the_agent',
        'observations',
        'processed_at',
    ];

    protected $casts = [
        'effective_date_of_the_instrument' => 'date',
        'expiration_date_of_the_instrument' => 'date',
        'deadline_for_completing_report' => 'date',
        'deadline_for_analysis_and_issuance_of_the_opinion' => 'date',
        'date_of_processing_of_the_opinion_via_suite' => 'date',
        'date_of_notification_to_the_agent' => 'date',
        'processed_at' => 'datetime',
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
