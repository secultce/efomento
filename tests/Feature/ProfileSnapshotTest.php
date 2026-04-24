<?php

namespace Tests\Feature;

use App\Enums\Gender;
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
            'gender' => Gender::MASCULINO->value,
        ], ProfileSnapshotSource::AGENT_UPDATE);

        $this->assertDatabaseHas('profile_snapshots', [
            'object_id'   => $agent->id,
            'object_type' => 'agent',
            'gender'      => Gender::MASCULINO->value,
            'source'      => ProfileSnapshotSource::AGENT_UPDATE->value,
        ]);
    }

    public function test_snapshot_anterior_permanece_intacto_apos_novo_registro(): void
    {
        $agent = Agent::factory()->create();

        $primeiro = $this->service->record($agent, [
            'gender' => Gender::OUTRO->value,
        ], ProfileSnapshotSource::AGENT_UPDATE);

        $this->service->record($agent, [
            'gender' => Gender::MASCULINO->value,
        ], ProfileSnapshotSource::AGENT_UPDATE);

        $this->assertDatabaseHas('profile_snapshots', [
            'id'     => $primeiro->id,
            'gender' => Gender::OUTRO->value,
        ]);

        $this->assertEquals(2, $agent->profileSnapshots()->count());
    }

    public function test_latest_snapshot_retorna_o_mais_recente(): void
    {
        $agent = Agent::factory()->create();

        ProfileSnapshot::factory()->create([
            'object_id'   => $agent->id,
            'object_type' => 'agent',
            'gender'      => Gender::OUTRO->value,
            'recorded_at' => now()->subDay(),
        ]);

        ProfileSnapshot::factory()->create([
            'object_id'   => $agent->id,
            'object_type' => 'agent',
            'gender'      => Gender::MASCULINO->value,
            'recorded_at' => now(),
        ]);

        $latest = $agent->latestSnapshot;

        $this->assertEquals(Gender::MASCULINO, $latest->gender);
    }

    public function test_cria_snapshot_para_project(): void
    {
        $project = Project::factory()->create();

        $this->service->record($project, [
            'gender' => Gender::FEMININO->value,
        ], ProfileSnapshotSource::PROJECT_REGISTRATION);

        $this->assertDatabaseHas('profile_snapshots', [
            'object_id'   => $project->id,
            'object_type' => 'project',
            'gender'      => Gender::FEMININO->value,
            'source'      => ProfileSnapshotSource::PROJECT_REGISTRATION->value,
        ]);
    }

    public function test_agent_acessa_multiplos_snapshots_via_relacionamento(): void
    {
        $agent = Agent::factory()->create();

        ProfileSnapshot::factory()->count(3)->create([
            'object_id'   => $agent->id,
            'object_type' => 'agent',
        ]);

        $this->assertCount(3, $agent->profileSnapshots);
    }

    public function test_scope_latest_snapshot_ordena_por_recorded_at_desc(): void
    {
        $agent = Agent::factory()->create();

        $antigo = ProfileSnapshot::factory()->create([
            'object_id'   => $agent->id,
            'object_type' => 'agent',
            'recorded_at' => now()->subDays(5),
        ]);

        $recente = ProfileSnapshot::factory()->create([
            'object_id'   => $agent->id,
            'object_type' => 'agent',
            'recorded_at' => now(),
        ]);

        $primeiro = $agent->profileSnapshots()->latestSnapshot()->first();

        $this->assertEquals($recente->id, $primeiro->id);
    }
}
