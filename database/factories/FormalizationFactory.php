<?php

namespace Database\Factories;

use App\Enums\DeliberationType;
use App\Enums\ReportStatus;
use App\Models\Formalization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormalizationFactory extends Factory
{
    protected $model = Formalization::class;

    public function definition()
    {
        return [
            'project_id' => Project::factory(),
            'asjur_finalistic_processing_date' => $this->faker->date(),
            'asjur_received_at' => $this->faker->date(),
            'process_assigned_to' => $this->faker->name(),
            'report_status' => $this->faker->randomElement(ReportStatus::cases())->value,
            'eparcerias_certificate_date' => $this->faker->date(),
            'asjur_processing_date' => $this->faker->date(),
            'responsible_at_asjur' => $this->faker->name(),
            'term_number' => $this->faker->unique()->numerify('TERM-###'),
            'term_signature_sent_at' => $this->faker->date(),
            'term_signed_at' => $this->faker->date(),
            'sent_to_office_at' => $this->faker->date(),
            'signed_by_office_at' => $this->faker->date(),
            'sacc_number' => $this->faker->bothify('SACC-#####'),
            'cge_atende_ticket' => $this->faker->bothify('TICKET-#####'),
            'deliberation' => $this->faker->randomElement(DeliberationType::cases())->value,
            'sent_to_chief_of_staff_at' => $this->faker->date(),
            'official_gazette_published_at' => $this->faker->date(),
            'validity_start_at' => $this->faker->date(),
            'validity_end_at' => $this->faker->date(),
            'legal_opinion_date' => $this->faker->date(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Formalization $formalization) {
            $formalization->files()->createMany([
                [
                    'name' => 'Commitment Term',
                    'grp' => 'commitment_term',
                    'path' => 'files/commitment1.pdf',
                    'mime_type' => 'application/pdf',
                    'private' => false,
                ],
                [
                    'name' => 'Term Summary',
                    'grp' => 'term_summary',
                    'path' => 'files/summary1.pdf',
                    'mime_type' => 'application/pdf',
                    'private' => false,
                ],
                [
                    'name' => 'Official Gazette',
                    'grp' => 'official_gazette',
                    'path' => 'files/gazette1.pdf',
                    'mime_type' => 'application/pdf',
                    'private' => true,
                ],
                [
                    'name' => 'Legal Opinion',
                    'grp' => 'legal_opinion',
                    'path' => 'files/legal1.pdf',
                    'mime_type' => 'application/pdf',
                    'private' => true,
                ],
            ]);
        });
    }
}
