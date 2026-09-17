<?php

use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ToolController;
use App\Http\Controllers\Api\Admin\ToolFileController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DemoController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/stripe/webhook', StripeWebhookController::class);

Route::get('/tools', [StoreController::class, 'index']);

Route::post('/license/validate', [LicenseController::class, 'validateRequest']);
Route::post('/license/activate', [LicenseController::class, 'activate']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/me/credits', [ReferralController::class, 'credits']);

    Route::post('/tools/{tool}/checkout', [StripeController::class, 'checkout']);
    Route::get('/tools/{tool}/demo', [DemoController::class, 'show']);
    Route::get('/tools/{tool}/download', [DownloadController::class, 'download']);
    Route::get('/tools/{tool}/download/config', [DownloadController::class, 'config']);

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
        Route::get('/analytics', [AnalyticsController::class, 'index']);
    });
