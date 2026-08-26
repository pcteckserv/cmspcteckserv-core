<?php

namespace Pcteckserv\CmsCore;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
use Pcteckserv\CmsCore\Console\SyncPermissionsCommand;
use Pcteckserv\CmsCore\Console\SyncVersionsCommand;
use Pcteckserv\CmsCore\Contracts\CmsAccessUser;
use Pcteckserv\CmsCore\Contracts\MediaUrlGenerator;
use Pcteckserv\CmsCore\Services\Media\StorageMediaUrlGenerator;
use Pcteckserv\CmsCore\Support\Permissions\PermissionRegistry;
use Pcteckserv\CmsCore\Services\UserModelResolver;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Pcteckserv\CmsCore\View\Components\CmsFooter;

class CmsCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cms-core.php', 'cms-core');
        $this->mergeConfigFrom(__DIR__.'/../config/cms-backups.php', 'cms-backups');
        $this->app->singleton(PermissionRegistry::class);
        $this->app->bind(MediaUrlGenerator::class, StorageMediaUrlGenerator::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cms-core');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Blade::component(CmsFooter::class, 'cms-footer');

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
        Route::bind('user', fn ($value) => app(UserModelResolver::class)->className()::query()->findOrFail($value));
        $this->registerCorePermissions();
        $this->registerGates();
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
                SyncPermissionsCommand::class,
                SyncVersionsCommand::class,
            ]);

            Schedule::command('cms:backup:run-due')->everyMinute()->withoutOverlapping();
            Schedule::command('cms:backup:cleanup')->dailyAt('03:30')->withoutOverlapping();
        }
    }

    private function registerCorePermissions(): void
    {
        $this->app->make(PermissionRegistry::class)->register([
            'core.users.view' => ['label' => 'Ver utilizadores', 'group' => 'Utilizadores'],
            'core.users.create' => ['label' => 'Criar utilizadores', 'group' => 'Utilizadores'],
            'core.users.update' => ['label' => 'Editar utilizadores', 'group' => 'Utilizadores'],
            'core.users.delete' => ['label' => 'Eliminar utilizadores', 'group' => 'Utilizadores'],
            'core.users.manage_roles' => ['label' => 'Gerir roles de utilizadores', 'group' => 'Utilizadores'],
            'core.roles.view' => ['label' => 'Ver roles', 'group' => 'Roles'],
            'core.roles.create' => ['label' => 'Criar roles', 'group' => 'Roles'],
            'core.roles.update' => ['label' => 'Editar roles', 'group' => 'Roles'],
            'core.roles.delete' => ['label' => 'Eliminar roles', 'group' => 'Roles'],
            'core.site-options.view' => ['label' => 'Ver opções gerais', 'group' => 'Opções gerais'],
            'core.site-options.update' => ['label' => 'Editar opções gerais', 'group' => 'Opções gerais'],
            'footer.view-settings' => ['label' => 'Ver configuração do footer', 'group' => 'Footer'],
            'footer.update-settings' => ['label' => 'Editar configuração do footer', 'group' => 'Footer'],
            'backups.view' => ['label' => 'Ver backups', 'group' => 'Backups'],
            'backups.configure' => ['label' => 'Configurar backups', 'group' => 'Backups'],
            'backups.run' => ['label' => 'Executar backups', 'group' => 'Backups'],
            'backups.download' => ['label' => 'Descarregar backups', 'group' => 'Backups'],
            'backups.delete' => ['label' => 'Eliminar backups', 'group' => 'Backups'],
            'backups.restore' => ['label' => 'Restaurar backups', 'group' => 'Backups'],
            'backups.verify' => ['label' => 'Verificar backups', 'group' => 'Backups'],
            'backups.manage-notifications' => ['label' => 'Gerir alertas de backup', 'group' => 'Backups'],
            'media.view' => ['label' => 'Ver media', 'group' => 'Media'],
            'media.upload' => ['label' => 'Enviar media', 'group' => 'Media'],
            'media.edit' => ['label' => 'Editar media', 'group' => 'Media'],
            'media.delete' => ['label' => 'Eliminar media', 'group' => 'Media'],
            'media.restore' => ['label' => 'Restaurar media', 'group' => 'Media'],
            'media.force-delete' => ['label' => 'Eliminar media definitivamente', 'group' => 'Media'],
            'media.optimize' => ['label' => 'Optimizar media', 'group' => 'Media'],
        ]);
    }

    private function registerGates(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            if ($user instanceof CmsAccessUser && $user->isCmsSuperAdmin()) {
                return true;
            }

            if ($user instanceof CmsAccessUser && $this->app->make(PermissionRegistry::class)->has($ability)) {
                return $user->hasCmsPermission($ability);
            }

            return null;
        });

        foreach ($this->app->make(PermissionRegistry::class)->all() as $permission) {
            Gate::define(
                $permission->key,
                fn ($user): bool => $user instanceof CmsAccessUser && $user->hasCmsPermission($permission->key),
            );
        }
    }

    private function registerBackupRateLimiters(): void
    {
        RateLimiter::for('backups', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }

    private function registerMediaRateLimiters(): void
    {
        RateLimiter::for('media-upload', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }
}
