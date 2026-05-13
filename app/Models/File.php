<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mime_type',
        'name',
        'object_type',
        'object_id',
        'grp',
        'description',
        'path',
        'private',
    ];

    protected $casts = [
        'private' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function fileable(): MorphTo
    {
        return $this->morphTo('fileable', 'object_type', 'object_id');
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->private || ! $this->path) {
            return null;
        }

        return Storage::disk(config('efomento.file_disk', 'public'))->url($this->path);
    }

    public function scopePublic($query): mixed
    {
        return $query->where('private', false);
    }

    public function scopeByGroup($query, string $grp): mixed
    {
        return $query->where('grp', $grp);
    }
}
