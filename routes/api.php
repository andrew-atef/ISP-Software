<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BootstrapController;
use App\Http\Controllers\Api\FinancialController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InventoryTransferController;
use App\Http\Controllers\Api\TaskExecutionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bootstrap', BootstrapController::class);

    Route::post('/tasks/{id}/start', [TaskExecutionController::class, 'start']);
    Route::post('/tasks/{id}/pause', [TaskExecutionController::class, 'pause']);
    Route::post('/tasks/{id}/media', [TaskExecutionController::class, 'uploadMedia']);
    Route::post('/tasks/{id}/complete', [TaskExecutionController::class, 'complete']);

    Route::get('/inventory/wallet', [InventoryController::class, 'getWallet']);
    Route::post('/inventory/requests', [InventoryController::class, 'storeRequest']);

    Route::post('/inventory/transfer', [InventoryTransferController::class, 'store']);
    Route::get('/inventory/transfer/incoming', [InventoryTransferController::class, 'incomingList']);
    Route::post('/inventory/transfer/{id}/accept', [InventoryTransferController::class, 'accept']);
    Route::post('/inventory/transfer/{id}/reject', [InventoryTransferController::class, 'reject']);

    Route::get('/financials/summary', [FinancialController::class, 'summary']);
    Route::get('/financials/loans', [FinancialController::class, 'loans']);
});

