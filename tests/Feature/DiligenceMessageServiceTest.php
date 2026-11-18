<?php

namespace Tests\Feature;

use App\Enums\DiligenceDirection;
use App\Mail\DiligenceMail;
use App\Models\DiligenceMessage;
use App\Models\Monitoring;
use App\Models\Project;
use App\Models\User;
use App\Services\DiligenceMessageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Client as ImapClient;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\MessageCollection;

class DiligenceMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private DiligenceMessageService $service;

    private User $user;

    private Monitoring $monitoring;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->monitoring = Monitoring::factory()->for(Project::factory())->create();
        $this->service = app(DiligenceMessageService::class);
    }

    public function test_send_persists_outbound_message_with_sender_data(): void
    {
        Mail::fake();

        $message = $this->service->send(
            diligenceable: $this->monitoring,
            subject: 'Diligência — Monitoramento',
            body: 'Favor enviar o relatório de monitoramento atualizado.',
            toEmail: 'agente@example.com',
            sender: $this->user,
        );

        $this->assertDatabaseHas('diligence_messages', [
            'id' => $message->id,
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $this->monitoring->id,
            'direction' => DiligenceDirection::OUTBOUND->value,
            'from_email' => config('mail.from.address'),
            'to_email' => 'agente@example.com',
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($message->diligenceable->is($this->monitoring));
        $this->assertNotNull($message->sent_at);
    }

    public function test_send_generates_unique_message_ids_with_diligence_prefix(): void
    {
        Mail::fake();

        $first = $this->sendMessage();
        $second = $this->sendMessage();

        $this->assertMatchesRegularExpression('/^<diligence_[^@]+@[^>]+\.[^>]+>$/', $first->imap_message_id);
        $this->assertStringEndsWith('@efomento.ce.gov.br>', $first->imap_message_id);
        $this->assertNotSame($first->imap_message_id, $second->imap_message_id);
    }

    public function test_send_threads_the_reply_to_the_latest_message(): void
    {
        Mail::fake();

        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Primeira mensagem da diligência.',
            'imap_message_id' => '<diligence_antiga@efomento.ce.gov.br>',
            'sent_at' => now()->subDays(2),
            'created_by' => $this->user->id,
        ]);

        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Mensagem mais recente da diligência.',
            'imap_message_id' => '<diligence_recente@efomento.ce.gov.br>',
            'sent_at' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $message = $this->sendMessage();

        $this->assertSame('<diligence_recente@efomento.ce.gov.br>', $message->in_reply_to);
    }

    public function test_send_first_message_of_the_thread_has_no_in_reply_to(): void
    {
        Mail::fake();

        $this->assertNull($this->sendMessage()->in_reply_to);
    }

    public function test_send_dispatches_mail_to_the_recipient(): void
    {
        Mail::fake();

        $message = $this->sendMessage();

        Mail::assertSent(DiligenceMail::class, function (DiligenceMail $mail) use ($message) {
            return $mail->hasTo('agente@example.com') && $mail->diligenceMessage->is($message);
        });
    }

    public function test_send_copies_the_secondary_recipient(): void
    {
        Mail::fake();

        $message = $this->service->send(
            diligenceable: $this->monitoring,
            subject: 'Diligência — Monitoramento',
            body: 'Favor enviar o relatório de monitoramento atualizado.',
            toEmail: 'agente@example.com',
            sender: $this->user,
            ccEmail: 'agente.secundario@example.com',
        );

        Mail::assertSent(DiligenceMail::class, function (DiligenceMail $mail) use ($message) {
            return $mail->hasTo('agente@example.com')
                && $mail->hasCc('agente.secundario@example.com')
                && $mail->diligenceMessage->is($message);
        });
    }

    public function test_send_ignores_an_invalid_or_duplicated_secondary_recipient(): void
    {
        Mail::fake();

        $this->service->send(
            diligenceable: $this->monitoring,
            subject: 'Diligência — Monitoramento',
            body: 'Favor enviar o relatório de monitoramento atualizado.',
            toEmail: 'agente@example.com',
            sender: $this->user,
            ccEmail: 'AGENTE@example.com',
        );

        Mail::assertSent(DiligenceMail::class, fn (DiligenceMail $mail) => $mail->hasTo('agente@example.com')
            && ! $mail->hasCc('AGENTE@example.com'));
    }

    public function test_message_id_domain_falls_back_when_reply_to_has_no_domain(): void
    {
        Mail::fake();

        config(['efomento.diligence_reply_to' => 'endereco-invalido']);

        $message = $this->sendMessage();

        $this->assertStringEndsWith('@efomento.ce.gov.br>', $message->imap_message_id);
    }

    public function test_sync_incoming_creates_inbound_reply_on_the_same_thread(): void
    {
        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Favor enviar o relatório de monitoramento atualizado.',
            'imap_message_id' => '<diligence_origem@efomento.ce.gov.br>',
            'sent_at' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $sentAt = now()->subHour()->startOfSecond();

        $this->fakeImapInbox([
            $this->fakeImapMessage([
                'message_id' => 'resposta@example.com',
                'in_reply_to' => 'diligence_origem@efomento.ce.gov.br',
                'from' => 'agente@example.com',
                'subject' => 'Re: Diligência — Monitoramento',
                'bodies' => ['text' => 'Segue em anexo o relatório solicitado.'],
                'date' => $sentAt,
            ]),
        ]);

        $count = $this->service->syncIncoming();

        $this->assertSame(1, $count);

        $this->assertDatabaseHas('diligence_messages', [
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $this->monitoring->id,
            'direction' => DiligenceDirection::INBOUND->value,
            'from_email' => 'agente@example.com',
            'to_email' => config('mail.from.address'),
            'imap_message_id' => '<resposta@example.com>',
            'in_reply_to' => '<diligence_origem@efomento.ce.gov.br>',
            'body' => 'Segue em anexo o relatório solicitado.',
        ]);

        $inbound = DiligenceMessage::where('imap_message_id', '<resposta@example.com>')->first();
        $this->assertTrue($inbound->sent_at->equalTo($sentAt));
    }

    public function test_sync_incoming_normalizes_the_imap_date_to_the_application_timezone(): void
    {
        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Favor enviar o relatório de monitoramento atualizado.',
            'imap_message_id' => '<diligence_origem@efomento.ce.gov.br>',
            'sent_at' => Carbon::parse('2026-08-24 14:35:32', 'UTC'),
            'created_by' => $this->user->id,
        ]);

        $this->fakeImapInbox([
            $this->fakeImapMessage([
                'message_id' => 'resposta_timezone@example.com',
                'in_reply_to' => 'diligence_origem@efomento.ce.gov.br',
                'from' => 'agente@example.com',
                'subject' => 'Re: Diligência — Monitoramento',
                'bodies' => ['text' => 'Segue o relatório solicitado.'],
                'date' => Carbon::parse('2026-08-24 11:39:11', 'America/Fortaleza'),
            ]),
        ]);

        $this->assertSame(1, $this->service->syncIncoming());

        $inbound = DiligenceMessage::where('imap_message_id', '<resposta_timezone@example.com>')->firstOrFail();
        $this->assertSame('2026-08-24 14:39:11', $inbound->sent_at->format('Y-m-d H:i:s'));
    }

    public function test_sync_incoming_stores_non_inline_attachments_on_the_message(): void
    {
        $disk = config('efomento.file_disk', 'public');
        Storage::fake($disk);

        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Favor enviar o relatório de monitoramento atualizado.',
            'imap_message_id' => '<diligence_origem@efomento.ce.gov.br>',
            'sent_at' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $this->fakeImapInbox([
            $this->fakeImapMessage([
                'message_id' => 'resposta_com_anexo@example.com',
                'in_reply_to' => 'diligence_origem@efomento.ce.gov.br',
                'from' => 'agente@example.com',
                'subject' => 'Re: Diligência — Monitoramento',
                'bodies' => ['text' => 'Segue o relatório solicitado.'],
                'date' => now(),
                'attachments' => [
                    $this->fakeImapAttachment('relatorio final.pdf', 'application/pdf', 'conteudo-pdf', 'anexo-1'),
                    $this->fakeImapAttachment('assinatura.png', 'image/png', 'imagem-inline', 'inline-1', 'inline'),
                ],
            ]),
        ]);

        $this->assertSame(1, $this->service->syncIncoming());

        $message = DiligenceMessage::where('imap_message_id', '<resposta_com_anexo@example.com>')->firstOrFail();
        $attachment = $message->attachments()->sole();

        $this->assertSame('relatorio final.pdf', $attachment->name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame('imap', $attachment->source);
        $this->assertSame('anexo-1:0', $attachment->external_id);
        $this->assertTrue($attachment->private);
        Storage::disk($disk)->assertExists($attachment->path);
        $this->assertSame('conteudo-pdf', Storage::disk($disk)->get($attachment->path));
    }

    public function test_sync_incoming_skips_messages_already_imported(): void
    {
        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::INBOUND,
            'from_email' => 'agente@example.com',
            'to_email' => 'efomento@example.com',
            'subject' => 'Re: Diligência — Monitoramento',
            'body' => 'Resposta já importada.',
            'imap_message_id' => '<resposta@example.com>',
            'sent_at' => now()->subDay(),
        ]);

        $this->fakeImapInbox([
            $this->fakeImapMessage([
                'message_id' => 'resposta@example.com',
                'in_reply_to' => 'diligence_origem@efomento.ce.gov.br',
                'from' => 'agente@example.com',
                'subject' => 'Re: Diligência — Monitoramento',
                'bodies' => ['text' => 'Resposta já importada.'],
                'date' => now(),
            ]),
        ]);

        $this->assertSame(0, $this->service->syncIncoming());
        $this->assertSame(1, $this->monitoring->diligenceMessages()->count());
    }

    public function test_sync_incoming_backfills_attachments_for_an_existing_message(): void
    {
        $disk = config('efomento.file_disk', 'public');
        Storage::fake($disk);

        $message = $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::INBOUND,
            'from_email' => 'agente@example.com',
            'to_email' => 'efomento@example.com',
            'subject' => 'Re: Diligência — Monitoramento',
            'body' => 'Resposta já importada.',
            'imap_message_id' => '<resposta_existente@example.com>',
            'sent_at' => now()->subDay(),
        ]);

        $this->fakeImapInbox([
            $this->fakeImapMessage([
                'message_id' => 'resposta_existente@example.com',
                'in_reply_to' => 'diligence_origem@efomento.ce.gov.br',
                'from' => 'agente@example.com',
                'subject' => 'Re: Diligência — Monitoramento',
                'bodies' => ['text' => 'Resposta já importada.'],
                'date' => now(),
                'attachments' => [
                    $this->fakeImapAttachment('relatorio.pdf', 'application/pdf', 'conteudo-pdf', 'anexo-existente'),
                ],
            ]),
        ]);

        $this->assertSame(0, $this->service->syncIncoming());

        $attachment = $message->attachments()->sole();
        $this->assertSame('relatorio.pdf', $attachment->name);
        Storage::disk($disk)->assertExists($attachment->path);
    }

    public function test_sync_incoming_corrects_the_timestamp_of_an_existing_message(): void
    {
        $message = $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::INBOUND,
            'from_email' => 'agente@example.com',
            'to_email' => 'efomento@example.com',
            'subject' => 'Re: Diligência — Monitoramento',
            'body' => 'Resposta já importada com horário incorreto.',
            'imap_message_id' => '<resposta_horario_existente@example.com>',
            'sent_at' => Carbon::parse('2026-08-24 11:39:11', 'UTC'),
        ]);

        $this->fakeImapInbox([
            $this->fakeImapMessage([
                'message_id' => 'resposta_horario_existente@example.com',
                'in_reply_to' => 'diligence_origem@efomento.ce.gov.br',
                'from' => 'agente@example.com',
                'subject' => 'Re: Diligência — Monitoramento',
                'bodies' => ['text' => 'Resposta já importada com horário incorreto.'],
                'date' => Carbon::parse('2026-08-24 11:39:11', 'America/Fortaleza'),
            ]),
        ]);

        $this->assertSame(0, $this->service->syncIncoming());
        $this->assertSame('2026-08-24 14:39:11', $message->fresh()->sent_at->format('Y-m-d H:i:s'));
    }

    public function test_sync_incoming_ignores_replies_without_matching_diligence(): void
    {
        $this->fakeImapInbox([
            $this->fakeImapMessage([
                'message_id' => 'spam@example.com',
                'in_reply_to' => 'desconhecido@example.com',
                'from' => 'desconhecido@example.com',
                'subject' => 'Mensagem sem diligência',
                'bodies' => ['text' => 'Não deveria ser importada.'],
                'date' => now(),
            ]),
        ]);

        $this->assertSame(0, $this->service->syncIncoming());
        $this->assertSame(0, $this->monitoring->diligenceMessages()->count());
    }

    public function test_sync_incoming_strips_tags_from_html_body_when_there_is_no_text_body(): void
    {
        $this->monitoring->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => 'efomento@example.com',
            'to_email' => 'agente@example.com',
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Favor enviar o relatório de monitoramento atualizado.',
            'imap_message_id' => '<diligence_origem@efomento.ce.gov.br>',
            'sent_at' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $this->fakeImapInbox([
            $this->fakeImapMessage([
                'message_id' => 'resposta_html@example.com',
                'in_reply_to' => 'diligence_origem@efomento.ce.gov.br',
                'from' => 'agente@example.com',
                'subject' => 'Re: Diligência — Monitoramento',
                'bodies' => ['html' => '<p>Segue o <strong>relatório</strong>.</p>'],
                'date' => now(),
            ]),
        ]);

        $this->assertSame(1, $this->service->syncIncoming());

        $this->assertDatabaseHas('diligence_messages', [
            'imap_message_id' => '<resposta_html@example.com>',
            'body' => 'Segue o relatório.',
        ]);
    }

    public function test_sync_incoming_returns_zero_when_inbox_is_empty(): void
    {
        $this->fakeImapInbox([]);

        $this->assertSame(0, $this->service->syncIncoming());
    }

    private function sendMessage(): DiligenceMessage
    {
        return $this->service->send(
            diligenceable: $this->monitoring,
            subject: 'Diligência — Monitoramento',
            body: 'Favor enviar o relatório de monitoramento atualizado.',
            toEmail: 'agente@example.com',
            sender: $this->user,
        );
    }

    private function fakeImapInbox(array $messages): void
    {
        $query = Mockery::mock(WhereQuery::class);
        $query->shouldReceive('unseen')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(MessageCollection::make($messages));

        $folder = Mockery::mock(Folder::class);
        $folder->shouldReceive('messages')->andReturn($query);

        $client = Mockery::mock(ImapClient::class);
        $client->shouldReceive('connect')->once()->andReturnSelf();
        $client->shouldReceive('getFolderByName')->with('INBOX')->andReturn($folder);
        $client->shouldReceive('disconnect')->once()->andReturnSelf();

        Client::shouldReceive('account')->with('default')->andReturn($client);
    }

    /**
     * Espelha a API real do webklex/php-imap: bodies são strings e os headers
     * Message-ID/In-Reply-To chegam sem os colchetes angulares.
     */
    private function fakeImapMessage(array $attributes): object
    {
        return new class($attributes)
        {
            public function __construct(private readonly array $attributes) {}

            public function __get(string $key): mixed
            {
                return $this->attributes[$key] ?? null;
            }

            public function hasTextBody(): bool
            {
                return ($this->attributes['bodies']['text'] ?? '') !== '';
            }

            public function getTextBody(): string
            {
                return $this->attributes['bodies']['text'] ?? '';
            }

            public function getHTMLBody(): string
            {
                return $this->attributes['bodies']['html'] ?? '';
            }

            public function getAttachments(): Collection
            {
                return collect($this->attributes['attachments'] ?? []);
            }
        };
    }

    private function fakeImapAttachment(
        string $name,
        string $contentType,
        string $content,
        string $id,
        ?string $disposition = 'attachment',
    ): object {
        return new class($name, $contentType, $content, $id, $disposition)
        {
            public string $filename;

            public string $hash;

            public function __construct(
                public string $name,
                public string $content_type,
                private readonly string $content,
                public string $id,
                public ?string $disposition,
            ) {
                $this->filename = $name;
                $this->hash = hash('sha256', $content);
            }

            public function getContent(): string
            {
                return $this->content;
            }
        };
    }
}
