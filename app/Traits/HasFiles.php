<?php

namespace App\Traits;

use App\Models\File;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFiles
{
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable', 'object_type', 'object_id');
    }

    public function filesByGroup(string $grp): MorphMany
    {
        return $this->morphMany(File::class, 'fileable', 'object_type', 'object_id')
            ->where('grp', $grp);
    }

    public function publicFiles(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable', 'object_type', 'object_id')
            ->where('private', false);
    }
}
