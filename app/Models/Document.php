<?php

namespace App\Models;

use App\Enums\DocumentPhase;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Document extends Model  implements Auditable
{
    use AuditableTrait, HasCreatedBy, HasFactory, SoftDeletes;

    protected $fillable = [
        'notice_id',
        'project_id',
        'type',
        'phase',
        'body',
        'status',
        'created_by',
    ];

    protected $casts = [
        'type'   => DocumentType::class,
        'phase'  => DocumentPhase::class,
        'status' => DocumentStatus::class,
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(DocumentImage::class);
    }
}
