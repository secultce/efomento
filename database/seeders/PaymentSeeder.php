<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Project;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            Payment::factory()->create([
                'project_id' => $project->id,
            ]);
        }
    }
}
