<?php

namespace App\Models;

use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\AgentStatus;
use App\Enums\AccountType;
use App\Enums\OpeningStatus;
use App\Enums\DocumentPhase;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Support\Facades\Auth;
class Opening extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait, HasFiles;

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

    public function supervisors(): HasMany
    {
        return $this->hasMany(OpeningSupervisor::class);
    }

    public function activeSupervisor(): HasOne
    {
        return $this->hasOne(OpeningSupervisor::class)
            ->where('is_active', true)
            ->latestOfMany('assigned_at');
    }

    public function assignSupervisors(array $supervisorIds): void
    {
        OpeningSupervisor::where('opening_id', $this->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'removed_at' => now(),
            ]);

  
        foreach ($supervisorIds as $supervisorId) {
            OpeningSupervisor::create([
                'opening_id' => $this->id,
                'user_id' => $supervisorId,
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
                'is_active' => true,
            ]);
        }
    }

    public function createCI(string $content): void
    {
        $this->project->documents()->create([
            'project_id' => $this->project_id,
            'notice_id' => $this->project->notice_id,
            'type' => 'CI',
            'body' => $content,
            'phase' => DocumentPhase::OPENING,
            'created_by' => auth()->id(),
        ]);
    }
}
