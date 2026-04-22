<?php

namespace App\Services;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectDocumentService
{
    public function createDocumentCI(array $selectedProjects, string $content)
    {
        DB::transaction(function () use ($selectedProjects, $content) {
            $projects = Project::with('opening')->whereIn('id', $selectedProjects)->get();

            if(empty($content)) {
                throw new \Exception('O conteúdo da comunicação interna não pode ser vazio.');
            }

            if($projects->isEmpty()) {
                throw new \Exception('Nenhum projeto selecionado para criar a comunicação interna.');
            }

            foreach ($projects as $project) {
                if ($project->documents()->where('type', 'ci')->where('phase', 'opening')->exists()) {
                   $project->opening->updateCI($content);
                   continue;
                }
                $project->opening->createCI($content);
            }
        });
    }
}