<?php

namespace App\Models;

use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class BudgetAllocation extends Model implements Auditable
{
    use AuditableTrait, HasCreatedBy, HasFactory, SoftDeletes;

    protected $fillable = [
        'notice_id',
        'management_unit',
        'work_program',
        'objective',
        'deliverable',
        'budget_function',
        'budget_subfunction',
        'project_activity',
        'expense_element',
        'funding_source',
        'mapp',
        'finalistic_project',
        'region_code',
        'planning_macroregion',
        'allocation_code',
        'allocation_number',
        'created_by',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }
}
