<?php

namespace App\Enums;

enum DocumentImagePosition: string
{
    case LEFT = 'left';
    case CENTER = 'center';
    case RIGHT = 'right';

    public static function fromIndex(int $index): ?self
    {
        return match ($index) {
            0 => self::LEFT,
            1 => self::CENTER,
            2 => self::RIGHT,
            default => null,
        };
    }
}
