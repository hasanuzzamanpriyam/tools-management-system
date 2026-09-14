<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'));

Route::get('/login', fn () => view('app'))->name('login');

Route::get('/{any}', fn () => view('app'))->where('any', '.*');