<?php

namespace App\Models;

use App\Enums\FileStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class LegalAnalysisFile extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $fillable = [
        'legal_analysis_id',
        'file_id',
        'status_id',
    ];

    protected $casts = [
        'status_id' => FileStatus::class,
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(LegalAnalysis::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
