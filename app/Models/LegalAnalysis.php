<?php

namespace App\Models;

use App\Enums\FileStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class LegalAnalysis extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes, HasFactory;

    protected $table = 'legal_analyses';

    protected $fillable = [
        'project_id',
        'created_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(LegalAnalysisFile::class);
    }

    public function getFileStatus(int $fileId): ?FileStatus
    {
        $file = $this->files()->where('file_id', $fileId)->first();

        return $file ? $file->status : null;
    }
}
