<?php

namespace Database\Seeders;

use App\Models\LegalAnalysis;
use App\Models\LegalAnalysisFile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LegalAnalysisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LegalAnalysis::factory()
            ->count(10)
            ->create()
            ->each(function ($analysis) {
                // para cada análise, cria arquivos relacionados
                LegalAnalysisFile::factory()
                    ->count(rand(1, 5))
                    ->create([
                        'legal_analysis_id' => $analysis->id,
                    ]);
            });
    }
}
