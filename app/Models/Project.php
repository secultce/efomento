<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;
    protected $table = 'projects';
    
    protected $fillable = [
        'nup',
        'project_url',
        'external_id',
        'name',
        'total_project_amount',
        'total_commitment_amount',
        'installments',
        'process_manager',
        'process_manager_email',
        'creditor_registration_nup',
        'creditor_registration_request_date',
    ];

    protected $casts = [
        'total_project_amount' => 'decimal:2',
        'total_commitment_amount' => 'decimal:2',
        'creditor_registration_request_date' => 'date',
        'installments' => 'integer',
    ];
}
