<?php

namespace Database\Seeders;

use App\Models\Formalization;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\Project;
use Illuminate\Database\Seeder;

class NoticeSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(database_path('seeders/data/notice_pnab.json'));
        $notices = json_decode($json, associative: true);

        foreach ($notices as $noticeData) {
            // 60% chance for the notice to have a nup
            $hasNup = fake()->boolean(60);
            // Create or update Notice
            $notice = Notice::updateOrCreate(
                ['external_id' => $noticeData['id']],
                [
                    'external_id' => $noticeData['id'],
                    'name' => $noticeData['nome'],
                    'notice_url' => env('EXTERNAL_PROVIDER_URL').$noticeData['id'],
                    'creditor_registration_request_date' => $this->parseDate($noticeData['inicio'] ?? null),
                    'nup' => $hasNup ? fake()->numerify('#####.######/####-##') : null,
                    'total_notice_amount' => fake()->randomFloat(2, 10000, 500000),
                    'total_commitment_amount' => fake()->randomFloat(2, 5000, 300000),
                    'installments' => fake()->numberBetween(1, 12),
                    'process_manager' => fake('pt_BR')->name(),
                    'process_manager_email' => fake()->safeEmail(),
                    'creditor_registration_nup' => fake()->numerify('CR-#####'),
                ]
            );

            // skip seed if notice doesnt have a nup
            if (! $hasNup) {
                continue;
            }

            // only create if notice has a nup
            $projects = Project::factory()
                ->count(rand(2, 5))
                ->create([
                    'notice_id' => $notice->id,
                ]);

            foreach ($projects as $project) {

                if (fake()->boolean(70)) {
                    Opening::factory()
                        ->count(1)
                        ->create([
                            'project_id' => $project->id,
                        ]);
                }

                if (fake()->boolean(80)) {
                    Formalization::factory()
                        ->count(1)
                        ->create([
                            'project_id' => $project->id,
                        ]);
                }
            }
        }
    }

    private function parseDate(?string $isoDate): ?string
    {
        if (! $isoDate) {
            return null;
        }

        return substr($isoDate, 0, 10); // "2024-05-16T03:00:00.000Z" → "2024-05-16"
    }
}
