<?php

namespace App\Services;

use App\Enums\InstrumentType;
use App\Models\Formalization;
use App\Models\InstrumentSequence;
use Illuminate\Support\Facades\DB;

class InstrumentSequenceService
{
    public function generateNextTermNumber(InstrumentType $instrumentType, ?int $year = null): string
    {
        $year = $year ?? (int) now()->format('Y');

        return DB::transaction(function () use ($instrumentType, $year) {
            $sequence = InstrumentSequence::where('instrument_type', $instrumentType->value)
                ->where('year', $year)
                ->first();

            if (! $sequence) {
                $maxExisting = Formalization::whereHas('project.notice', function ($query) use ($instrumentType) {
                    $query->where('instrument_type', $instrumentType->value);
                })
                    ->where('term_number', 'like', "%/{$year}")
                    ->get()
                    ->map(fn ($f) => (int) explode('/', $f->term_number)[0])
                    ->max() ?? 0;

                InstrumentSequence::insertOrIgnore([
                    'instrument_type' => $instrumentType->value,
                    'year' => $year,
                    'current_number' => $maxExisting,
                    'initial_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $sequence = InstrumentSequence::where('instrument_type', $instrumentType->value)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('current_number');
            $sequence->refresh();

            return "{$sequence->current_number}/{$year}";
        });
    }
}
