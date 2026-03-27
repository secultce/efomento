<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Formalization;

class FormalizationFactory extends Factory
{
    protected $model = Formalization::class;

    public function definition()
    {
        return [
            'asjur_finalistic_processing_date' => $this->faker->date(),
            'asjur_received_at' => $this->faker->date(),
            'process_supervisor_id' => $this->faker->uuid(),
            'report_status' => $this->faker->randomElement([
                'SEM CADASTRO', 
                'REGULAR E ADIMPLEMTE', 
                'REGULAR E INADIMPLENTE', 
                'IRREGULAR E ADIMPLENTE', 
                'IRREGULAR E INADIMPLENTE'
            ]),
            'eparcerias_certificate_date' => $this->faker->date(),
            'term_status' => $this->faker->randomElement(['signed','unsigned']),
            'term_number' => $this->faker->unique()->numerify('TERM-###'),
            'term_signature_sent_at' => $this->faker->date(),
            'sent_to_office_at' => $this->faker->date(),
            'term_signed_at' => $this->faker->date(),
            'asjur_processing_date' => $this->faker->date(),
            'office_signature_status' => $this->faker->randomElement(['signed','unsigned']),
            'sacc_number' => $this->faker->bothify('SACC-#####'),
            'cge_atende_ticket' => $this->faker->bothify('TICKET-#####'),
            'sacc_registered_at' => $this->faker->date(),
            'deliberation' => $this->faker->randomElement(['manual','batch_cge','fec']),
            'validity_start_at' => $this->faker->date(),
            'validity_end_at' => $this->faker->date(),
            'sent_to_chief_of_staff_at' => $this->faker->date(),
            'official_gazette_published_at' => $this->faker->date(),
            'legal_opinion_date' => $this->faker->date(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Formalization $formalization) {
            $formalization->files()->createMany([
                ['name'=>'Commitment Term','grp'=>'commitment_term','path'=>'files/commitment1.pdf','private'=>false],
                ['name'=>'Term Summary','grp'=>'term_summary','path'=>'files/summary1.pdf','private'=>false],
                ['name'=>'Official Gazette','grp'=>'official_gazette','path'=>'files/gazette1.pdf','private'=>true],
                ['name'=>'Legal Opinion','grp'=>'legal_opinion','path'=>'files/legal1.pdf','private'=>true],
            ]);
        });
    }
}