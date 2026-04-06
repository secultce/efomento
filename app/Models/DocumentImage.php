<?php

namespace App\Models;

use App\Enums\DocumentImagePosition;
use App\Enums\DocumentImageSection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'section',
        'position',
        'path',
    ];

    protected $casts = [
        'section'  => DocumentImageSection::class,
        'position' => DocumentImagePosition::class,
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
