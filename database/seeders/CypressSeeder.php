<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class CypressSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'CypressSeeder can only be executed in local or testing environments.'
            );
        }

        $this->call([
            CypressUserSeeder::class,
            CypressRoleSeeder::class,
            CypressNoticeSeeder::class,
            CypressProjectSeeder::class,
        ]);
    }
}
