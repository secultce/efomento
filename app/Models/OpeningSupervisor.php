<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningSupervisor extends Model
{
    protected $fillable = [
        'opening_id',
        'user_id',
        'assigned_by',
        'is_active',
        'assigned_at',
        'removed_at',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'assigned_at' => 'datetime',
        'removed_at'  => 'datetime',
    ];

    public function opening(): BelongsTo
    {
        return $this->belongsTo(Opening::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
