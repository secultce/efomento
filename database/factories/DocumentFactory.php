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
            [DocumentType::TERM,             DocumentPhase::FORMALIZATION],
            [DocumentType::EXTRACT,          DocumentPhase::FORMALIZATION],
            [DocumentType::JURIDICAL_OPINION, DocumentPhase::JURIDICAL],
            [DocumentType::DISPATCH,          DocumentPhase::JURIDICAL],
        ];

        [$type, $phase] = $this->faker->randomElement($combinations);

        return [
            'notice_id'  => Notice::factory(),
            'project_id' => Project::factory(),
            'type'       => $type,
            'phase'      => $phase,
            'body'       => $this->faker->paragraphs(2, true),
            'status'     => DocumentStatus::DRAFT,
            'created_by' => User::factory(),
        ];
    }
}
