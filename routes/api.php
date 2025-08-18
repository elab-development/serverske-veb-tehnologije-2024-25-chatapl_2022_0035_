<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ExternalApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/request-reset', [PasswordResetController::class, 'sendResetLink']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Room routes
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::put('/rooms/{id}', [RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
    Route::post('/rooms/{id}/join', [RoomController::class, 'join']);
    Route::post('/rooms/{id}/leave', [RoomController::class, 'leave']);

    // Message routes
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{id}', [MessageController::class, 'show']);
    Route::put('/messages/{id}', [MessageController::class, 'update']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
    Route::post('/messages/upload', [MessageController::class, 'uploadFile']);
    Route::get('/messages/{id}/download', [MessageController::class, 'downloadFile']);

    // Export routes
    Route::get('/export/messages/{roomId}', [ExportController::class, 'exportMessages']);
    Route::get('/export/room-stats/{roomId}', [ExportController::class, 'exportRoomStats']);

    // Statistics routes
    Route::get('/statistics/overall', [StatisticsController::class, 'overall']);
    Route::get('/statistics/room-stats', [StatisticsController::class, 'roomStats']);
    Route::get('/statistics/user-stats', [StatisticsController::class, 'userStats']);
    Route::post('/statistics/clear-cache', [StatisticsController::class, 'clearCache']);

    // Password management routes
    Route::post('/password/change', [PasswordResetController::class, 'changePassword']);

    // External API routes
    Route::prefix('external')->group(function () {
        Route::get('/weather', [ExternalApiController::class, 'weather']);
        Route::post('/translate', [ExternalApiController::class, 'translate']);
        Route::get('/news', [ExternalApiController::class, 'news']);
        Route::post('/currency', [ExternalApiController::class, 'currency']);
        Route::get('/ip-info', [ExternalApiController::class, 'ipInfo']);
        Route::get('/domain-info', [ExternalApiController::class, 'domainInfo']);
        Route::get('/crypto-price', [ExternalApiController::class, 'cryptoPrice']);
        Route::get('/youtube-info', [ExternalApiController::class, 'youtubeInfo']);
        Route::get('/github-repo', [ExternalApiController::class, 'githubRepo']);
        Route::get('/available-apis', [ExternalApiController::class, 'availableApis']);
    });

    // Audit routes (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/audit/logs', [AuditController::class, 'index']);
        Route::get('/audit/logs/{id}', [AuditController::class, 'show']);
        Route::get('/audit/security-stats', [AuditController::class, 'securityStats']);
        Route::get('/audit/suspicious-activities', [AuditController::class, 'suspiciousActivities']);
        Route::get('/audit/failed-logins', [AuditController::class, 'failedLogins']);
        Route::get('/audit/export', [AuditController::class, 'export']);
    });
}); 