<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Installment;
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
            ->create()
            ->each(function ($budget) {
                $count = rand(1, 5);

                $budget->installments()->createMany(
                    Installment::factory()
                        ->count($count)
                        ->sequence(fn ($sequence) => [
                            'installment_number' => $sequence->index + 1,
                        ])
                        ->make()
                        ->toArray()
                );
            });
    }
}
