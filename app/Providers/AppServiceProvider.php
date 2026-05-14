<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\Project;
use App\Observers\NoticeObserver;
use App\Observers\ProjectObserver;
use App\Support\Notify;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(Notify::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Relation::morphMap([
            'agent' => Agent::class,
            'notice' => Notice::class,
            'project' => Project::class,
            'opening' => Opening::class,
        ]);

        Notice::observe(NoticeObserver::class);
        Project::observe(ProjectObserver::class);

        RateLimiter::for('mapas-api', function () {
            return Limit::perMinute(
                (int) config('efomento.mapas_rate_limit_per_minute', 60)
            )->by('mapas-api');
        });
    }
}
