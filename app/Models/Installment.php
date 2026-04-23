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
        'amount',
        'request_date',
        'justification',
        'observations',
        'installment_number',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_date' => 'date',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
