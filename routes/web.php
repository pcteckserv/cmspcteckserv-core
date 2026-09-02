<?php

use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Http\Controllers\Admin\ActivityLogController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\ArtisanCommandsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\BackupsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\Consent\ConsentCategoriesController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\Consent\ConsentDashboardController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\Consent\ConsentScansController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\Consent\ConsentServicesController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\Consent\ConsentSettingsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\DashboardController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\FooterSettingsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\MaintenanceSettingsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\MediaController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\Queues\QueueDashboardController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\RolesController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\SiteOptionsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\SmtpSettingsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\UpdatesController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\UsersController;
use Pcteckserv\CmsCore\Http\Controllers\Auth\AuthenticatedSessionController;
use Pcteckserv\CmsCore\Http\Controllers\Consent\ConsentRecordController;
use Pcteckserv\CmsCore\Http\Controllers\MaintenanceAccessController;
use Pcteckserv\CmsCore\Seo\Http\Controllers\RobotsTxtController;
use Pcteckserv\CmsCore\Seo\Http\Controllers\SeoAuditController;
use Pcteckserv\CmsCore\Seo\Http\Controllers\SeoDashboardController;
use Pcteckserv\CmsCore\Seo\Http\Controllers\SeoNotFoundController;
use Pcteckserv\CmsCore\Seo\Http\Controllers\SeoRedirectsController;
use Pcteckserv\CmsCore\Seo\Http\Controllers\SeoSettingsController;
use Pcteckserv\CmsCore\Seo\Http\Controllers\SitemapController;

Route::middleware('web')->group(function (): void {
    Route::get('/favicon.ico', function () {
        $path = __DIR__.'/../resources/images/favicon.png';

        abort_unless(is_file($path), 404);

        return response()
            ->file($path, ['Content-Type' => 'image/png'])
            ->setMaxAge(604800)
            ->setPublic();
    })->name('cms-core.favicon');

    Route::get('/vendor/cms-core/images/{file}', function (string $file) {
        abort_unless(in_array($file, [
            'favicon.png',
            'logotipos-pcteckserv-texto.svg',
        ], true), 404);

        $path = __DIR__.'/../resources/images/'.$file;

        abort_unless(is_file($path), 404);

        return response()
            ->file($path)
            ->setMaxAge(604800)
            ->setPublic();
    })->where('file', '[A-Za-z0-9._-]+')->name('cms-core.images.show');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');

    Route::middleware('guest')->group(function (): void {
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::post('/maintenance/access', [MaintenanceAccessController::class, 'store'])
        ->middleware('throttle:maintenance-access')
        ->name('maintenance.access');

    Route::post('/consent/records', [ConsentRecordController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('consent.records.store');

    Route::get('/sitemap.xml', SitemapController::class)->name('seo.sitemap');
    Route::get('/robots.txt', RobotsTxtController::class)->name('seo.robots');

    Route::middleware('auth')
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('/site-options', [SiteOptionsController::class, 'edit'])->name('site-options.edit');
            Route::put('/site-options', [SiteOptionsController::class, 'update'])->name('site-options.update');
            Route::get('/footer', [FooterSettingsController::class, 'edit'])->name('footer.edit');
            Route::put('/footer', [FooterSettingsController::class, 'update'])->name('footer.update');
            Route::get('/maintenance', [MaintenanceSettingsController::class, 'edit'])->name('maintenance.edit');
            Route::put('/maintenance', [MaintenanceSettingsController::class, 'update'])->name('maintenance.update');
            Route::get('/maintenance/preview', [MaintenanceSettingsController::class, 'preview'])->name('maintenance.preview');
            Route::put('/maintenance/revoke-access', [MaintenanceSettingsController::class, 'revoke'])->name('maintenance.revoke-access');
            Route::post('/maintenance/disable', [MaintenanceSettingsController::class, 'disable'])->name('maintenance.disable');
            Route::get('/smtp-settings', [SmtpSettingsController::class, 'edit'])->name('smtp-settings.edit');
            Route::put('/smtp-settings', [SmtpSettingsController::class, 'update'])->name('smtp-settings.update');
            Route::post('/smtp-settings/test', [SmtpSettingsController::class, 'test'])->name('smtp-settings.test');
            Route::get('/laravel-commands', [ArtisanCommandsController::class, 'index'])->name('laravel-commands.index');
            Route::post('/laravel-commands/{command}/run', [ArtisanCommandsController::class, 'run'])->name('laravel-commands.run');
            Route::get('/queues', QueueDashboardController::class)->name('queues.dashboard');
            Route::post('/queues/work-once', [QueueDashboardController::class, 'workOnce'])->name('queues.work-once');
            Route::post('/queues/restart', [QueueDashboardController::class, 'restart'])->name('queues.restart');
            Route::post('/queues/failed/retry-all', [QueueDashboardController::class, 'retryAll'])->name('queues.failed.retry-all');
            Route::post('/queues/failed/{id}/retry', [QueueDashboardController::class, 'retry'])->name('queues.failed.retry');
            Route::delete('/queues/failed/{id}', [QueueDashboardController::class, 'forget'])->name('queues.failed.forget');
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
            Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
            Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
            Route::prefix('seo')->name('seo.')->group(function (): void {
                Route::get('/', SeoDashboardController::class)->name('dashboard');
                Route::get('/settings', [SeoSettingsController::class, 'edit'])->name('settings.edit');
                Route::put('/settings', [SeoSettingsController::class, 'update'])->name('settings.update');
                Route::get('/redirects', [SeoRedirectsController::class, 'index'])->name('redirects.index');
                Route::post('/redirects', [SeoRedirectsController::class, 'store'])->name('redirects.store');
                Route::put('/redirects/{redirect}', [SeoRedirectsController::class, 'update'])->name('redirects.update');
                Route::delete('/redirects/{redirect}', [SeoRedirectsController::class, 'destroy'])->name('redirects.destroy');
                Route::get('/404', [SeoNotFoundController::class, 'index'])->name('not-found.index');
                Route::put('/404/{notFound}', [SeoNotFoundController::class, 'update'])->name('not-found.update');
                Route::get('/audit', [SeoAuditController::class, 'index'])->name('audit.index');
            });
            Route::prefix('consent')->name('consent.')->group(function (): void {
                Route::get('/', ConsentDashboardController::class)->name('dashboard');
                Route::get('/settings', [ConsentSettingsController::class, 'edit'])->name('settings.edit');
                Route::put('/settings', [ConsentSettingsController::class, 'update'])->name('settings.update');
                Route::post('/settings/publish', [ConsentSettingsController::class, 'publish'])->name('settings.publish');
                Route::get('/categories', [ConsentCategoriesController::class, 'index'])->name('categories.index');
                Route::put('/categories/{category}', [ConsentCategoriesController::class, 'update'])->name('categories.update');
                Route::get('/services', [ConsentServicesController::class, 'index'])->name('services.index');
                Route::get('/services/{service}', [ConsentServicesController::class, 'show'])->name('services.show');
                Route::put('/services/{service}', [ConsentServicesController::class, 'update'])->name('services.update');
                Route::get('/scans', [ConsentScansController::class, 'index'])->name('scans.index');
                Route::post('/scans', [ConsentScansController::class, 'store'])->name('scans.store');
            });
            Route::get('/backups', [BackupsController::class, 'index'])->name('backups.index');
            Route::put('/backups/destinations/{destination}', [BackupsController::class, 'updateDestination'])->name('backups.destinations.update');
            Route::post('/backups/destinations/{destination}/test', [BackupsController::class, 'testDestination'])->middleware('throttle:backups')->name('backups.destinations.test');
            Route::put('/backups/plans/{plan}', [BackupsController::class, 'updatePlan'])->name('backups.plans.update');
            Route::post('/backups/plans/{plan}/run', [BackupsController::class, 'run'])->middleware('throttle:backups')->name('backups.plans.run');
            Route::post('/backups/plans/{plan}/test-email', [BackupsController::class, 'testEmail'])->middleware('throttle:backups')->name('backups.plans.test-email');
            Route::post('/backups/runs/{run}/verify', [BackupsController::class, 'verify'])->middleware('throttle:backups')->name('backups.runs.verify');
            Route::delete('/backups/runs/{run}', [BackupsController::class, 'destroy'])->name('backups.runs.destroy');
            Route::get('/media', [MediaController::class, 'index'])->name('media.index');
            Route::post('/media', [MediaController::class, 'store'])->middleware('throttle:media-upload')->name('media.store');
            Route::get('/media-library', [MediaController::class, 'library'])->name('media.library');
            Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
            Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
            Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
            Route::post('/media/{media}/optimize', [MediaController::class, 'optimize'])->middleware('throttle:media-upload')->name('media.optimize');
            Route::post('/media/{media}/restore', [MediaController::class, 'restore'])->name('media.restore');
            Route::delete('/media/{media}/force', [MediaController::class, 'forceDelete'])->name('media.force-delete');
            Route::get('/updates', [UpdatesController::class, 'index'])->name('updates.index');
            Route::post('/updates/{package}/run', [UpdatesController::class, 'update'])
                ->where('package', '.*')
                ->name('updates.run');
            Route::resource('users', UsersController::class)->except(['show']);
            Route::resource('roles', RolesController::class)->except(['show']);
        });
    });
