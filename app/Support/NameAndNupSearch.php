<?php

namespace App\Support;

final readonly class NameAndNupSearch
{
    public array $words;

    public string $digits;

    public bool $isEmpty;

    public function __construct(?string $search)
    {
        $search = trim($search ?? '');
        preg_match_all('/\p{L}[\p{L}\p{M}]*/u', $search, $matches);

        $this->words = array_unique($matches[0]);
        $this->digits = preg_replace('/\D/', '', $search);
        $this->isEmpty = $search === '';
    }
}
