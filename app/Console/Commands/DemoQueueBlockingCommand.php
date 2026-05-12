<?php

namespace App\Console\Commands;

use App\Jobs\DemoBlockingJob;
use App\Jobs\DemoFastJob;
use Illuminate\Console\Command;

// SOMENTE PARA DEMONSTRAÇÃO — não usar em produção
class DemoQueueBlockingCommand extends Command
{
    protected $signature = 'demo:queue-blocking';

    protected $description = '[DEMO] Demonstra bloqueio de worker causado por usleep em jobs de fila';

    public function handle(): void
    {
        $this->line('1. Despachando <comment>DemoBlockingJob</comment> (10 itens, usleep entre chunks)...');
        DemoBlockingJob::dispatch(range(1, 10))->onQueue('demo');

        $this->line('2. Despachando <comment>5 DemoFastJob independentes</comment> (deveriam ser rápidos)...');
        for ($i = 1; $i <= 5; $i++) {
            DemoFastJob::dispatch("independente-{$i}")->onQueue('demo');
        }

        $this->newLine();
        $this->info('Jobs despachados! Em outro terminal, rode:');
        $this->line('  docker compose exec app php artisan queue:work --queue=demo --verbose');
        $this->newLine();
        $this->line('Em um terceiro terminal, acompanhe o log em tempo real:');
        $this->line('  docker compose exec app tail -f storage/logs/laravel.log | grep DEMO');
        $this->newLine();
        $this->comment('Observe: os jobs "independente-N" só executam APÓS o DemoBlockingJob');
        $this->comment('terminar completamente — mesmo sendo jobs rápidos e independentes.');
    }
}
