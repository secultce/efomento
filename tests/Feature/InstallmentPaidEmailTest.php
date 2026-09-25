<?php

namespace Tests\Feature;

use App\Events\InstallmentPaidEvent;
use App\Exceptions\Integration\ExternalServiceException;
use App\Listeners\SendInstallmentPaidEmail;
use App\Mail\InstallmentPaidMail;
use App\Models\Agent;
use App\Models\AgentEmailLog;
use App\Models\Budget;
use App\Models\Installment;
use App\Models\ProfileSnapshot;
use App\Models\Project;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InstallmentPaidEmailTest extends TestCase
{
    use RefreshDatabase;

    private function installment(?string $email = 'agent@example.com'): Installment
    {
        $project = Project::factory()->create(['title_project' => 'Projeto Cultural']);
        $project->agent->update(['director_email' => null]);
        ProfileSnapshot::factory()->create([
            'object_id' => $project->agent_id, 'object_type' => 'agent', 'email' => $email,
        ]);

        return Installment::factory()->create([
            'budget_id' => Budget::factory()->create(['project_id' => $project->id])->id,
            'installment_number' => 1, 'payment_date' => '2026-09-01',
            'payment_amount' => 1000.50, 'process_number' => '12345/2026',
        ]);
    }

    public function test_sends_and_audits_only_once_with_relationships_and_content(): void
    {
        Mail::fake();
        $installment = $this->installment();
        $event = new InstallmentPaidEvent($installment->id);
        $listener = app(SendInstallmentPaidEmail::class);
        InstallmentPaidEvent::dispatch($installment->id);
        $listener->handle($event);

        Mail::assertSent(InstallmentPaidMail::class, 1);
        Mail::assertSent(InstallmentPaidMail::class, function ($mail) {
            $this->assertTrue($mail->hasTo('agent@example.com'));
            $mail->assertSeeInHtml('Projeto Cultural');
            $mail->assertSeeInHtml('12345/2026');
            $mail->assertSeeInHtml('01/09/2026');
            $mail->assertSeeInHtml('R$ 1.000,50');
            $this->assertSame(1, $mail->installmentNumber);

            return true;
        });
        $log = AgentEmailLog::sole();
        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertTrue($log->related->is($installment));
        $this->assertTrue($log->project->is($installment->budget->project));
        $this->assertTrue($log->agent->is($installment->budget->project->agent));
    }

    public function test_uses_director_email_when_snapshot_email_is_invalid(): void
    {
        Mail::fake();
        $installment = $this->installment('invalid');
        $installment->budget->project->agent->update(['director_email' => 'director@example.com']);
        app(SendInstallmentPaidEmail::class)->handle(new InstallmentPaidEvent($installment->id));
        Mail::assertSent(InstallmentPaidMail::class, fn ($mail) => $mail->hasTo('director@example.com'));
    }

    public function test_missing_email_is_audited_without_sending(): void
    {
        Mail::fake();
        $installment = $this->installment(null);
        app(SendInstallmentPaidEmail::class)->handle(new InstallmentPaidEvent($installment->id));
        Mail::assertNothingSent();
        $this->assertSame('failed', AgentEmailLog::sole()->status);
        $this->assertNotNull(AgentEmailLog::sole()->error_message);
        $this->assertNull(AgentEmailLog::sole()->sent_at);
    }

    public function test_repeated_failures_and_success_preserve_history_without_console_auditing(): void
    {
        config(['audit.console' => false]);
        $this->assertTrue(app()->runningInConsole());
        $installment = $this->installment();
        $mailer = Mail::getFacadeRoot();
        Mail::shouldReceive('to')->twice()->andReturnSelf();
        Mail::shouldReceive('send')->twice()->andReturnUsing(function () {
            $this->assertSame('queued', AgentEmailLog::sole()->status);
            throw new \RuntimeException('SMTP indisponível');
        });
        $listener = app(SendInstallmentPaidEmail::class);
        $event = new InstallmentPaidEvent($installment->id);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $listener->handle($event);
                $this->fail('Expected a mail transport failure');
            } catch (ExternalServiceException $e) {
                $this->assertSame('SMTP indisponível', $e->getPrevious()->getMessage());
            }
            $this->assertSame('failed', AgentEmailLog::sole()->status);
            $this->assertSame('SMTP indisponível', AgentEmailLog::sole()->error_message);
            $this->assertNull(AgentEmailLog::sole()->sent_at);
            $this->assertCount($attempt + 1, AgentEmailLog::sole()->audits()->get()
                ->filter(fn ($audit) => ($audit->new_values['status'] ?? null) === 'failed'));
        }
        Mail::swap($mailer);
        Mail::fake();
        $listener->handle($event);
        $log = AgentEmailLog::sole();
        $this->assertSame('sent', $log->status);
        $this->assertNull($log->error_message);
        $this->assertNotNull($log->sent_at);
        $audits = $log->audits()->orderBy('id')->get();
        $this->assertSame(['queued', 'failed', 'queued', 'failed', 'queued', 'sent'],
            $audits->map(fn ($audit) => $audit->new_values['status'])->all());
        foreach ([1, 3] as $index) {
            $this->assertSame('SMTP indisponível', $audits[$index]->new_values['error_message']);
            $this->assertSame('SMTP indisponível', $audits[$index + 1]->old_values['error_message']);
            $this->assertNull($audits[$index + 1]->new_values['error_message']);
        }
        $this->assertNotEmpty($audits->last()->new_values['sent_at']);
        $listener->handle($event);
        $this->assertSame($audits->count(), $log->audits()->count());
        Mail::assertSent(InstallmentPaidMail::class, 1);
    }

    public function test_agent_deletion_preserves_email_log_and_audits(): void
    {
        $agent = Agent::factory()->create();
        $log = AgentEmailLog::create([
            'agent_id' => $agent->id,
            'recipient_email' => 'agent@example.com',
            'recipient_name' => $agent->name,
            'mail_class' => InstallmentPaidMail::class,
            'event_type' => 'installment_paid',
            'subject' => InstallmentPaidMail::SUBJECT,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $auditIds = $log->audits()->pluck('id')->all();
        $this->assertNotEmpty($auditIds);

        $agent->delete();
        $this->assertTrue($log->fresh()->agent->is($agent));
        $agent->forceDelete();

        $log->refresh();
        $this->assertNull($log->agent_id);
        $this->assertNull($log->agent);
        $this->assertSame('agent@example.com', $log->recipient_email);
        $this->assertSame('sent', $log->status);
        $this->assertSame($auditIds, $log->audits()->pluck('id')->all());
    }

    public function test_event_queues_listener_only_after_commit_and_not_after_rollback(): void
    {
        Queue::fake();
        DB::beginTransaction();
        InstallmentPaidEvent::dispatch(123);
        Queue::assertNothingPushed();
        DB::rollBack();
        Queue::assertNothingPushed();

        DB::beginTransaction();
        InstallmentPaidEvent::dispatch(123);
        Queue::assertNothingPushed();
        DB::commit();
        Queue::assertPushedOn('default', CallQueuedListener::class, fn ($job) => $job->class === SendInstallmentPaidEmail::class);
    }

    public function test_deleted_installment_does_not_send(): void
    {
        Mail::fake();
        app(SendInstallmentPaidEmail::class)->handle(new InstallmentPaidEvent(99999));
        Mail::assertNothingSent();
        $this->assertDatabaseCount('agent_email_logs', 0);
    }
}
