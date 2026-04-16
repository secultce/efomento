<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_monitoring()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $monitoring = Monitoring::create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'effective_date_of_the_instrument' => '2024-01-01',
            'expiration_date_of_the_instrument' => '2024-12-31',
            'deadline_for_completing_report' => '2024-06-01',
            'deadline_for_analysis_and_issuance_of_the_opinion' => '2024-07-01',
            'date_of_processing_of_the_opinion_via_suite' => '2024-08-01',
            'date_of_notification_to_the_agent' => '2024-09-01',
            'observations' => 'Teste',
        ]);

        $this->assertDatabaseHas('monitorings', [
            'id' => $monitoring->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_dates_are_cast_correctly()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $monitoring = Monitoring::create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'effective_date_of_the_instrument' => '2024-01-01',
            'expiration_date_of_the_instrument' => '2024-12-31',
            'deadline_for_completing_report' => '2024-06-01',
            'deadline_for_analysis_and_issuance_of_the_opinion' => '2024-07-01',
            'date_of_processing_of_the_opinion_via_suite' => '2024-08-01',
            'date_of_notification_to_the_agent' => '2024-09-01',
        ]);

        $this->assertInstanceOf(Carbon::class, $monitoring->effective_date_of_the_instrument);
        $this->assertEquals('2024-01-01', $monitoring->effective_date_of_the_instrument->format('Y-m-d'));
    }

    public function test_relationships()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $monitoring = Monitoring::create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'effective_date_of_the_instrument' => now(),
            'expiration_date_of_the_instrument' => now(),
            'deadline_for_completing_report' => now(),
            'deadline_for_analysis_and_issuance_of_the_opinion' => now(),
            'date_of_processing_of_the_opinion_via_suite' => now(),
            'date_of_notification_to_the_agent' => now(),
        ]);

        $this->assertEquals($project->id, $monitoring->project->id);
        $this->assertEquals($user->id, $monitoring->creator->id);
    }

    public function test_soft_delete()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $monitoring = Monitoring::create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'effective_date_of_the_instrument' => now(),
            'expiration_date_of_the_instrument' => now(),
            'deadline_for_completing_report' => now(),
            'deadline_for_analysis_and_issuance_of_the_opinion' => now(),
            'date_of_processing_of_the_opinion_via_suite' => now(),
            'date_of_notification_to_the_agent' => now(),
        ]);

        $monitoring->delete();

        $this->assertSoftDeleted('monitorings', [
            'id' => $monitoring->id,
        ]);
    }

    public function test_fillable_fields()
    {
        $monitoring = new Monitoring();

        $this->assertEquals([
            'project_id',
            'created_by',
            'effective_date_of_the_instrument',
            'expiration_date_of_the_instrument',
            'deadline_for_completing_report',
            'deadline_for_analysis_and_issuance_of_the_opinion',
            'date_of_processing_of_the_opinion_via_suite',
            'date_of_notification_to_the_agent',
            'observations',
            'processed_at',
        ], $monitoring->getFillable());
    }
}
