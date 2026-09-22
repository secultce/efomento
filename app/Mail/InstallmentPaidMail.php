<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InstallmentPaidMail extends Mailable
{
    public const SUBJECT = 'Pagamento de parcela confirmado — e-fomento';

    public function __construct(
        public readonly string $recipientName,
        public readonly string $processNumber,
        public readonly string $projectTitle,
        public readonly int $installmentNumber,
        public readonly string $paymentDate,
        public readonly string $paymentAmount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECT);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.installment-paid');
    }
}
