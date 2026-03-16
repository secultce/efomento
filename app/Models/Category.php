<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'nome',
        'type',
    ];

    /**
     * Get the projects for the category.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
