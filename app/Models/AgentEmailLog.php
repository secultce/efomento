<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class AgentEmailLog extends Model implements Auditable
{
    use AuditableTrait;

    protected $auditInclude = [
        'status',
        'error_message',
        'sent_at',
    ];

    protected $fillable = [
        'agent_id',
        'project_id',
        'related_type',
        'related_id',
        'recipient_email',
        'recipient_name',
        'mail_class',
        'event_type',
        'subject',
        'status',
        'error_message',
        'sent_at',
        'deduplication_key',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public static function isAuditingEnabled(): bool
    {
        return (bool) config('audit.enabled', true);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function related(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
