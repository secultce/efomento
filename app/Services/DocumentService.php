<?php

namespace App\Services;
use App\Models\Document;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DocumentService
{
    public function createCI(array $selectedProjects, string $content)
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

                $document = new Document();
                $document->project_id = $project->id;
                $document->notice_id = $project->notice_id;
                $document->type = 'CI';
                $document->body = $content;
                $document->phase = 'opening';
                $document->created_by = auth()->id();
                $document->save();
            }
        });
    }
}