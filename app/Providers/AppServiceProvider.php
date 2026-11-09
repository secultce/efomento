<?php

namespace App\Providers;

use App\Contracts\InstallmentCycleStrategy;
use App\Enums\ProjectStageSlug;
use App\Models\Agent;
use App\Models\DiligenceMessage;
use App\Models\Monitoring;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\Project;
use App\Observers\NoticeObserver;
use App\Observers\ProjectObserver;
use App\Services\Diligenceable\MonitoringDiligenceableResolver;
use App\Services\DiligenceableResolverRegistry;
use App\Services\Strategies\StandardInstallmentCycleStrategy;
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
        $this->app->bind(Notify::class);

        $this->app->bind(InstallmentCycleStrategy::class, StandardInstallmentCycleStrategy::class);

        $this->app->singleton(DiligenceableResolverRegistry::class, fn () => new DiligenceableResolverRegistry([
            ProjectStageSlug::MONITORAMENTO->value => new MonitoringDiligenceableResolver,
        ]));
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
            'monitoring' => Monitoring::class,
            'diligence_message' => DiligenceMessage::class,
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
