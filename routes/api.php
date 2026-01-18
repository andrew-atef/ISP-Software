<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BootstrapController;
use App\Http\Controllers\Api\TaskExecutionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bootstrap', BootstrapController::class);

    Route::post('/tasks/{id}/start', [TaskExecutionController::class, 'start']);
    Route::post('/tasks/{id}/pause', [TaskExecutionController::class, 'pause']);
    Route::post('/tasks/{id}/media', [TaskExecutionController::class, 'uploadMedia']);
    Route::post('/tasks/{id}/complete', [TaskExecutionController::class, 'complete']);
});
