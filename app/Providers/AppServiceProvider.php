<?php

namespace App\Providers;

use App\Contracts\Students\LevelProgression;
use App\Services\Students\Promotion\StandardLevelProgression;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Promotion depends on the contract; swap the rule here if the ladder changes.
        $this->app->bind(
            LevelProgression::class,
            StandardLevelProgression::class,
        );

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
