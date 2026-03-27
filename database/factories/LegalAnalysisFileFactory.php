<?php

namespace Database\Factories;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\LegalAnalysis;
use App\Models\LegalAnalysisFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalAnalysisFile>
 */
class LegalAnalysisFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legal_analysis_id' => LegalAnalysis::inRandomOrder()->first()?->id ?? LegalAnalysis::factory(),
            'file_id' => File::inRandomOrder()->first()?->id ?? File::factory(),
            'status_id' => $this->faker->randomElement(FileStatus::values()),
        ];
    }
}
