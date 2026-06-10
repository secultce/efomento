<?php

namespace App\Services;

use App\Enums\DiligenceDirection;
use App\Mail\DiligenceMail;
use App\Models\DiligenceMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webklex\IMAP\Facades\Client;

class DiligenceMessageService
{
    public function send(Model $diligenceable, string $subject, string $body, string $toEmail, User $sender): DiligenceMessage
    {
        $replyTo = $diligenceable->diligenceMessages()->latest('sent_at')->value('imap_message_id');

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
                $messageId = (string) $imapMessage->message_id;
                Log::info('Processando mensagem IMAP', ['message_id' => $messageId]);

                if (DiligenceMessage::where('imap_message_id', $messageId)->exists()) {
                    continue;
                }

                $inReplyTo = (string) $imapMessage->in_reply_to;
                $diligenceMessage = DiligenceMessage::where('imap_message_id', $inReplyTo)->first();

                if (! $diligenceMessage) {
                    continue; // Não deve existir mensagem de resposta sem existir uma mensagem de diligência
                }

                $diligenceMessage->diligenceable->diligenceMessages()->create([
                    'direction' => DiligenceDirection::INBOUND,
                    'from_email' => (string) $imapMessage->from,
                    'to_email' => config('mail.from.address'),
                    'subject' => (string) $imapMessage->subject,
                    'body' => $imapMessage->hasTextBody()
                        ? $imapMessage->bodies['text']->content
                        : strip_tags($imapMessage->bodies['html']->content),
                    'imap_message_id' => $messageId,
                    'in_reply_to' => $inReplyTo,
                    'sent_at' => $imapMessage->date->toDateTime(),
                ]);

                $count++;
            }
        } finally {
            $client->disconnect();
        }

        return $count;
    }
}
