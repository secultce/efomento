<?php

namespace Tests\Unit;

use App\Mail\DiligenceMail;
use App\Models\DiligenceMessage;
use Tests\TestCase;

class DiligenceMailTest extends TestCase
{
    private function makeMessage(array $attributes = []): DiligenceMessage
    {
        return new DiligenceMessage(array_merge([
            'subject' => 'Diligência sobre o projeto',
            'body' => 'Corpo da mensagem de diligência.',
            'imap_message_id' => '<diligence_abc123@efomento>',
            'in_reply_to' => null,
        ], $attributes));
    }

    public function test_envelope_uses_message_subject_and_configured_reply_to(): void
    {
        config(['efomento.diligence_reply_to' => 'diligencias@example.com']);

        $envelope = (new DiligenceMail($this->makeMessage()))->envelope();

        $this->assertSame('Diligência sobre o projeto', $envelope->subject);
        $this->assertSame('diligencias@example.com', $envelope->replyTo[0]->address);
    }

    public function test_content_uses_diligence_view(): void
    {
        $content = (new DiligenceMail($this->makeMessage()))->content();

        $this->assertSame('mail.diligence', $content->view);
    }

    public function test_headers_use_imap_message_id(): void
    {
        $headers = (new DiligenceMail($this->makeMessage()))->headers();

        $this->assertSame('<diligence_abc123@efomento>', $headers->messageId);
        $this->assertSame([], $headers->references);
    }

    public function test_headers_include_reference_when_replying_to_previous_message(): void
    {
        $mail = new DiligenceMail($this->makeMessage([
            'in_reply_to' => '<diligence_previous@efomento>',
        ]));

        $this->assertSame(['<diligence_previous@efomento>'], $mail->headers()->references);
    }

    public function test_mailable_renders_with_message_body(): void
    {
        config(['efomento.diligence_reply_to' => 'diligencias@example.com']);

        $mail = new DiligenceMail($this->makeMessage());

        $mail->assertHasSubject('Diligência sobre o projeto');
    }
}
