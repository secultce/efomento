<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CypressResetCommand extends Command
{
    protected $signature = 'cypress:reset';

    protected $description = 'Reset Cypress test data';

    public function handle(): int
    {
        $this->info('Resetting Cypress test data...');

        Cache::flush();

        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\CypressSeeder',
        ]);

        $this->info('Cypress test data reset successfully.');

        return self::SUCCESS;
    }
}
