<?php

namespace Tests\Feature;

use App\Enums\DiligenceDirection;
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
        $this->monitoring = Monitoring::factory()->for($this->project)->create();
    }

    public function test_guest_cannot_access_diligences(): void
    {
        $this->getJson(route('diligences.index', ['project' => $this->project, 'stage' => 'monitoramento']))
            ->assertUnauthorized();
    }

    public function test_index_returns_thread_messages(): void
    {
        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Favor enviar o relatório de monitoramento atualizado.',
            'imap_message_id' => '<diligence_test_1@efomento>',
            'sent_at' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::INBOUND,
            'from_email' => 'agente@example.com',
            'to_email' => 'efomento@example.com',
            'subject' => 'Re: Diligência — Monitoramento',
            'body' => 'Segue em anexo o relatório solicitado.',
            'imap_message_id' => '<resposta_test_1@example.com>',
            'in_reply_to' => '<diligence_test_1@efomento>',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('diligences.index', ['project' => $this->project, 'stage' => 'monitoramento']));

        $response->assertOk()
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
            ->assertJsonPath('messages.0.direction', 'OUTBOUND')
            ->assertJsonPath('messages.0.creator', $this->user->name)
            ->assertJsonPath('messages.1.direction', 'INBOUND');
    }

    public function test_index_does_not_leak_messages_from_other_projects(): void
    {
        DiligenceMessage::factory()->create([
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $this->monitoring->id,
        ]);

        DiligenceMessage::factory()->create();

        $this->actingAs($this->user)
            ->getJson(route('diligences.index', ['project' => $this->project, 'stage' => 'monitoramento']))
            ->assertOk()
            ->assertJsonCount(1, 'messages');
    }

    public function test_index_returns_404_when_project_has_no_monitoring_yet(): void
    {
        $projectWithoutMonitoring = Project::factory()->create();

        $this->actingAs($this->user)
            ->getJson(route('diligences.index', ['project' => $projectWithoutMonitoring, 'stage' => 'monitoramento']))
            ->assertNotFound();
    }

    public function test_index_returns_404_for_stage_without_resolver(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('diligences.index', ['project' => $this->project, 'stage' => 'abertura']))
            ->assertNotFound();
    }

    public function test_store_creates_outbound_message_and_sends_mail(): void
    {
        Mail::fake();

        $payload = [
            'subject' => 'Diligência — Monitoramento — Projeto Teste',
            'body' => 'Favor enviar o relatório de monitoramento atualizado.',
            'to_email' => 'agente@example.com',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('diligences.store', ['project' => $this->project, 'stage' => 'monitoramento']), $payload);

        $response->assertCreated()
            ->assertJsonPath('message.direction', DiligenceDirection::OUTBOUND->value)
            ->assertJsonPath('message.to_email', 'agente@example.com');

        $this->assertDatabaseHas('diligence_messages', [
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $this->monitoring->id,
            'direction' => DiligenceDirection::OUTBOUND->value,
            'to_email' => 'agente@example.com',
            'created_by' => $this->user->id,
        ]);

        Mail::assertSent(DiligenceMail::class, function (DiligenceMail $mail) {
            return $mail->hasTo('agente@example.com');
        });
    }

    public function test_store_builds_rfc_compliant_email_headers(): void
    {
        // Sem Mail::fake(): o mailer de teste constrói o e-mail real e o Symfony
        // valida o Message-ID contra o RFC 2822 (regressão do header inválido).
        $this->actingAs($this->user)
            ->postJson(route('diligences.store', ['project' => $this->project, 'stage' => 'monitoramento']), [
                'subject' => 'Diligência — Monitoramento',
                'body' => 'Favor enviar o relatório de monitoramento atualizado.',
                'to_email' => 'agente@example.com',
            ])
            ->assertCreated();

        $messageId = $this->monitoring->diligenceMessages()->first()->imap_message_id;
        $this->assertMatchesRegularExpression('/^<diligence_[^@]+@[^>]+\.[^>]+>$/', $messageId);
    }

    public function test_store_validates_payload(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->postJson(route('diligences.store', ['project' => $this->project, 'stage' => 'monitoramento']), [
                'subject' => '',
                'body' => 'curta',
                'to_email' => 'nao-eh-email',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'body', 'to_email']);

        Mail::assertNothingSent();
    }

    public function test_store_returns_404_for_stage_without_resolver(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->postJson(route('diligences.store', ['project' => $this->project, 'stage' => 'abertura']), [
                'subject' => 'Diligência — Abertura',
                'body' => 'Favor enviar a documentação pendente da abertura.',
                'to_email' => 'agente@example.com',
            ])
            ->assertNotFound();

        Mail::assertNothingSent();
    }
}
