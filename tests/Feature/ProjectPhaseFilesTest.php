<?php

namespace Tests\Feature;

use App\Jobs\DownloadMapasProjectFileJob;
use App\Jobs\SyncPhaseFilesJob;
use App\Models\File;
use App\Models\Project;
use App\Models\ProjectRegistrationPhase;
use App\Services\PhaseFileExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectPhaseFilesTest extends TestCase
{
    use RefreshDatabase;

    private function fileField(int $id, array|string $files, string $type = 'file'): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'title' => "Campo {$id}",
            'description' => "Descrição {$id}",
            'value' => $files,
        ];
    }

    private function mapasFile(int $id, string $name = 'doc.pdf'): array
    {
        return ['id' => $id, 'url' => "http://mapas.test/arquivos/privateFile/{$id}/", 'name' => $name];
    }

    private function phaseWith(Project $project, array $fields, int $registrationId = 100): ProjectRegistrationPhase
    {
        return ProjectRegistrationPhase::create([
            'project_id' => $project->id,
            'registration_id' => $registrationId,
            'phase_order' => 1,
            'is_first' => true,
            'status' => 10,
            'data' => ['sections' => [['title' => null, 'fields' => $fields]]],
            'content_hash' => str_repeat('a', 64),
            'fetched_at' => now(),
        ]);
    }

    public function test_extractor_returns_normalized_files_of_file_fields_only(): void
    {
        $data = ['sections' => [
            ['title' => 'A', 'fields' => [
                $this->fileField(10, [$this->mapasFile(1, 'a.pdf'), $this->mapasFile(2, 'b.pdf')]),
                $this->fileField(11, [], 'file'),
                $this->fileField(12, 'texto solto', 'file'),
                $this->fileField(13, [$this->mapasFile(3)], 'links'),
                ['id' => 14, 'type' => 'text', 'title' => 'Nome', 'value' => 'Fulano'],
            ]],
            ['title' => 'B', 'fields' => [
                $this->fileField(20, [$this->mapasFile(4), ['id' => 5, 'url' => null, 'name' => 'sem-url.pdf']]),
                $this->fileField(21, [$this->mapasFile(1, 'a.pdf')]),
            ]],
        ]];

        $files = app(PhaseFileExtractor::class)->extract($data);

        $this->assertSame([1, 2, 4], $files->pluck('external_id')->all());
        $this->assertSame([
            'external_id' => 1,
            'url' => 'http://mapas.test/arquivos/privateFile/1/',
            'name' => 'a.pdf',
            'title' => 'Campo 10',
            'grp' => 'rfc_10',
            'description' => 'Descrição 10',
            'private' => true,
        ], $files->first());
    }

    public function test_extractor_handles_phase_without_sections(): void
    {
        $this->assertTrue(app(PhaseFileExtractor::class)->extract([])->isEmpty());
    }

    public function test_job_batches_downloads_owned_by_the_phase(): void
    {
        Bus::fake();
        $project = Project::factory()->create();
        $phase = $this->phaseWith($project, [$this->fileField(10, [$this->mapasFile(1), $this->mapasFile(2)])], 777);

        (new SyncPhaseFilesJob($phase->id))->handle(app(PhaseFileExtractor::class));

        Bus::assertBatched(function ($batch) use ($project, $phase) {
            return $batch->jobs->count() === 2
                && $batch->jobs->every(fn (DownloadMapasProjectFileJob $job) => $job->projectId === $project->id
                    && $job->registrationId === 777
                    && $job->objectType === 'project_registration_phase'
                    && $job->objectId === $phase->id);
        });
    }

    public function test_job_skips_files_already_registered_for_any_owner(): void
    {
        Bus::fake();
        $project = Project::factory()->create();
        $phase = $this->phaseWith($project, [$this->fileField(10, [
            $this->mapasFile(1), $this->mapasFile(2), $this->mapasFile(3),
        ])]);

        // 1: baixado pelo fluxo de detalhes (dono = projeto); 2: apagado (soft delete); 3: pendente.
        File::factory()->create(['object_type' => 'project', 'object_id' => $project->id, 'source' => 'mapas', 'external_id' => '1']);
        File::factory()->create(['object_type' => 'project', 'object_id' => $project->id, 'source' => 'mapas', 'external_id' => '2'])->delete();

        (new SyncPhaseFilesJob($phase->id))->handle(app(PhaseFileExtractor::class));

        Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1
            && $batch->jobs->first()->file['external_id'] === 3);
    }

    public function test_job_is_idempotent_once_files_are_registered(): void
    {
        Bus::fake();
        $project = Project::factory()->create();
        $phase = $this->phaseWith($project, [$this->fileField(10, [$this->mapasFile(1)])]);
        File::factory()->create(['object_type' => 'project_registration_phase', 'object_id' => $phase->id, 'source' => 'mapas', 'external_id' => '1']);

        (new SyncPhaseFilesJob($phase->id))->handle(app(PhaseFileExtractor::class));

        Bus::assertNothingBatched();
    }

    public function test_job_ignores_missing_phase_and_phase_without_files(): void
    {
        Bus::fake();
        $project = Project::factory()->create();
        $phase = $this->phaseWith($project, [['id' => 1, 'type' => 'text', 'title' => 'x', 'value' => 'y']]);

        (new SyncPhaseFilesJob($phase->id))->handle(app(PhaseFileExtractor::class));
        (new SyncPhaseFilesJob(999999))->handle(app(PhaseFileExtractor::class));

        Bus::assertNothingBatched();
    }

    public function test_download_stores_file_on_disk_owned_by_the_phase(): void
    {
        Storage::fake('public');
        config(['efomento.file_disk' => 'public']);
        Http::fake(['mapas.test/*' => Http::response('%PDF-1.4 conteudo', 200, ['Content-Type' => 'application/pdf'])]);

        $project = Project::factory()->create();
        $phase = $this->phaseWith($project, [$this->fileField(10, [$this->mapasFile(55, 'anexo.pdf')])], 777);

        (new SyncPhaseFilesJob($phase->id))->handle(app(PhaseFileExtractor::class));

        $file = $phase->files()->sole();
        $this->assertSame('55', $file->external_id);
        $this->assertSame('mapas', $file->source);
        $this->assertSame('rfc_10', $file->grp);
        $this->assertSame('Campo 10', $file->title);
        $this->assertSame('application/pdf', $file->mime_type);
        $this->assertTrue($file->private);
        $this->assertSame("projects/{$project->id}/mapas/55.pdf", $file->path);
        Storage::disk('public')->assertExists($file->path);

        // Segunda passada: o arquivo já está registrado e nada é baixado de novo.
        Http::fake();
        (new SyncPhaseFilesJob($phase->id))->handle(app(PhaseFileExtractor::class));
        Http::assertNothingSent();
        $this->assertSame(1, File::where('external_id', '55')->count());
    }

    public function test_phase_exposes_its_files_through_the_morph_map(): void
    {
        $project = Project::factory()->create();
        $phase = $this->phaseWith($project, []);
        $file = File::factory()->create([
            'object_type' => 'project_registration_phase',
            'object_id' => $phase->id,
            'source' => 'mapas',
            'external_id' => '1',
        ]);

        $this->assertTrue($phase->files()->get()->contains($file));
        $this->assertSame($phase->id, $file->fileable->id);
    }

    public function test_command_dispatches_one_job_per_phase_and_filters_by_project(): void
    {
        Bus::fake();
        $a = Project::factory()->create();
        $b = Project::factory()->create();
        $phaseA = $this->phaseWith($a, [], 100);
        $this->phaseWith($b, [], 200);

        $this->artisan('app:sync-phase-files', ['--project' => $a->id])->assertSuccessful();
        Bus::assertDispatchedTimes(SyncPhaseFilesJob::class, 1);
        Bus::assertDispatched(SyncPhaseFilesJob::class, fn ($job) => $job->phaseId === $phaseA->id);

        $this->artisan('app:sync-phase-files')->assertSuccessful();
        Bus::assertDispatchedTimes(SyncPhaseFilesJob::class, 3);
    }
}
