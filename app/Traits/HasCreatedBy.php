<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCreatedBy
{
    protected static function bootHasCreatedBy(): void
    {
        static::creating(function ($model) {
            if (auth()->check() && is_null($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
