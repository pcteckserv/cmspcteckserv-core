<?php

namespace Pcteckserv\CmsCore;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Pcteckserv\CmsCore\Console\BackupStatusCommand;
use Pcteckserv\CmsCore\Console\CheckUpdatesCommand;
use Pcteckserv\CmsCore\Console\CleanupBackupsCommand;
use Pcteckserv\CmsCore\Console\CreateBackupCommand;
use Pcteckserv\CmsCore\Console\OptimizeMediaCommand;
use Pcteckserv\CmsCore\Console\RunDueBackupsCommand;
use Pcteckserv\CmsCore\Console\SyncVersionsCommand;
use Pcteckserv\CmsCore\Contracts\MediaUrlGenerator;
use Pcteckserv\CmsCore\Services\Media\StorageMediaUrlGenerator;
use Pcteckserv\CmsCore\Support\SiteOptions;

class CmsCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cms-core.php', 'cms-core');
        $this->mergeConfigFrom(__DIR__.'/../config/cms-backups.php', 'cms-backups');
        $this->app->bind(MediaUrlGenerator::class, StorageMediaUrlGenerator::class);
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
            __DIR__.'/../config/cms-backups.php' => config_path('cms-backups.php'),
        ], 'cms-backups-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/cms-core'),
        ], 'cms-core-views');

        $this->publishes([
            __DIR__.'/../resources/images' => public_path('vendor/cms-core/images'),
        ], 'cms-core-assets');

        $this->app->make(SiteOptions::class)->applyMailConfig();
        $this->registerBackupGates();
        $this->registerMediaGates();
        $this->registerBackupRateLimiters();
        $this->registerMediaRateLimiters();

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackupStatusCommand::class,
                CheckUpdatesCommand::class,
                CleanupBackupsCommand::class,
                CreateBackupCommand::class,
                OptimizeMediaCommand::class,
                RunDueBackupsCommand::class,
                SyncVersionsCommand::class,
            ]);

            Schedule::command('cms:backup:run-due')->everyMinute()->withoutOverlapping();
            Schedule::command('cms:backup:cleanup')->dailyAt('03:30')->withoutOverlapping();
        }
    }

    private function registerBackupGates(): void
    {
        foreach ([
            'backups.view',
            'backups.configure',
            'backups.run',
            'backups.download',
            'backups.delete',
            'backups.restore',
            'backups.verify',
            'backups.manage-notifications',
        ] as $permission) {
            Gate::define($permission, function ($user) use ($permission): bool {
                if (method_exists($user, 'hasCmsPermission')) {
                    return $user->hasCmsPermission($permission);
                }

                if (method_exists($user, 'canAccessCms')) {
                    return $user->canAccessCms();
                }

                return true;
            });
        }
    }

    private function registerMediaGates(): void
    {
        foreach (config('cms-core.media.permissions', []) as $permission) {
            Gate::define($permission, function ($user) use ($permission): bool {
                if (method_exists($user, 'hasCmsPermission')) {
                    return $user->hasCmsPermission($permission);
                }

                if (method_exists($user, 'canAccessCms') && ! $user->canAccessCms()) {
                    return false;
                }

                return true;
            });
        }
    }

    private function registerMediaRateLimiters(): void
    {
        RateLimiter::for('media-upload', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }

    private function registerBackupRateLimiters(): void
    {
        RateLimiter::for('backups', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }
}
