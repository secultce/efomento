<?php

namespace Database\Factories;

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
            [Document::TYPE_TERM,             Document::PHASE_FORMALIZATION],
            [Document::TYPE_EXTRACT,          Document::PHASE_FORMALIZATION],
            [Document::TYPE_JURIDICAL_OPINION, Document::PHASE_JURIDICAL],
            [Document::TYPE_DISPATCH,          Document::PHASE_JURIDICAL],
        ];

        [$type, $phase] = $this->faker->randomElement($combinations);

        return [
            'notice_id'  => Notice::factory(),
            'project_id' => Project::factory(),
            'type'       => $type,
            'phase'      => $phase,
            'body'       => $this->faker->paragraphs(2, true),
            'status'     => Document::STATUS_DRAFT,
            'created_by' => User::factory(),
        ];
    }
}
