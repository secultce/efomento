<?php

namespace Tests\Feature;

use App\Enums\DiligenceDirection;
use App\Mail\DiligenceMail;
use App\Models\DiligenceMessage;
use App\Models\Monitoring;
use App\Models\User;
use App\Services\DiligenceMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DiligenceMessageService::class);
    }

    private function fakeImapMessage(array $attributes = []): object
    {
        return new class($attributes)
        {
            public string $message_id;

            public string $in_reply_to;

            public string $from;

            public string $subject;

            public array $bodies;

            public Carbon $date;

            private bool $hasText;

            public function __construct(array $attributes)
            {
                $this->message_id = $attributes['message_id'] ?? '<resposta@example.com>';
                $this->in_reply_to = $attributes['in_reply_to'] ?? '';
                $this->from = $attributes['from'] ?? 'agente@example.com';
                $this->subject = $attributes['subject'] ?? 'Re: Diligência';
                $this->bodies = $attributes['bodies'] ?? [
                    'text' => (object) ['content' => 'Resposta do agente cultural.'],
                ];
                $this->date = $attributes['date'] ?? now();
                $this->hasText = $attributes['hasText'] ?? true;
            }

            public function hasTextBody(): bool
            {
                return $this->hasText;
            }
        };
    }

    private function mockImapInbox(array $messages): void
    {
        $query = Mockery::mock(WhereQuery::class);
        $query->shouldReceive('unseen')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(new MessageCollection($messages));

        $folder = Mockery::mock(Folder::class);
        $folder->shouldReceive('messages')->andReturn($query);

        $client = Mockery::mock(ImapClient::class);
        $client->shouldReceive('connect')->once()->andReturnSelf();
        $client->shouldReceive('getFolderByName')->with('INBOX')->andReturn($folder);
        $client->shouldReceive('disconnect')->once()->andReturnSelf();

        Client::shouldReceive('account')->with('default')->andReturn($client);
    }

    public function test_send_creates_outbound_message_and_sends_mail(): void
    {
        Mail::fake();

        $monitoring = Monitoring::factory()->create();
        $sender = User::factory()->create();

        $message = $this->service->send(
            $monitoring,
            'Diligência: pendências',
            'Há pendências na prestação de contas.',
            'agente@example.com',
            $sender
        );

        $this->assertTrue($message->exists);
        $this->assertEquals(DiligenceDirection::OUTBOUND, $message->direction);
        $this->assertSame(config('mail.from.address'), $message->from_email);
        $this->assertSame('agente@example.com', $message->to_email);
        $this->assertSame($sender->id, $message->created_by);
        $this->assertNull($message->in_reply_to);
        $this->assertMatchesRegularExpression('/^<diligence_.+@efomento>$/', $message->imap_message_id);
        $this->assertNotNull($message->sent_at);
        $this->assertTrue($message->diligenceable->is($monitoring));

        Mail::assertSent(
            DiligenceMail::class,
            fn (DiligenceMail $mail) => $mail->diligenceMessage->is($message)
                && $mail->hasTo('agente@example.com')
        );
    }

    public function test_send_threads_reply_to_latest_previous_message(): void
    {
        Mail::fake();

        $monitoring = Monitoring::factory()->create();
        $sender = User::factory()->create();

        DiligenceMessage::factory()->create([
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $monitoring->id,
            'imap_message_id' => '<antiga@efomento>',
            'sent_at' => now()->subDays(2),
        ]);

        DiligenceMessage::factory()->create([
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $monitoring->id,
            'imap_message_id' => '<recente@efomento>',
            'sent_at' => now()->subDay(),
        ]);

        $message = $this->service->send(
            $monitoring,
            'Diligência: lembrete',
            'Reforçando a solicitação anterior.',
            'agente@example.com',
            $sender
        );

        $this->assertSame('<recente@efomento>', $message->in_reply_to);
    }

    public function test_sync_incoming_creates_inbound_reply(): void
    {
        $monitoring = Monitoring::factory()->create();

        $outbound = DiligenceMessage::factory()->create([
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $monitoring->id,
            'imap_message_id' => '<diligencia@efomento>',
        ]);

        $sentAt = now()->subHour()->startOfSecond();

        $this->mockImapInbox([
            $this->fakeImapMessage([
                'message_id' => '<resposta@example.com>',
                'in_reply_to' => '<diligencia@efomento>',
                'from' => 'agente@example.com',
                'subject' => 'Re: Diligência',
                'date' => $sentAt,
            ]),
        ]);

        $count = $this->service->syncIncoming();

        $this->assertSame(1, $count);

        $inbound = DiligenceMessage::where('imap_message_id', '<resposta@example.com>')->first();

        $this->assertNotNull($inbound);
        $this->assertEquals(DiligenceDirection::INBOUND, $inbound->direction);
        $this->assertSame('agente@example.com', $inbound->from_email);
        $this->assertSame(config('mail.from.address'), $inbound->to_email);
        $this->assertSame('Resposta do agente cultural.', $inbound->body);
        $this->assertSame('<diligencia@efomento>', $inbound->in_reply_to);
        $this->assertTrue($inbound->diligenceable->is($monitoring));
        $this->assertTrue($inbound->sent_at->equalTo($sentAt));
        $this->assertNotNull($outbound->fresh());
    }

    public function test_sync_incoming_skips_already_imported_messages(): void
    {
        DiligenceMessage::factory()->inbound()->create([
            'imap_message_id' => '<ja-importada@example.com>',
        ]);

        $this->mockImapInbox([
            $this->fakeImapMessage(['message_id' => '<ja-importada@example.com>']),
        ]);

        $count = $this->service->syncIncoming();

        $this->assertSame(0, $count);
        $this->assertSame(1, DiligenceMessage::where('imap_message_id', '<ja-importada@example.com>')->count());
    }

    public function test_sync_incoming_skips_messages_without_known_thread(): void
    {
        $this->mockImapInbox([
            $this->fakeImapMessage([
                'message_id' => '<avulsa@example.com>',
                'in_reply_to' => '<desconhecida@example.com>',
            ]),
        ]);

        $count = $this->service->syncIncoming();

        $this->assertSame(0, $count);
        $this->assertDatabaseMissing('diligence_messages', [
            'imap_message_id' => '<avulsa@example.com>',
        ]);
    }

    public function test_sync_incoming_falls_back_to_html_body(): void
    {
        $monitoring = Monitoring::factory()->create();

        DiligenceMessage::factory()->create([
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => $monitoring->id,
            'imap_message_id' => '<diligencia@efomento>',
        ]);

        $this->mockImapInbox([
            $this->fakeImapMessage([
                'message_id' => '<resposta-html@example.com>',
                'in_reply_to' => '<diligencia@efomento>',
                'hasText' => false,
                'bodies' => [
                    'html' => (object) ['content' => '<p>Resposta em <strong>HTML</strong>.</p>'],
                ],
            ]),
        ]);

        $count = $this->service->syncIncoming();

        $this->assertSame(1, $count);

        $inbound = DiligenceMessage::where('imap_message_id', '<resposta-html@example.com>')->first();

        $this->assertSame('Resposta em HTML.', $inbound->body);
    }

    public function test_sync_incoming_returns_zero_when_inbox_is_empty(): void
    {
        $this->mockImapInbox([]);

        $this->assertSame(0, $this->service->syncIncoming());
    }
}
