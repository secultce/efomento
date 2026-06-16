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
            'subject' => 'Diligência — Monitoramento',
            'body' => 'Corpo da mensagem de diligência.',
            'imap_message_id' => '<diligence_abc123@efomento.ce.gov.br>',
            'in_reply_to' => null,
        ], $attributes));
    }

    public function test_headers_strip_angle_brackets_from_message_id(): void
    {
        $headers = (new DiligenceMail($this->makeMessage()))->headers();

        $this->assertSame('diligence_abc123@efomento.ce.gov.br', $headers->messageId);
    }

    public function test_headers_reference_the_replied_message(): void
    {
        $mail = new DiligenceMail($this->makeMessage([
            'in_reply_to' => '<resposta_agente@example.com>',
        ]));

        $this->assertSame(['<resposta_agente@example.com>'], $mail->headers()->references);
    }

    public function test_headers_have_no_references_for_the_first_message_of_the_thread(): void
    {
        $this->assertSame([], (new DiligenceMail($this->makeMessage()))->headers()->references);
    }

    public function test_envelope_uses_subject_and_reply_to_from_config(): void
    {
        config(['efomento.diligence_reply_to' => 'diligencias@example.com']);

        $envelope = (new DiligenceMail($this->makeMessage()))->envelope();

        $this->assertSame('Diligência — Monitoramento', $envelope->subject);
        $this->assertTrue($envelope->hasReplyTo('diligencias@example.com'));
    }

    public function test_content_uses_diligence_view(): void
    {
        $content = (new DiligenceMail($this->makeMessage()))->content();

        $this->assertSame('mail.diligence', $content->view);
    }

    public function test_mailable_renders_with_message_body(): void
    {
        config(['efomento.diligence_reply_to' => 'diligencias@example.com']);

        $mail = new DiligenceMail($this->makeMessage());

        $mail->assertSeeInHtml('Corpo da mensagem de diligência.');
    }
}
