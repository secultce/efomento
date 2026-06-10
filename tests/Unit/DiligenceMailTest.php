<?php

namespace Tests\Unit;

use App\Mail\DiligenceMail;
use App\Models\DiligenceMessage;
use Tests\TestCase;

class DiligenceMailTest extends TestCase
{
    public function test_headers_strip_angle_brackets_from_message_id(): void
    {
        $mail = new DiligenceMail(new DiligenceMessage([
            'subject' => 'Diligência — Monitoramento',
            'imap_message_id' => '<diligence_abc123@efomento.ce.gov.br>',
        ]));

        $headers = $mail->headers();

        $this->assertSame('diligence_abc123@efomento.ce.gov.br', $headers->messageId);
    }

    public function test_headers_reference_the_replied_message(): void
    {
        $mail = new DiligenceMail(new DiligenceMessage([
            'subject' => 'Diligência — Monitoramento',
            'imap_message_id' => '<diligence_abc123@efomento.ce.gov.br>',
            'in_reply_to' => '<resposta_agente@example.com>',
        ]));

        $this->assertSame(['<resposta_agente@example.com>'], $mail->headers()->references);
    }

    public function test_headers_have_no_references_for_the_first_message_of_the_thread(): void
    {
        $mail = new DiligenceMail(new DiligenceMessage([
            'subject' => 'Diligência — Monitoramento',
            'imap_message_id' => '<diligence_abc123@efomento.ce.gov.br>',
        ]));

        $this->assertSame([], $mail->headers()->references);
    }

    public function test_envelope_uses_subject_and_reply_to_from_config(): void
    {
        config(['efomento.diligence_reply_to' => 'diligencias@example.com']);

        $mail = new DiligenceMail(new DiligenceMessage([
            'subject' => 'Diligência — Monitoramento',
            'imap_message_id' => '<diligence_abc123@efomento.ce.gov.br>',
        ]));

        $envelope = $mail->envelope();

        $this->assertSame('Diligência — Monitoramento', $envelope->subject);
        $this->assertTrue($envelope->hasReplyTo('diligencias@example.com'));
    }
}
