<?php

namespace Tests\Feature;

use App\Enums\DiligenceDirection;
use App\Enums\ProjectStageSlug;
use App\Mail\DiligenceMail;
use App\Models\DiligenceMessage;
use App\Models\Monitoring;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DiligenceMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Monitoring $monitoring;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->monitoring = Monitoring::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Diligência: documentação pendente',
            'body' => 'Favor encaminhar os documentos pendentes da prestação de contas.',
            'to_email' => 'agente@example.com',
        ], $overrides);
    }

    public function test_guest_cannot_access_diligence_messages(): void
    {
        $this->getJson(route('diligences.index', [$this->project, ProjectStageSlug::MONITORAMENTO->value]))
            ->assertUnauthorized();
    }

    public function test_index_returns_messages_for_the_stage(): void
    {
        DiligenceMessage::factory()->count(2)->create([
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $this->monitoring->id,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('diligences.index', [$this->project, ProjectStageSlug::MONITORAMENTO->value]))
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonStructure([
                'messages' => [
                    '*' => [
                        'id',
                        'direction',
                        'from_email',
                        'to_email',
                        'subject',
                        'body',
                        'sent_at',
                        'read_at',
                        'creator',
                    ],
                ],
            ])
            ->assertJsonPath('messages.0.creator', $this->user->name);
    }

    public function test_index_does_not_leak_messages_from_other_projects(): void
    {
        DiligenceMessage::factory()->create([
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $this->monitoring->id,
        ]);

        DiligenceMessage::factory()->create();

        $this->actingAs($this->user)
            ->getJson(route('diligences.index', [$this->project, ProjectStageSlug::MONITORAMENTO->value]))
            ->assertOk()
            ->assertJsonCount(1, 'messages');
    }

    public function test_index_returns_404_for_stage_without_diligence_support(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('diligences.index', [$this->project, ProjectStageSlug::ABERTURA->value]))
            ->assertNotFound();
    }

    public function test_store_creates_message_and_sends_mail(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->postJson(
                route('diligences.store', [$this->project, ProjectStageSlug::MONITORAMENTO->value]),
                $this->validPayload()
            )
            ->assertCreated()
            ->assertJsonPath('message.subject', 'Diligência: documentação pendente');

        $this->assertDatabaseHas('diligence_messages', [
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $this->monitoring->id,
            'direction' => DiligenceDirection::OUTBOUND->value,
            'to_email' => 'agente@example.com',
            'created_by' => $this->user->id,
        ]);

        Mail::assertSent(
            DiligenceMail::class,
            fn (DiligenceMail $mail) => $mail->hasTo('agente@example.com')
        );
    }

    public function test_store_validates_payload(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->postJson(
                route('diligences.store', [$this->project, ProjectStageSlug::MONITORAMENTO->value]),
                [
                    'subject' => '',
                    'body' => 'curta',
                    'to_email' => 'nao-eh-email',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'body', 'to_email']);

        Mail::assertNothingSent();
    }

    public function test_store_returns_404_for_stage_without_diligence_support(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->postJson(
                route('diligences.store', [$this->project, ProjectStageSlug::ABERTURA->value]),
                $this->validPayload()
            )
            ->assertNotFound();

        Mail::assertNothingSent();
    }
}
