<?php

namespace App\Console\Commands;

use App\Models\Notice;
use Illuminate\Console\Command;

class CypressResetCommand extends Command
{
    protected $signature = 'cypress:reset';

    protected $description = 'Reset Cypress test data';

    public function handle(): int
    {
        $this->info('Resetting Cypress test data...');

        Notice::where('external_id', 'cypress-notice-identification-form')->delete();

        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\CypressSeeder',
        ]);

        $this->info('Cypress test data reset successfully.');

        return self::SUCCESS;
    }
}
