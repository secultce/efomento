<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDocumentImagePath implements ValidationRule
{
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const MESSAGE = 'Apenas imagens nos formatos .jpg, .jpeg, .png e .webp são permitidas.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::pattern(), $value)) {
            $fail(self::MESSAGE);
        }
    }

    private static function pattern(): string
    {
        $extensions = implode('|', array_map(
            fn (string $extension) => preg_quote($extension, '/'),
            self::ALLOWED_EXTENSIONS,
        ));

        return '/\Adocuments\/[A-Za-z0-9_-]+\.(?:'.$extensions.')\z/i';
    }
}
