<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\PaymentCallbackController;

Route::get('/', function () {
    return view('welcome');
});

// Payment callback routes (simulates frontend)
Route::get('/payment/callback', [PaymentCallbackController::class, 'handleCallback'])->name('payment.callback');
Route::get('/payment/test', [PaymentCallbackController::class, 'testVerification'])->name('payment.test');

// Logs Management Routes
Route::prefix('logs')->name('logs.')->group(function () {
    Route::get('/', [LogsController::class, 'index'])->name('index');
    Route::get('/download/{file}', [LogsController::class, 'download'])->name('download');
    Route::get('/clear/{file}', [LogsController::class, 'clear'])->name('clear');
});
