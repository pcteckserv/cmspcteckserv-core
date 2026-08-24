<?php

use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Http\Controllers\Admin\DashboardController;
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
            Route::get('/updates', [UpdatesController::class, 'index'])->name('updates.index');
            Route::post('/updates/{package}/run', [UpdatesController::class, 'update'])
                ->where('package', '.*')
                ->name('updates.run');
        });
    });
