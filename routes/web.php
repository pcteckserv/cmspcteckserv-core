<?php

use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Http\Controllers\Admin\ArtisanCommandsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\DashboardController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\MediaController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\SiteOptionsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\SmtpSettingsController;
use Pcteckserv\CmsCore\Http\Controllers\Admin\UpdatesController;
use Pcteckserv\CmsCore\Http\Controllers\Auth\AuthenticatedSessionController;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware('auth')
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('/site-options', [SiteOptionsController::class, 'edit'])->name('site-options.edit');
            Route::put('/site-options', [SiteOptionsController::class, 'update'])->name('site-options.update');
            Route::get('/smtp-settings', [SmtpSettingsController::class, 'edit'])->name('smtp-settings.edit');
            Route::put('/smtp-settings', [SmtpSettingsController::class, 'update'])->name('smtp-settings.update');
            Route::post('/smtp-settings/test', [SmtpSettingsController::class, 'test'])->name('smtp-settings.test');
            Route::get('/laravel-commands', [ArtisanCommandsController::class, 'index'])->name('laravel-commands.index');
            Route::post('/laravel-commands/{command}/run', [ArtisanCommandsController::class, 'run'])->name('laravel-commands.run');
            Route::get('/media', [MediaController::class, 'index'])->name('media.index');
            Route::post('/media', [MediaController::class, 'store'])->middleware('throttle:media-upload')->name('media.store');
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
        });
    });
