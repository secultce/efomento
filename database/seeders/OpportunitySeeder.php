<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Opportunity;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $json    = file_get_contents(database_path('seeders/data/opportunity_pnab.json'));
        $opportunities = json_decode($json, associative: true);

        foreach ($opportunities as $opportunity) {
            Opportunity::updateOrCreate(
                ['external_id' => $opportunity['id']],
                [
                    // ── Dados do JSON ─────────────────────────────────────
                    'external_id'                      => $opportunity['id'],
                    'name'                             => $opportunity['nome'],
                    'opportunity_url' => env('EXTERNAL_PROVIDER_URL') . $opportunity['id'],
                    'creditor_registration_request_date' => $this->parseDate($opportunity['inicio'] ?? null),

                    // ── Dados simulados (sem equivalente no JSON) ─────────
                    'total_opportunity_amount'      => fake()->randomFloat(2, 10000, 500000),
                    'total_commitment_amount'   => fake()->randomFloat(2, 5000, 300000),
                    'installments'              => fake()->numberBetween(1, 12),
                    'process_manager'           => fake('pt_BR')->name(),
                    'process_manager_email'     => fake()->safeEmail(),
                    'creditor_registration_nup' => fake()->numerify('CR-#####'),
                ]
            );
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
