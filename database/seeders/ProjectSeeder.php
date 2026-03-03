<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(database_path('seeders/data/editais_project_pnab.json'));
        $editais = json_decode($json, associative: true);

        foreach ($editais as $edital) {
            Project::updateOrCreate(
                ['nup' => 'NUP-' . $edital['id']],
                [
                    'external_id' => $edital['id'],
                    'name'        => $edital['nome'],
                    'project_url' => (string) $edital['id_projeto'],
                ]
            );
        }
    }
}
