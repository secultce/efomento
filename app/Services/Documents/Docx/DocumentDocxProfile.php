<?php

namespace App\Services\Documents\Docx;

enum DocumentDocxProfile: string
{
    case Standard = 'standard';
    case CasaCivil = 'casa_civil';

    /** A4 width minus the PDF template's 50px left and right margins. */
    private const STANDARD_CONTENT_WIDTH_TWIPS = 10406;

    private const CASA_CIVIL_CONTENT_WIDTH_TWIPS = 9921;

    public static function resolve(string $profile): self
    {
        return self::tryFrom($profile) ?? self::Standard;
    }

    public function isCasaCivil(): bool
    {
        return $this === self::CasaCivil;
    }

    public function contentWidthTwips(): int
    {
        return $this->isCasaCivil()
            ? self::CASA_CIVIL_CONTENT_WIDTH_TWIPS
            : self::STANDARD_CONTENT_WIDTH_TWIPS;
    }
}
