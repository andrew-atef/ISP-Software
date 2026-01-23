<?php

use App\Http\Controllers\PhotoWatermarkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Photo watermark route
Route::get('/photos/{id}/watermarked', [PhotoWatermarkController::class, 'show'])
    ->name('photos.watermarked')
    ->middleware('auth');

