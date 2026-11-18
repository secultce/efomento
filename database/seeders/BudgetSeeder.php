<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
                $totalInstallments = rand(1, 10);

                Installment::factory()
                    ->count($totalInstallments)
                    ->state(new Sequence(
                        fn ($sequence) => [
                            'budget_id' => $budget->id,
                            'installment_number' => $sequence->index + 1,
                        ]
                    ))
                    ->create();
            });
    }
}
