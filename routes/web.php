<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogsController;

Route::get('/', function () {
    return view('welcome');
});

// Logs Management Routes
Route::prefix('logs')->name('logs.')->group(function () {
    Route::get('/', [LogsController::class, 'index'])->name('index');
    Route::get('/download/{file}', [LogsController::class, 'download'])->name('download');
    Route::get('/clear/{file}', [LogsController::class, 'clear'])->name('clear');
});
