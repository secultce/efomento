<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    public $timestamps = false;

    protected $fillable = ['ispb', 'code', 'name', 'short_name'];

    public function openings(): HasMany
    {
        return $this->hasMany(Opening::class);
    }
}
