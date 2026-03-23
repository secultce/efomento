<?php

namespace App\Models;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Category extends Model implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $fillable = [
        'name',
        'type',
    ];

    protected $casts = [
        'type' => CategoryType::class,
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }
}
