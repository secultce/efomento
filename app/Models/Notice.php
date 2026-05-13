<?php

namespace App\Models;

use App\Traits\HasCreatedBy;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Notice extends Model implements Auditable
{
    use AuditableTrait, HasCreatedBy, HasFactory, HasFiles, SoftDeletes;

    protected $table = 'notices';

    protected $fillable = [
        'nup',
        'notice_url',
        'external_id',
        'name',
        'total_notice_amount',
        'total_commitment_amount',
        'installments',
        'process_manager',
        'process_manager_email',
        'instrument_type',
        'creditor_registration_nup',
        'creditor_registration_request_date',
        'budget_allocation_nup',
        'budget_allocation_request_date',
        'created_by',
    ];

    protected $casts = [
        'total_notice_amount' => 'decimal:2',
        'total_commitment_amount' => 'decimal:2',
        'creditor_registration_request_date' => 'date',
        'installments' => 'integer',
    ];

    /**
     * Get the projects for the notice.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
