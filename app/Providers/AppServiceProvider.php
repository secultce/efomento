<?php

namespace App\Providers;

use App\Models\Notice;
use App\Models\Opening;
use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Observers\NoticeObserver;
use App\Support\Notify;

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
            'notice'  => Notice::class,
            'project' => Project::class,
            'opening' => Opening::class,
        ]);

        Notice::observe(NoticeObserver::class);
    }
}
