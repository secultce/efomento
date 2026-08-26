<?php

namespace App\Services;

use App\Enums\DiligenceDirection;
use App\Mail\DiligenceMail;
use App\Models\DiligenceMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Webklex\IMAP\Facades\Client;

class DiligenceMessageService
{
    public function send(
        Model $diligenceable,
        string $subject,
        string $body,
        string $toEmail,
        User $sender,
        ?string $ccEmail = null,
    ): DiligenceMessage {
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

        $pendingMail = Mail::to($toEmail);
        $ccEmail = $this->validCcEmail($ccEmail, $toEmail);

        if ($ccEmail) {
            $pendingMail->cc($ccEmail);
        }

        $pendingMail->send(new DiligenceMail($message));

        return $message;
    }

    private function validCcEmail(?string $ccEmail, string $toEmail): ?string
    {
        $ccEmail = trim((string) $ccEmail);

        if (
            ! filter_var($ccEmail, FILTER_VALIDATE_EMAIL)
            || strcasecmp($ccEmail, $toEmail) === 0
        ) {
            return null;
        }

        return $ccEmail;
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

                $existingMessage = DiligenceMessage::where('imap_message_id', $messageId)->first();

                if ($existingMessage) {
                    if ($existingMessage->direction === DiligenceDirection::INBOUND) {
                        $normalizedSentAt = $this->normalizeImapSentAt($imapMessage);

                        if (! $existingMessage->sent_at->equalTo($normalizedSentAt)) {
                            $existingMessage->update(['sent_at' => $normalizedSentAt]);
                        }

                        if (! $existingMessage->attachments()->exists()) {
                            $this->persistAttachmentsForExistingMessage($existingMessage, $imapMessage);
                        }
                    }

                    continue;
                }

                $inReplyTo = $this->normalizeMessageId((string) $imapMessage->in_reply_to);
                $diligenceMessage = $inReplyTo
                    ? DiligenceMessage::where('imap_message_id', $inReplyTo)->first()
                    : null;

                if (! $diligenceMessage) {
                    continue; // Não deve existir mensagem de resposta sem existir uma mensagem de diligência
                }

                $this->persistIncomingMessage($diligenceMessage, $imapMessage, $messageId, $inReplyTo);

                $count++;
            }
        } finally {
            $client->disconnect();
        }

        return $count;
    }

    private function persistIncomingMessage(
        DiligenceMessage $threadMessage,
        object $imapMessage,
        ?string $messageId,
        ?string $inReplyTo,
    ): void {
        $storedPaths = [];

        try {
            DB::transaction(function () use ($threadMessage, $imapMessage, $messageId, $inReplyTo, &$storedPaths) {
                $message = $threadMessage->diligenceable->diligenceMessages()->create([
                    'direction' => DiligenceDirection::INBOUND,
                    'from_email' => (string) $imapMessage->from,
                    'to_email' => config('mail.from.address'),
                    'subject' => (string) $imapMessage->subject,
                    'body' => $imapMessage->hasTextBody()
                        ? $imapMessage->getTextBody()
                        : strip_tags($imapMessage->getHTMLBody()),
                    'imap_message_id' => $messageId,
                    'in_reply_to' => $inReplyTo,
                    'sent_at' => $this->normalizeImapSentAt($imapMessage),
                ]);

                $this->storeAttachments($message, $imapMessage, $storedPaths);
            });
        } catch (Throwable $exception) {
            Storage::disk(config('efomento.file_disk', 'public'))->delete($storedPaths);

            throw $exception;
        }
    }

    private function persistAttachmentsForExistingMessage(DiligenceMessage $message, object $imapMessage): void
    {
        $storedPaths = [];

        try {
            DB::transaction(function () use ($message, $imapMessage, &$storedPaths) {
                $this->storeAttachments($message, $imapMessage, $storedPaths);
            });
        } catch (Throwable $exception) {
            Storage::disk(config('efomento.file_disk', 'public'))->delete($storedPaths);

            throw $exception;
        }
    }

    private function normalizeImapSentAt(object $imapMessage): \DateTimeInterface
    {
        $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));

        return $imapMessage->date->toDate()->setTimezone($timezone);
    }

    private function storeAttachments(DiligenceMessage $message, object $imapMessage, array &$storedPaths): void
    {
        $disk = Storage::disk(config('efomento.file_disk', 'public'));
        $position = 0;

        foreach ($imapMessage->getAttachments() as $attachment) {
            if (Str::lower((string) $attachment->disposition) === 'inline') {
                continue;
            }

            $content = $attachment->getContent();
            $name = trim((string) ($attachment->name ?: $attachment->filename)) ?: 'anexo';
            $baseExternalId = trim((string) ($attachment->id ?: $attachment->hash)) ?: hash('sha256', $content);
            $externalId = Str::limit($baseExternalId, 240, '').':'.$position;
            $path = "diligence-messages/{$message->id}/".Str::uuid();

            if (! $disk->put($path, $content)) {
                throw new RuntimeException("Falha ao salvar anexo de e-mail em disco: {$name}");
            }

            $storedPaths[] = $path;

            $message->attachments()->create([
                'mime_type' => (string) ($attachment->content_type ?: 'application/octet-stream'),
                'name' => Str::limit(basename($name), 255, ''),
                'source' => 'imap',
                'external_id' => $externalId,
                'grp' => 'attachments',
                'path' => $path,
                'private' => true,
            ]);

            $position++;
        }
    }
}
