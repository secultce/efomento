<?php

namespace App\Services;

use App\Models\Bank;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankResolver
{
    private bool $loaded = false;

    /** @var array<string, int> */
    private array $codes = [];

    /** @var array<string, array<int, int>> */
    private array $names = [];

    public function resolveBankId(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $this->loadCatalog();

        if (ctype_digit($value)) {
            $id = $this->codes[str_pad($value, 3, '0', STR_PAD_LEFT)] ?? null;
        } else {
            $matches = array_values($this->names[$this->normalize($value)] ?? []);
            if (count($matches) > 1) {
                Log::warning('spreadsheet.import.bank_ambiguous', [
                    'bank' => $value,
                    'candidate_ids' => array_slice($matches, 0, 2),
                ]);

                return null;
            }

            $id = $matches[0] ?? null;
        }

        if ($id === null) {
            Log::warning('spreadsheet.import.bank_not_found', ['bank' => $value]);
        }

        return $id;
    }

    private function loadCatalog(): void
    {
        if ($this->loaded) {
            return;
        }

        foreach (Bank::query()->get(['id', 'code', 'name', 'short_name']) as $bank) {
            if ($bank->code !== null) {
                $this->codes[$bank->code] = $bank->id;
            }

            foreach ([$bank->name, $bank->short_name] as $name) {
                // Deduplicate aliases of the same institution, not distinct banks.
                $this->names[$this->normalize($name)][$bank->id] = $bank->id;
            }
        }

        $this->loaded = true;
    }

    private function normalize(string $name): string
    {
        return (string) Str::of($name)
            ->ascii()->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->replaceMatches('/\bbco\b/', 'banco')
            ->replaceMatches('/\s+s\s*a\s*$/', '')
            ->trim();
    }
}
