<?php

namespace App\Models;

use App\Traits\HasCreatedBy;
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
    use AuditableTrait, HasCreatedBy, HasFactory, HasFiles, SoftDeletes;

    protected $fillable = [
        'project_id',
        'budget_allocation_id',
        'created_by',
        'processing_date_for_codip',
        'processing_date_for_coafi',
    ];

    protected $casts = [
        'processing_date_for_codip' => 'date',
        'processing_date_for_coafi' => 'date',
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

    public function budgetAllocation(): BelongsTo
    {
        return $this->belongsTo(BudgetAllocation::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }
}
