<?php

namespace Tests\Feature;

use App\Services\DiligenceMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SyncDiligenceEmailsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_succeeds_and_outputs_count(): void
    {
        $this->mock(DiligenceMessageService::class)
            ->shouldReceive('syncIncoming')
            ->once()
            ->andReturn(3);

        $this->artisan('app:sync-diligence-emails')
            ->expectsOutputToContain('3 mensagem')
            ->assertSuccessful();
    }

    public function test_command_fails_when_service_throws(): void
    {
        $this->mock(DiligenceMessageService::class)
            ->shouldReceive('syncIncoming')
            ->once()
            ->andThrow(new RuntimeException('IMAP connection refused'));

        $this->artisan('app:sync-diligence-emails')
            ->expectsOutputToContain('IMAP connection refused')
            ->assertFailed();
    }

    public function test_command_outputs_zero_when_no_new_messages(): void
    {
        $this->mock(DiligenceMessageService::class)
            ->shouldReceive('syncIncoming')
            ->once()
            ->andReturn(0);

        $this->artisan('app:sync-diligence-emails')
            ->expectsOutputToContain('0 mensagem')
            ->assertSuccessful();
    }
}
