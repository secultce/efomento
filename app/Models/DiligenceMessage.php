<?php

namespace App\Models;

use App\Enums\DiligenceDirection;
use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class DiligenceMessage extends Model implements Auditable
{
    use AuditableTrait, HasCreatedBy, HasFactory, SoftDeletes;

    protected $fillable = [
        'diligenceable_type',
        'diligenceable_id',
        'direction',
        'from_email',
        'to_email',
        'subject',
        'body',
        'imap_message_id',
        'in_reply_to',
        'sent_at',
        'read_at',
        'created_by',
    ];

    protected $casts = [
        'direction' => DiligenceDirection::class,
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function diligenceable(): MorphTo
    {
        return $this->morphTo();
    }
}
