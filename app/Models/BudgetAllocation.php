<?php

namespace App\Models;

use App\Enums\ExpenseNature;
use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'expense_nature',
        'funding_source',
        'mapp',
        'finalistic_project',
        'region_code',
        'planning_macroregion',
        'allocation_code',
        'allocation_number',
        'created_by',
    ];

    protected $casts = [
        'expense_nature' => ExpenseNature::class,
    ];

    protected $appends = [
        'expense_nature_label',
    ];

    protected function expenseNatureLabel(): Attribute
    {
        return Attribute::get(fn () => $this->expense_nature?->label());
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }
}
