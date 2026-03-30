<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentImage extends Model
{
    use HasFactory;

    // Sections
    const SECTION_HEADER = 'header';
    const SECTION_FOOTER = 'footer';

    // Positions
    const POSITION_LEFT = 'left';
    const POSITION_CENTER = 'center';
    const POSITION_RIGHT = 'right';

    protected $fillable = [
        'document_id',
        'section',
        'position',
        'path',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
