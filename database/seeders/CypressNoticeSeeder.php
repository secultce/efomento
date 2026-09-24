<?php

namespace Database\Seeders;

use App\Models\Notice;
use Illuminate\Database\Seeder;

class CypressNoticeSeeder extends Seeder
{
    public function run(): void
    {
        Notice::updateOrCreate(
            [
                'nup' => '27001123456789012',
            ],
            [
                'name' => 'EDITAL CYPRESS - EFOMENTO',
                'instrument_type' => 'CONVÊNIO',
                'total_notice_amount' => 13500000,
                'process_manager' => 'Cypress Notice Manager',
                'process_manager_email' => 'cypress.manager@example.com',
                'installments' => 3,
            ]
        );

        Notice::updateOrCreate(
            [
                'external_id' => 'cypress-notice-identification-form',
            ],
            [
                'name' => 'EDITAL CYPRESS - PREENCHIMENTO',
                'instrument_type' => null,
                'total_notice_amount' => null,
                'process_manager' => null,
                'process_manager_email' => null,
                'installments' => null,
            ]
        );
    }
}
