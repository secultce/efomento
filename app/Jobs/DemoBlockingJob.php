<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// SOMENTE PARA DEMONSTRAÇÃO — não usar em produção
class DemoBlockingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $items) {}

    public function handle(): void
    {
        Log::info('[DEMO] DemoBlockingJob iniciado', [
            'total' => count($this->items),
            'time' => now()->format('H:i:s.u'),
        ]);

        collect($this->items)
            ->chunk(5)
            ->each(function ($chunk, $chunkIndex) {
                foreach ($chunk as $item) {
                    DemoFastJob::dispatch("chunk-{$chunkIndex}-item-{$item}")
                        ->onQueue('demo');
                }

                Log::warning('[DEMO] >>> BLOQUEANDO worker com usleep (2s)...', [
                    'chunk' => $chunkIndex,
                    'time' => now()->format('H:i:s.u'),
                ]);

                usleep(2_000_000); // 2s exagerado para tornar o bloqueio visível no log

                Log::warning('[DEMO] <<< Worker liberado', [
                    'chunk' => $chunkIndex,
                    'time' => now()->format('H:i:s.u'),
                ]);
            });

        Log::info('[DEMO] DemoBlockingJob finalizado', [
            'time' => now()->format('H:i:s.u'),
        ]);
    }
}
