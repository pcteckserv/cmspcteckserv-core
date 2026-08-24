<?php

namespace Pcteckserv\CmsCore;

use Illuminate\Support\ServiceProvider;
use Pcteckserv\CmsCore\Console\CheckUpdatesCommand;
use Pcteckserv\CmsCore\Console\SyncVersionsCommand;

class CmsCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cms-core.php', 'cms-core');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cms-core');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/cms-core.php' => config_path('cms-core.php'),
        ], 'cms-core-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/cms-core'),
        ], 'cms-core-views');

        $this->publishes([
            __DIR__.'/../resources/images' => public_path('vendor/cms-core/images'),
        ], 'cms-core-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckUpdatesCommand::class,
                SyncVersionsCommand::class,
            ]);
        }
    }
}
