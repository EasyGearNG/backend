<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\LogViewerController;

Route::get('/', function () {
    return view('welcome');
});

// Payment callback routes (simulates frontend)
Route::get('/payment/callback', [PaymentCallbackController::class, 'handleCallback'])->name('payment.callback');
Route::get('/payment/test', [PaymentCallbackController::class, 'testVerification'])->name('payment.test');

// Log Viewer Routes (Password Protected)
Route::prefix('log-viewer')->name('logs.')->group(function () {
    Route::get('/login', [LogViewerController::class, 'showLogin'])->name('login');
    Route::post('/login', [LogViewerController::class, 'login']);
    Route::get('/logout', [LogViewerController::class, 'logout'])->name('logout');
    Route::get('/', [LogViewerController::class, 'index'])->name('index');
    Route::post('/clear', [LogViewerController::class, 'clear'])->name('clear');
});

// Logs Management Routes
Route::prefix('logs')->name('old.logs.')->group(function () {
    Route::get('/', [LogsController::class, 'index'])->name('index');
    Route::get('/download/{file}', [LogsController::class, 'download'])->name('download');
    Route::get('/clear/{file}', [LogsController::class, 'clear'])->name('clear');
});
