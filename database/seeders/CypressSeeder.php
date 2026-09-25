<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CypressSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CypressUserSeeder::class,
            CypressRoleSeeder::class,
            CypressNoticeSeeder::class,
            CypressProjectSeeder::class,
        ]);
    }
}
