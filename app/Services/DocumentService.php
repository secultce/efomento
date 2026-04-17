<?php

namespace App\Services;
use App\Models\Document;
use App\Models\Project;
use App\Models\Opening;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DocumentService
{
    public function createDocumentCI(array $selectedProjects, string $content)
    {
        DB::transaction(function () use ($selectedProjects, $content) {
            $projects = Project::whereIn('id', $selectedProjects)->get();

            if(empty($content)) {
                throw new \Exception('O conteúdo da comunicação interna não pode ser vazio.');
            }

            if($projects->isEmpty()) {
                throw new \Exception('Nenhum projeto selecionado para criar a comunicação interna.');
            }

            foreach ($projects as $project) {
                if ($project->documents()->where('type', 'CI')->where('phase', 'opening')->exists()) {
                    throw new \Exception("O projeto da inscrição {$project->registration_id} já possui uma comunicação interna para a fase de abertura.");
                }

                $project->opening->createCI($content);
            }
        });
    }
}