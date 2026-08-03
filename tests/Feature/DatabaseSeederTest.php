<?php

namespace Tests\Feature;

use Database\Seeders\AgentSeeder;
use Database\Seeders\BudgetSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LegalAnalysisSeeder;
use Database\Seeders\MonitoringSeeder;
use Database\Seeders\NoticeSeeder;
use Database\Seeders\PaymentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use InvalidArgumentException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    public function test_none_mode_does_not_run_any_seeders(): void
    {
        config(['seeding.mode' => 'none']);

        $seeder = $this->recordingSeeder();
        $seeder->run();

        $this->assertSame([], $seeder->calls);
    }

    public function test_users_mode_runs_only_permissions_and_users(): void
    {
        config(['seeding.mode' => 'users']);

        $seeder = $this->recordingSeeder();
        $seeder->run();

        $this->assertSame([
            PermissionSeeder::class,
            UserSeeder::class,
        ], $seeder->calls);
    }

    public function test_all_mode_runs_every_seeder(): void
    {
        config(['seeding.mode' => 'all']);

        $seeder = $this->recordingSeeder();
        $seeder->run();

        $this->assertSame([
            PermissionSeeder::class,
            NoticeSeeder::class,
            UserSeeder::class,
            AgentSeeder::class,
            LegalAnalysisSeeder::class,
            BudgetSeeder::class,
            PaymentSeeder::class,
            MonitoringSeeder::class,
        ], $seeder->calls);
    }

    public function test_invalid_mode_is_rejected(): void
    {
        config(['seeding.mode' => 'invalid']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SEED_MODE. Supported values: none, users, all.');

        $this->recordingSeeder()->run();
    }

    private function recordingSeeder(): DatabaseSeeder
    {
        return new class extends DatabaseSeeder
        {
            /** @var array<int, class-string> */
            public array $calls = [];

            public function call($class, $silent = false, array $parameters = [])
            {
                $this->calls = (array) $class;

                return $this;
            }
        };
    }
}
