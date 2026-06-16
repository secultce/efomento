<?php

namespace Tests\Feature;

use App\Enums\DiligenceDirection;
use App\Models\DiligenceMessage;
use App\Models\Monitoring;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_monitoring(): void
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
            'technical_opinions' => [],
            'date_of_notification_to_the_agent' => '2024-09-01',
            'observations' => 'Teste',
        ]);

        $this->assertDatabaseHas('monitorings', [
            'id' => $monitoring->id,
            'project_id' => $project->id,
        ]);
    }

    #[Test]
    public function dates_are_cast_correctly(): void
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
            'technical_opinions' => [],
            'date_of_notification_to_the_agent' => '2024-09-01',
        ]);

        $this->assertInstanceOf(Carbon::class, $monitoring->effective_date_of_the_instrument);
        $this->assertEquals('2024-01-01', $monitoring->effective_date_of_the_instrument->format('Y-m-d'));

        $this->assertInstanceOf(Carbon::class, $monitoring->expiration_date_of_the_instrument);
        $this->assertEquals('2024-12-31', $monitoring->expiration_date_of_the_instrument->format('Y-m-d'));

        $this->assertInstanceOf(Carbon::class, $monitoring->deadline_for_completing_report);
        $this->assertInstanceOf(Carbon::class, $monitoring->deadline_for_analysis_and_issuance_of_the_opinion);
        $this->assertInstanceOf(Carbon::class, $monitoring->date_of_notification_to_the_agent);
    }

    #[Test]
    public function relationships_resolve_correctly(): void
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
            'technical_opinions' => [],
            'date_of_notification_to_the_agent' => now(),
        ]);

        $this->assertEquals($project->id, $monitoring->project->id);
        $this->assertEquals($user->id, $monitoring->creator->id);
    }

    #[Test]
    public function soft_delete_keeps_record_in_database(): void
    {
        $monitoring = Monitoring::factory()->create();

        $monitoring->delete();

        $this->assertSoftDeleted('monitorings', ['id' => $monitoring->id]);
    }

    #[Test]
    public function fillable_fields_match_expected_columns(): void
    {
        $monitoring = new Monitoring;

        $this->assertEquals([
            'project_id',
            'created_by',
            'effective_date_of_the_instrument',
            'expiration_date_of_the_instrument',
            'deadline_for_completing_report',
            'deadline_for_analysis_and_issuance_of_the_opinion',
            'technical_opinions',
            'date_of_notification_to_the_agent',
            'observations',
            'processed_at',
        ], $monitoring->getFillable());
    }

    #[Test]
    public function technical_opinions_defaults_to_empty_array(): void
    {
        $monitoring = Monitoring::factory()->create(['technical_opinions' => []]);

        $this->assertIsArray($monitoring->technical_opinions);
        $this->assertEmpty($monitoring->technical_opinions);
    }

    #[Test]
    public function technical_opinions_stores_and_retrieves_array_of_opinions(): void
    {
        $opinions = [
            ['suite_number' => '1234', 'processing_date' => '2024-03-15'],
            ['suite_number' => '5678', 'processing_date' => '2024-05-20'],
        ];

        $monitoring = Monitoring::factory()->create(['technical_opinions' => $opinions]);

        $fresh = $monitoring->fresh();

        $this->assertIsArray($fresh->technical_opinions);
        $this->assertCount(2, $fresh->technical_opinions);
        $this->assertEquals('1234', $fresh->technical_opinions[0]['suite_number']);
        $this->assertEquals('2024-03-15', $fresh->technical_opinions[0]['processing_date']);
        $this->assertEquals('5678', $fresh->technical_opinions[1]['suite_number']);
    }

    #[Test]
    public function technical_opinions_can_be_updated_by_adding_new_entry(): void
    {
        $monitoring = Monitoring::factory()->create([
            'technical_opinions' => [
                ['suite_number' => '0001', 'processing_date' => '2024-01-10'],
            ],
        ]);

        $updated = array_merge($monitoring->technical_opinions, [
            ['suite_number' => '0002', 'processing_date' => '2024-06-10'],
        ]);

        $monitoring->update(['technical_opinions' => $updated]);

        $this->assertCount(2, $monitoring->fresh()->technical_opinions);
    }

    #[Test]
    public function diligence_messages_morph_relation_returns_messages_in_order(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->create();

        $monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Favor enviar o relatório.',
            'imap_message_id' => '<msg1@efomento>',
            'sent_at' => now()->subDay(),
            'created_by' => $user->id,
        ]);

        $monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::INBOUND,
            'from_email' => 'agente@example.com',
            'to_email' => 'efomento@example.com',
            'subject' => 'Re: Diligência — Monitoramento',
            'body' => 'Segue o relatório.',
            'imap_message_id' => '<msg2@agente>',
            'in_reply_to' => '<msg1@efomento>',
            'sent_at' => now(),
            'created_by' => $user->id,
        ]);

        $messages = $monitoring->diligenceMessages;

        $this->assertCount(2, $messages);
        $this->assertEquals(DiligenceDirection::OUTBOUND, $messages->first()->direction);
        $this->assertEquals(DiligenceDirection::INBOUND, $messages->last()->direction);
    }

    #[Test]
    public function diligence_messages_are_polymorphically_linked_to_monitoring(): void
    {
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->create();

        $message = $monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Assunto',
            'body' => 'Corpo da mensagem.',
            'imap_message_id' => '<msg-poly@efomento>',
            'sent_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('diligence_messages', [
            'id' => $message->id,
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $monitoring->id,
        ]);

        $resolved = DiligenceMessage::find($message->id)->diligenceable;
        $this->assertInstanceOf(Monitoring::class, $resolved);
        $this->assertTrue($resolved->is($monitoring));
    }

    #[Test]
    public function processed_at_is_cast_to_datetime(): void
    {
        $monitoring = Monitoring::factory()->create(['processed_at' => '2024-08-01 10:30:00']);

        $this->assertInstanceOf(Carbon::class, $monitoring->processed_at);
        $this->assertEquals('2024-08-01', $monitoring->processed_at->format('Y-m-d'));
    }

    #[Test]
    public function processed_at_is_nullable(): void
    {
        $monitoring = Monitoring::factory()->create(['processed_at' => null]);

        $this->assertNull($monitoring->processed_at);
    }

    #[Test]
    public function project_monitoring_relation_is_accessible_from_project(): void
    {
        $project = Project::factory()->create();
        $monitoring = Monitoring::factory()->for($project)->create();

        $this->assertTrue($project->monitoring->is($monitoring));
    }
}
