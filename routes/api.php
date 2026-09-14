<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ToolController;
use App\Http\Controllers\Api\Admin\ToolFileController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/stripe/webhook', StripeWebhookController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/tools/{tool}/checkout', [StripeController::class, 'checkout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::middleware(['auth:sanctum', 'role:super_admin,admin'])
    ->prefix('admin')
    ->group(function () {
        Route::apiResource('tools', ToolController::class);
        Route::post('/tools/{tool}/files', [ToolFileController::class, 'store']);
        Route::delete('/tools/{tool}/files/{file}', [ToolFileController::class, 'destroy'])->scopeBindings();

        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{user}', [UserController::class, 'update']);

        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    });
