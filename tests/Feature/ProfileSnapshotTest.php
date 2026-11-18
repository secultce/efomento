<?php

namespace Tests\Feature;

use App\Enums\ProfileSnapshotSource;
use App\Models\Agent;
use App\Models\ProfileSnapshot;
use App\Models\Project;
use App\Services\ProfileSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private ProfileSnapshotService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProfileSnapshotService::class);
    }

    public function test_cria_snapshot_para_agent(): void
    {
        $agent = Agent::factory()->create();

        $this->service->record($agent, [
            'gender' => 'Homem Cis',
        ], ProfileSnapshotSource::AGENT_UPDATE);

        $this->assertDatabaseHas('profile_snapshots', [
            'object_id' => $agent->id,
            'object_type' => 'agent',
            'gender' => 'Homem Cis',
            'source' => ProfileSnapshotSource::AGENT_UPDATE->value,
        ]);
    }

    public function test_agent_update_atualiza_snapshot_existente_ao_mudar_dados(): void
    {
        $agent = Agent::factory()->create();

        $snapshot = $this->service->recordIfChanged($agent, [
            'gender' => 'Não-binário',
        ], ProfileSnapshotSource::AGENT_UPDATE);

        $this->service->recordIfChanged($agent, [
            'gender' => 'Homem Cis',
        ], ProfileSnapshotSource::AGENT_UPDATE);

        $this->assertEquals(2, $agent->profileSnapshots()->count());

        $this->assertDatabaseHas('profile_snapshots', [
            'id' => $snapshot->id,
            'gender' => $snapshot->gender,
        ]);

        $this->assertDatabaseHas('profile_snapshots', [
            'object_id' => $agent->id,
            'gender' => 'Homem Cis',
        ]);
    }

    public function test_agent_update_nao_altera_snapshot_se_dados_iguais(): void
    {
        $agent = Agent::factory()->create();

        $this->service->recordIfChanged($agent, [
            'gender' => 'Homem Cis',
        ], ProfileSnapshotSource::AGENT_UPDATE);

        $resultado = $this->service->recordIfChanged($agent, [
            'gender' => 'Homem Cis',
        ], ProfileSnapshotSource::AGENT_UPDATE);

        $this->assertNull($resultado);
        $this->assertEquals(1, $agent->profileSnapshots()->count());
    }

    public function test_project_registration_sempre_cria_novo_snapshot(): void
    {
        $project = Project::factory()->create();

        $this->service->recordIfChanged($project, [
            'gender' => 'Mulher Cis',
        ], ProfileSnapshotSource::PROJECT_REGISTRATION);

        $this->service->recordIfChanged($project, [
            'gender' => 'Mulher Cis',
        ], ProfileSnapshotSource::PROJECT_REGISTRATION);

        $this->assertEquals(2, $project->profileSnapshots()->count());
    }

    public function test_latest_snapshot_retorna_o_mais_recente(): void
    {
        $agent = Agent::factory()->create();

        ProfileSnapshot::factory()->create([
            'object_id' => $agent->id,
            'object_type' => 'agent',
            'gender' => 'Não-binário',
            'recorded_at' => now()->subDay(),
        ]);

        ProfileSnapshot::factory()->create([
            'object_id' => $agent->id,
            'object_type' => 'agent',
            'gender' => 'Homem Cis',
            'recorded_at' => now(),
        ]);

        $latest = $agent->latestSnapshot;

        $this->assertEquals('Homem Cis', $latest->gender);
    }

    public function test_cria_snapshot_para_project(): void
    {
        $project = Project::factory()->create();

        $this->service->record($project, [
            'gender' => 'Mulher Cis',
        ], ProfileSnapshotSource::PROJECT_REGISTRATION);

        $this->assertDatabaseHas('profile_snapshots', [
            'object_id' => $project->id,
            'object_type' => 'project',
            'gender' => 'Mulher Cis',
            'source' => ProfileSnapshotSource::PROJECT_REGISTRATION->value,
        ]);
    }

    public function test_agent_acessa_multiplos_snapshots_via_relacionamento(): void
    {
        $agent = Agent::factory()->create();

        ProfileSnapshot::factory()->count(3)->create([
            'object_id' => $agent->id,
            'object_type' => 'agent',
        ]);

        $this->assertCount(3, $agent->profileSnapshots);
    }

    public function test_scope_latest_snapshot_ordena_por_recorded_at_desc(): void
    {
        $agent = Agent::factory()->create();

        $antigo = ProfileSnapshot::factory()->create([
            'object_id' => $agent->id,
            'object_type' => 'agent',
            'recorded_at' => now()->subDays(5),
        ]);

        $recente = ProfileSnapshot::factory()->create([
            'object_id' => $agent->id,
            'object_type' => 'agent',
            'recorded_at' => now(),
        ]);

        $primeiro = $agent->profileSnapshots()->latestSnapshot()->first();

        $this->assertEquals($recente->id, $primeiro->id);
    }
}
