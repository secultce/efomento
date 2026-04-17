<?php

namespace App\Services;
use App\Models\Document;
use App\Models\Project;
use App\Models\Opening;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
                    throw new \Exception("O projeto da inscrição {$project->agent->name} já possui uma comunicação interna para a fase de abertura.");
                }

                $project->opening->createCI($content);
            }
        });
    }
}