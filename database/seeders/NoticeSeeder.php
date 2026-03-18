<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notice;

class NoticeSeeder extends Seeder
{
    public function run(): void
    {
        $json    = file_get_contents(database_path('seeders/data/notice_pnab.json'));
        $notices = json_decode($json, associative: true);

        foreach ($notices as $notice) {
            Notice::updateOrCreate(
                ['external_id' => $notice['id']],
                [
                    // ── Dados do JSON ─────────────────────────────────────
                    'external_id'                      => $notice['id'],
                    'name'                             => $notice['nome'],
                    'notice_url' => env('EXTERNAL_PROVIDER_URL') . $notice['id'],
                    'creditor_registration_request_date' => $this->parseDate($notice['inicio'] ?? null),

                    // ── Dados simulados (sem equivalente no JSON) ─────────
                    'total_notice_amount'      => fake()->randomFloat(2, 10000, 500000),
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
