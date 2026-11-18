<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\LegalAnalysis;
use App\Models\LegalAnalysisFile;
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
                $files = File::factory()
                    ->count(rand(1, 5))
                    ->create([
                        'object_id' => $analysis->project_id,
                    ]);

                foreach ($files as $file) {
                    LegalAnalysisFile::factory()->create([
                        'legal_analysis_id' => $analysis->id,
                        'file_id' => $file->id,
                    ]);
                }
            });
    }
}
