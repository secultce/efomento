<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\AgentStatus;
use App\Enums\AccountType;
use App\Enums\OpeningStatus;

class Opening extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'opening_nup',
        'opening_date',
        'agent_status',
        'opened_by',
        'bank',
        'account_type',
        'branch',
        'account',
        'is_draft',
        'user_id',
        'status',
        'certificate_date',
        'supervisor_id',
        'started_at',
        'submitted_at',
        'concluded_at',
    ];

    protected $casts = [
        'agent_status' => AgentStatus::class,
        'account_type' => AccountType::class,
        'status' => OpeningStatus::class,
        'opening_date' => 'date',
        'is_draft' => 'boolean',
        'certificate_date' => 'datetime',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'concluded_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}