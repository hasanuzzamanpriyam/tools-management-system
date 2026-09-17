<?php

use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'));

Route::get('/login', fn () => view('app'))->name('login');

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'github|google');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'github|google');

Route::get('/{any}', fn () => view('app'))->where('any', '.*');