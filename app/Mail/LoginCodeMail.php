<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LoginCodeMail extends Mailable implements ShouldBeEncrypted
{
    use Queueable;

    public readonly int $ttlMinutes;

    public function __construct(public readonly string $code)
    {
        $this->ttlMinutes = config('two_factor.code_ttl_minutes');
        $this->onQueue('high');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seu código de acesso ao e-fomento');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.login-code');
    }
}
