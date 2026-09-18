<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\DispatcherAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\DispatcherController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportEntryController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/dispatcher/register', [DispatcherAuthController::class, 'register'])
        ->middleware('throttle:6,1');
    Route::post('/dispatcher/login', [DispatcherAuthController::class, 'login'])
        ->middleware('throttle:6,1');
    Route::post('/login', [UserAuthController::class, 'login'])
        ->middleware('throttle:6,1');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
});

// Dispatcher-only endpoints.
Route::middleware(['auth:sanctum', 'dispatcher'])->group(function () {
    Route::post('/reports', [ReportController::class, 'store']);
});

// Manager/Admin-only endpoints.
Route::middleware(['auth:sanctum', 'role:manager,admin'])->group(function () {
    Route::post('/drivers', [DriverController::class, 'store']);
    Route::put('/drivers/{driver}', [DriverController::class, 'update']);
    Route::delete('/drivers/{driver}', [DriverController::class, 'destroy']);

    Route::post('/zones', [ZoneController::class, 'store']);
    Route::put('/zones/{zone}', [ZoneController::class, 'update']);
    Route::delete('/zones/{zone}', [ZoneController::class, 'destroy']);

    Route::get('/dispatchers', [DispatcherController::class, 'index']);
    Route::post('/dispatchers', [DispatcherController::class, 'store']);
    Route::put('/dispatchers/{dispatcher}', [DispatcherController::class, 'update']);
    Route::patch('/dispatchers/{dispatcher}/approval', [DispatcherController::class, 'updateApproval']);
    Route::delete('/dispatchers/{dispatcher}', [DispatcherController::class, 'destroy']);

    Route::patch('/reports/{report}/status', [ReportController::class, 'updateStatus']);
    Route::patch('/reports/{report}/ai-summary/read', [ReportController::class, 'markAiSummaryRead']);

    Route::get('/entries/search', [InsightController::class, 'searchEntries']);
    Route::get('/drivers/top-cited', [InsightController::class, 'topCitedDrivers']);
    Route::get('/dashboard/stats', [InsightController::class, 'dashboardStats']);

    // The chat agent's tools query the whole database unscoped (no
    // per-dispatcher filtering), so the endpoint itself must stay
    // manager/admin-only — same reasoning as entries/search above.
    Route::post('/chat', [AiController::class, 'chat']);
});

// Shared endpoints reachable by ANY authenticated principal (dispatcher or user).
// Fine-grained ownership (dispatcher vs manager/admin) is enforced per-resource
// via ReportPolicy inside the controllers, not via route middleware.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/drivers', [DriverController::class, 'index']);
    Route::get('/drivers/{driver}', [DriverController::class, 'show']);

    Route::get('/zones', [ZoneController::class, 'index']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::put('/reports/{report}', [ReportController::class, 'update']);
    Route::delete('/reports/{report}', [ReportController::class, 'destroy']);

    Route::post('/reports/{report}/entries', [ReportEntryController::class, 'store']);
    Route::put('/reports/{report}/entries/{entry}', [ReportEntryController::class, 'update']);
    Route::delete('/reports/{report}/entries/{entry}', [ReportEntryController::class, 'destroy']);

    Route::post('/reports/{report}/analyze', [AiController::class, 'analyze']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
});
