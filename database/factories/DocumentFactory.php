<?php

namespace Database\Factories;

use App\Enums\DocumentPhase;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $combinations = [
            [DocumentType::TC, DocumentPhase::FORMALIZATION],
            [DocumentType::ET, DocumentPhase::FORMALIZATION],
            [DocumentType::PJ, DocumentPhase::JURIDICAL],
            [DocumentType::PO, DocumentPhase::JURIDICAL],
        ];

        [$type, $phase] = $this->faker->randomElement($combinations);

        return [
            'notice_id' => Notice::factory(),
            'project_id' => Project::factory(),
            'type' => $type,
            'phase' => $phase,
            'body' => $this->faker->paragraphs(2, true),
            'status' => DocumentStatus::DRAFT,
            'created_by' => User::factory(),
        ];
    }
}
