<?php

namespace DeadSimpleApps\TreeSizeMailer;

use DeadSimpleApps\TreeSizeMailer\Commands\DiskReportCommand;
use Illuminate\Support\ServiceProvider;

class TreeSizeMailerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/tree-size-mailer.php',
            'tree-size-mailer'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                DiskReportCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__.'/../config/tree-size-mailer.php' => config_path('tree-size-mailer.php'),
            ], 'tree-size-mailer-config');
        }

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tree-size-mailer');

        // Publish views (optional, users can override)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/tree-size-mailer'),
        ], 'tree-size-mailer-views');
    }
}
