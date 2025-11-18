<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

// Authentication API Routes
Route::post('/auth/send-pin', [AuthController::class, 'sendPin'])->name('api.auth.send-pin');
Route::post('/auth/verify-pin', [AuthController::class, 'verifyPin'])->name('api.auth.verify-pin');
Route::post('/auth/resend-pin', [AuthController::class, 'resendPin'])->name('api.auth.resend-pin');
