<?php

namespace Database\Seeders;

use App\Models\Budget;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Budget::factory()
            ->count(10)
            ->hasInstallments(rand(1, 5)) // cria parcelas automaticamente
            ->create();
    }
}
