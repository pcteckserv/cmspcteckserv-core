<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response('Core test host'))->name('home');
