<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRegistrationBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $registrations) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('sync.batch.processing', ['count' => count($this->registrations)]);

        collect($this->registrations)
            ->chunk(10)
            ->each(function ($chunk) {
                foreach ($chunk as $registration) {
                    SyncRegistrationDetailsJob::dispatch($registration)
                        ->delay(now()->addMilliseconds(rand(100, 500)))
                        ->onQueue('details');
                }

                usleep(200000); // 200ms entre blocos
            });
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
