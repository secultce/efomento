<?php

namespace App\Mail;

use App\Models\DiligenceMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DiligenceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly DiligenceMessage $diligenceMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [config('efomento.diligence_reply_to', env('DILIGENCE_REPLY_TO'))],
            subject: $this->diligenceMessage->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.diligence',
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            messageId: $this->diligenceMessage->imap_message_id,
            references: $this->diligenceMessage->in_reply_to
                ? [$this->diligenceMessage->in_reply_to]
                : [],
        );
    }
}
