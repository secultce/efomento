<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CypressSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CypressRoleSeeder::class,
        ]);
    }
}
