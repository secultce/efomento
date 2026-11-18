<?php

namespace App\Listeners;

use App\Events\InstallmentPaidEvent;
use App\Exceptions\Integration\ExternalServiceException;
use App\Mail\InstallmentPaidMail;
use App\Models\AgentEmailLog;
use App\Models\Installment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendInstallmentPaidEmail implements ShouldQueue
{
    public string $queue = 'default';

    public int $tries = 3;

    public int $backoff = 60;

    public function handle(InstallmentPaidEvent $event): void
    {
        $installment = Installment::with('budget.project.agent.latestSnapshot', 'budget.project.opening')
            ->find($event->installmentId);
        $project = $installment?->budget?->project;
        $agent = $project?->agent;

        if (! $agent) {
            Log::warning('installment.email.agent_missing', ['installment_id' => $event->installmentId]);

            return;
        }

        $email = collect([$agent->latestSnapshot?->email, $agent->director_email])
            ->map(fn ($value) => trim((string) $value))
            ->first(fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL)) ?? '';

        $log = AgentEmailLog::firstOrCreate(
            ['deduplication_key' => 'installment_paid:'.$installment->id],
            [
                'agent_id' => $agent->id,
                'project_id' => $project->id,
                'related_type' => $installment->getMorphClass(),
                'related_id' => $installment->id,
                'recipient_email' => $email,
                'recipient_name' => $agent->name,
                'mail_class' => InstallmentPaidMail::class,
                'event_type' => 'installment_paid',
                'subject' => InstallmentPaidMail::SUBJECT,
                'status' => 'queued',
            ],
        );

        $failure = DB::transaction(function () use ($log, $installment, $project): ?Throwable {
            $log = AgentEmailLog::lockForUpdate()->findOrFail($log->id);
            if ($log->status === 'sent') {
                return null;
            }

            if ($log->recipient_email === '') {
                $log->update(['status' => 'failed', 'error_message' => 'Agente sem e-mail válido no snapshot ou no cadastro.']);

                return null;
            }

            $log->update(['status' => 'queued', 'error_message' => null]);
            try {
                Mail::to($log->recipient_email, $log->recipient_name)->send(new InstallmentPaidMail(
                    recipientName: $log->recipient_name ?? '',
                    processNumber: $installment->process_number ?? $project->opening?->opening_nup ?? 'Não informado',
                    projectTitle: $project->title_project ?? '',
                    installmentNumber: $installment->installment_number,
                    paymentDate: $installment->payment_date?->format('d/m/Y') ?? 'Não informada',
                    paymentAmount: number_format((float) $installment->payment_amount, 2, ',', '.'),
                ));
            } catch (Throwable $e) {
                $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

                // Commit the attempt history before rethrowing for the queue retry.
                return $e;
            }

            $log->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

            return null;
        });

        if ($failure) {
            throw ExternalServiceException::unavailable('Envio de e-mail de pagamento', $failure);
        }
    }
}
