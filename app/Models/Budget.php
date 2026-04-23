<?php

namespace App\Models;

use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Budget extends Model implements Auditable
{
    use AuditableTrait, HasFactory, HasFiles, SoftDeletes;

    protected $fillable = [
        'project_id',
        'created_by',
        'processing_date_for_codip',
        'processing_date_for_coafi',
        'date_of_expense_breakdown_table',
        'processed_at',
    ];

    protected $casts = [
        'processing_date_for_codip' => 'date',
        'processing_date_for_coafi' => 'date',
        'date_of_expense_breakdown_table' => 'date',
        'processed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::deleting(function ($budget) {
            if ($budget->isForceDeleting()) {
                $budget->installments()->forceDelete();
            } else {
                $budget->installments()->delete();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }
}
