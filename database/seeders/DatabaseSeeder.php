<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $mode = config('seeding.mode');

        $seeders = match ($mode) {
            'none' => [],
            'users' => [
                PermissionSeeder::class,
                UserSeeder::class,
            ],
            'all' => [
                PermissionSeeder::class,
                NoticeSeeder::class,
                UserSeeder::class,
                AgentSeeder::class,
                LegalAnalysisSeeder::class,
                BudgetSeeder::class,
                PaymentSeeder::class,
                MonitoringSeeder::class,
            ],
            default => throw new InvalidArgumentException(
                'Invalid SEED_MODE. Supported values: none, users, all.'
            ),
        };

        if ($mode !== 'none') {
            $seeders = array_values(array_unique(array_merge(config('seeding.common', []), $seeders)));
        }

        if ($seeders !== []) {
            $this->call($seeders);
        }
    }
}
