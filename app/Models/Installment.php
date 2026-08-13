<?php

namespace App\Models;

use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Installment extends Model implements Auditable
{
    use AuditableTrait, HasCreatedBy, HasFactory, SoftDeletes;

    protected $fillable = [
        'budget_id',
        'budget_allocation_id',
        'amount',
        'request_date',
        'justification',
        'observations',
        'installment_number',
        'notice_installment_number',

        'commitment_number',
        'commitment_date',

        'settlement_number',
        'settlement_date',
        'settlement_amount',

        'payment_order_number',
        'payment_amount',
        'payment_date',

        'full_source',
        'expense_nature',
        'process_number',
        'creditor',
        'creditor_name',
        'retention_creditor',
        'origin_bank_domicile',
        'committed_amount',

        'remarks',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'budget_allocation_id' => 'integer',
        'amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'installment_number' => 'integer',
        'notice_installment_number' => 'integer',
        'request_date' => 'date',
        'commitment_date' => 'date',
        'settlement_date' => 'date',
        'payment_date' => 'date',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function budgetAllocation(): BelongsTo
    {
        return $this->belongsTo(BudgetAllocation::class, 'budget_allocation_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
