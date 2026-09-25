<?php

namespace Database\Seeders;

use App\Models\Notice;
use Illuminate\Database\Seeder;

class CypressNoticeSeeder extends Seeder
{
    public function run(): void
    {
        $notice = Notice::withTrashed()
            ->where('external_id', 'cypress-notice-efomento')
            ->first();

        if ($notice) {
            if ($notice->trashed()) {
                $notice->restore();
            }

            $notice->update([
                'nup' => '27001123456789012',
                'name' => 'EDITAL CYPRESS - EFOMENTO',
                'instrument_type' => 'CONVÊNIO',
                'total_notice_amount' => 13500000,
                'process_manager' => 'Cypress Notice Manager',
                'process_manager_email' => 'cypress.manager@example.com',
                'installments' => 3,
            ]);
        } else {
            $notice = Notice::withTrashed()
                ->where('nup', '27001123456789012')
                ->first();

            if ($notice) {
                if ($notice->trashed()) {
                    $notice->restore();
                }

                $notice->update([
                    'external_id' => 'cypress-notice-efomento',
                    'name' => 'EDITAL CYPRESS - EFOMENTO',
                    'instrument_type' => 'CONVÊNIO',
                    'total_notice_amount' => 13500000,
                    'process_manager' => 'Cypress Notice Manager',
                    'process_manager_email' => 'cypress.manager@example.com',
                    'installments' => 3,
                ]);
            } else {
                Notice::create([
                    'external_id' => 'cypress-notice-efomento',
                    'nup' => '27001123456789012',
                    'name' => 'EDITAL CYPRESS - EFOMENTO',
                    'instrument_type' => 'CONVÊNIO',
                    'total_notice_amount' => 13500000,
                    'process_manager' => 'Cypress Notice Manager',
                    'process_manager_email' => 'cypress.manager@example.com',
                    'installments' => 3,
                ]);
            }
        }

        $notice = Notice::withTrashed()
            ->where('external_id', 'cypress-notice-identification-form')
            ->first();

        if ($notice) {
            if ($notice->trashed()) {
                $notice->restore();
            }

            $notice->update([
                'nup' => null,
                'name' => 'EDITAL CYPRESS - PREENCHIMENTO',
                'instrument_type' => null,
                'total_notice_amount' => null,
                'process_manager' => null,
                'process_manager_email' => null,
                'installments' => null,
            ]);
        } else {
            Notice::create([
                'external_id' => 'cypress-notice-identification-form',
                'nup' => null,
                'name' => 'EDITAL CYPRESS - PREENCHIMENTO',
                'instrument_type' => null,
                'total_notice_amount' => null,
                'process_manager' => null,
                'process_manager_email' => null,
                'installments' => null,
            ]);
        }
    }
}
