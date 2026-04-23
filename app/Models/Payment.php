<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'creditor_requested_at',
        'creditor_registration_number',
        'communication_sent_at',
        'contact_notes',
        'created_by',
    ];

    protected $casts = [
        'creditor_requested_at' => 'date',
        'communication_sent_at' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
