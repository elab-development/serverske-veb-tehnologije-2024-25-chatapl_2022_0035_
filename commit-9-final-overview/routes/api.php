<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\PasswordResetController;

/*
|--------------------------------------------------------------------------
| API Rute
|--------------------------------------------------------------------------
|
| Ovde možete registrovati API rute za vašu aplikaciju. Ove
| rute se učitavaju u RouteServiceProvider-u i sve će biti
| dodeljene "api" middleware grupi. Napravite nešto odlično!
|
*/

// Javne rute
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/request-reset', [PasswordResetController::class, 'sendResetLink']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

// Zaštićene rute
Route::middleware('auth:sanctum')->group(function () {
    // Autentifikacione rute
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Rute za sobe
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::put('/rooms/{id}', [RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
    Route::post('/rooms/{id}/join', [RoomController::class, 'join']);
    Route::post('/rooms/{id}/leave', [RoomController::class, 'leave']);

    // Rute za poruke
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{id}', [MessageController::class, 'show']);
    Route::put('/messages/{id}', [MessageController::class, 'update']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
    Route::post('/messages/upload', [MessageController::class, 'uploadFile']);
    Route::get('/messages/{id}/download', [MessageController::class, 'downloadFile']);

    // Rute za export
    Route::get('/export/messages/{roomId}', [ExportController::class, 'exportMessages']);
    Route::get('/export/room-stats/{roomId}', [ExportController::class, 'exportRoomStats']);

    // Rute za statistike
    Route::get('/statistics/overall', [StatisticsController::class, 'overall']);
    Route::get('/statistics/room-stats', [StatisticsController::class, 'roomStats']);
    Route::get('/statistics/user-stats', [StatisticsController::class, 'userStats']);
    Route::post('/statistics/clear-cache', [StatisticsController::class, 'clearCache']);

    // Rute za upravljanje lozinkama
    Route::post('/password/change', [PasswordResetController::class, 'changePassword']);
}); 