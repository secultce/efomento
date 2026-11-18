<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\Notice;
use App\Models\Project;
use Illuminate\Database\Seeder;

class FileSeeder extends Seeder
{
    private const GROUPS = [
        'contrato',
        'plano_trabalho',
        'relatorio',
        'nota_fiscal',
        'documentacao',
    ];

    private const MIME_TYPES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
    ];

    private const EXTENSIONS = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public function run(): void
    {
        $notice = Notice::find(53);

        if (! $notice) {
            $this->command->warn('Notice com id=53 não encontrado. Usando o primeiro notice disponível.');
            $notice = Notice::first();
        }

        if (! $notice) {
            $this->command->error('Nenhum notice encontrado. Execute NoticeSeeder primeiro.');

            return;
        }

        $projects = Project::where('notice_id', $notice->id)->get();

        if ($projects->isEmpty()) {
            $this->command->warn("Notice #{$notice->id} não possui projetos. Criando 3 projetos de exemplo.");
            $projects = Project::factory()->count(3)->create(['notice_id' => $notice->id]);
        }

        $this->command->info("Criando arquivos para {$projects->count()} projetos do notice #{$notice->id} ({$notice->name})...");

        foreach ($projects as $project) {
            $fileCount = rand(2, 5);

            File::factory()
                ->count($fileCount)
                ->sequence(fn ($seq) => $this->fileAttributes($project->id))
                ->create();

            $this->command->line("  Projeto #{$project->id}: {$fileCount} arquivo(s) criado(s).");
        }

        $this->command->info('FileSeeder concluído.');
    }

    private function fileAttributes(int $projectId): array
    {
        $mime = fake()->randomElement(self::MIME_TYPES);
        $ext = self::EXTENSIONS[$mime];
        $grp = fake()->randomElement(self::GROUPS);

        return [
            'object_type' => Project::class,
            'object_id' => $projectId,
            'mime_type' => $mime,
            'name' => "{$grp}_projeto_{$projectId}.{$ext}",
            'grp' => $grp,
            'description' => fake('pt_BR')->optional(0.6)->sentence(),
            'path' => "projects/{$projectId}/{$grp}.{$ext}",
            'private' => fake()->boolean(20),
        ];
    }
}
