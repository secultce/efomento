<?php

namespace App\Services;

use App\Enums\DiligenceDirection;
use App\Mail\DiligenceMail;
use App\Models\DiligenceMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Webklex\IMAP\Facades\Client;

class DiligenceMessageService
{
    public function send(Model $diligenceable, string $subject, string $body, string $toEmail, User $sender): DiligenceMessage
    {
        $replyTo = $diligenceable->diligenceMessages()->reorder()->latest('sent_at')->value('imap_message_id');

        $messageId = sprintf('<diligence_%s@%s>', Str::uuid(), $this->messageIdDomain());

        $message = $diligenceable->diligenceMessages()->create([
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => config('mail.from.address'),
            'to_email' => $toEmail,
            'subject' => $subject,
            'body' => $body,
            'imap_message_id' => $messageId,
            'in_reply_to' => $replyTo,
            'sent_at' => now(),
            'created_by' => $sender->id,
        ]);

        Mail::to($toEmail)->send(new DiligenceMail($message));

        return $message;
    }

    /**
     * O webklex/laravel-imap remove os colchetes angulares dos headers
     * Message-ID/In-Reply-To; o banco guarda no formato canônico <id@domínio>.
     */
    private function normalizeMessageId(string $value): ?string
    {
        $value = trim($value, " \t<>");

        return $value === '' ? null : '<'.$value.'>';
    }

    private function messageIdDomain(): string
    {
        $replyTo = (string) config('efomento.diligence_reply_to');

        return str_contains($replyTo, '@')
            ? substr(strrchr($replyTo, '@'), 1)
            : 'efomento.ce.gov.br';
    }

    public function syncIncoming(): int
    {
        $client = Client::account('default');
        $client->connect();

        try {
            $folder = $client->getFolderByName('INBOX');
            $messages = $folder->messages()->unseen()->get();

            $count = 0;

            foreach ($messages as $imapMessage) {
                $messageId = $this->normalizeMessageId((string) $imapMessage->message_id);
                Log::info('Processando mensagem IMAP', ['message_id' => $messageId]);

                if (DiligenceMessage::where('imap_message_id', $messageId)->exists()) {
                    continue;
                }

                $inReplyTo = $this->normalizeMessageId((string) $imapMessage->in_reply_to);
                $diligenceMessage = $inReplyTo
                    ? DiligenceMessage::where('imap_message_id', $inReplyTo)->first()
                    : null;

                if (! $diligenceMessage) {
                    continue; // Não deve existir mensagem de resposta sem existir uma mensagem de diligência
                }

                $diligenceMessage->diligenceable->diligenceMessages()->create([
                    'direction' => DiligenceDirection::INBOUND,
                    'from_email' => (string) $imapMessage->from,
                    'to_email' => config('mail.from.address'),
                    'subject' => (string) $imapMessage->subject,
                    'body' => $imapMessage->hasTextBody()
                        ? $imapMessage->getTextBody()
                        : strip_tags($imapMessage->getHTMLBody()),
                    'imap_message_id' => $messageId,
                    'in_reply_to' => $inReplyTo,
                    'sent_at' => $imapMessage->date->toDate(),
                ]);

                $count++;
            }
        } finally {
            $client->disconnect();
        }

        return $count;
    }
}
