<?php

namespace PlanParts\CrossMatcher;

use Illuminate\Support\ServiceProvider;
use PlanParts\CrossMatcher\Commands\ImportPriceListCommand;

class PlanPartsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/plan-parts.php',
            'plan-parts'
        );

        $this->app->singleton(PlanPartsClient::class, function ($app) {
            return new PlanPartsClient(
                token:   config('plan-parts.token'),
                baseUrl: config('plan-parts.base_url'),
                timeout: config('plan-parts.timeout'),
            );
        });

        $this->app->alias(PlanPartsClient::class, 'plan-parts');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/plan-parts.php' => config_path('plan-parts.php'),
            ], 'plan-parts-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'plan-parts-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views/' => resource_path('views/vendor/plan-parts'),
            ], 'plan-parts-views');

            $this->commands([
                ImportPriceListCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'plan-parts');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
