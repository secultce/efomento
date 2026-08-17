<?php

use App\Jobs\SyncMonitoringJob;
use App\Jobs\SyncNoticesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncNoticesJob)
    ->dailyAt('09:06')
    ->timezone('America/Fortaleza')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new SyncMonitoringJob, 'high')
    ->dailyAt('11:51')
    ->timezone('America/Fortaleza')
    ->withoutOverlapping(60)
    ->onOneServer();

Schedule::command('app:sync-diligence-emails')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('queue:prune-batches --hours=72 --unfinished=168 --cancelled=168')
    ->dailyAt('03:30')
    ->timezone('America/Fortaleza')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('03:40')
    ->timezone('America/Fortaleza')
    ->withoutOverlapping()
    ->onOneServer();
