<?php

use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Customers\AdminCustomersController as Admin;
use Pcteckserv\CmsCore\Customers\CustomerController as Customer;
use Pcteckserv\CmsCore\Customers\CustomerSession;
use Pcteckserv\CmsCore\Security\SecurityController as Security;

Route::middleware('web')->group(function () {
    Route::prefix('cliente')->name('customers.')->group(function () {
        Route::get('entrar', [Customer::class, 'login'])->name('login');
        Route::post('entrar', [Customer::class, 'authenticate'])->middleware('throttle:5,1')->name('login.submit');
        Route::get('registar', [Customer::class, 'register'])->name('register');
        Route::post('registar', [Customer::class, 'store'])->middleware('throttle:5,1')->name('register.submit');
        Route::get('recuperar', [Customer::class, 'forgot'])->name('password.forgot');
        Route::post('recuperar', [Customer::class, 'sendReset'])->middleware('throttle:3,1')->name('password.email');
        Route::get('recuperar/{token}', [Customer::class, 'reset'])->name('password.reset');
        Route::post('nova-palavra-passe', [Customer::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
        Route::middleware(CustomerSession::class)->group(function () {
            Route::get('/', [Customer::class, 'account'])->name('account');
            Route::put('perfil', [Customer::class, 'update'])->name('profile.update');
            Route::get('moradas', [Customer::class, 'addresses'])->name('addresses');
            Route::post('moradas', [Customer::class, 'saveAddress'])->name('addresses.store');
            Route::put('moradas/{address}', [Customer::class, 'saveAddress'])->name('addresses.update');
            Route::delete('moradas/{address}', [Customer::class, 'deleteAddress'])->name('addresses.destroy');
            Route::post('sair', [Customer::class, 'logout'])->name('logout');
        });
    });
    Route::prefix('seguranca')->name('security.')->group(function () {
        Route::get('verificar', [Security::class, 'challenge'])->name('challenge');
        Route::post('verificar', [Security::class, 'verify'])->middleware('throttle:5,1')->name('verify');
        Route::get('{guard}', [Security::class, 'edit'])->name('edit');
        Route::post('{guard}/preparar', [Security::class, 'setup'])->middleware('throttle:5,1')->name('setup');
        Route::post('{guard}/confirmar', [Security::class, 'confirm'])->middleware('throttle:5,1')->name('confirm');
        Route::delete('{guard}', [Security::class, 'disable'])->middleware('throttle:5,1')->name('disable');
    });
    Route::middleware(['auth:web', 'can:core.customers.view'])->prefix('admin/clientes')->name('admin.customers.')->group(function () {
        Route::get('/', [Admin::class, 'index'])->name('index');
        Route::middleware('can:core.customers.manage')->group(function () {
            Route::get('grupos', [Admin::class, 'groups'])->name('groups');
            Route::post('grupos', [Admin::class, 'saveGroup'])->name('groups.store');
            Route::put('grupos/{group}', [Admin::class, 'saveGroup'])->name('groups.update');
            Route::delete('grupos/{group}', [Admin::class, 'deleteGroup'])->name('groups.destroy');
            Route::get('criar', [Admin::class, 'edit'])->name('create');
            Route::post('/', [Admin::class, 'save'])->name('store');
            Route::put('{customer}', [Admin::class, 'save'])->name('update');
            Route::delete('{customer}', [Admin::class, 'destroy'])->name('destroy');
            Route::post('{customer}/moradas', [Admin::class, 'address'])->name('addresses.store');
            Route::put('{customer}/moradas/{address}', [Admin::class, 'address'])->name('addresses.update');
            Route::delete('{customer}/moradas/{address}', [Admin::class, 'deleteAddress'])->name('addresses.destroy');
        });
        Route::get('{customer}', [Admin::class, 'edit'])->whereNumber('customer')->name('edit');
    });
});
