<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    // Types
    const TYPE_TERM = 'term';
    const TYPE_EXTRACT = 'extract';
    const TYPE_JURIDICAL_OPINION = 'juridical_opinion';
    const TYPE_DISPATCH = 'dispatch';

    // Phases
    const PHASE_OPENING = 'opening';
    const PHASE_JURIDICAL = 'juridical';
    const PHASE_FORMALIZATION = 'formalization';
    const PHASE_BUDGET = 'budget';
    const PHASE_PAYMENT = 'payment';
    const PHASE_MONITORING = 'monitoring';

    // Statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_SIGNATURE = 'pending_signature';
    const STATUS_SIGNED = 'signed';

    protected $fillable = [
        'notice_id',
        'project_id',
        'type',
        'phase',
        'body',
        'status',
        'created_by',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(DocumentImage::class);
    }
}
