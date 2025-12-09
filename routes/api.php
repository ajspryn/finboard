<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\SearchController;

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

// Elasticsearch Search API Routes
Route::middleware(['web', 'auth'])->prefix('search')->group(function () {
    Route::get('/pembiayaan', [SearchController::class, 'searchPembiayaan'])->name('api.search.pembiayaan');
    Route::get('/tabungan', [SearchController::class, 'searchTabungan'])->name('api.search.tabungan');
    Route::get('/deposito', [SearchController::class, 'searchDeposito'])->name('api.search.deposito');
    Route::get('/financial-highlights', [SearchController::class, 'searchFinancialHighlights'])->name('api.search.financial-highlights');
    Route::get('/all', [SearchController::class, 'searchAll'])->name('api.search.all');

    // Detail routes
    Route::get('/pembiayaan/{id}', [SearchController::class, 'getPembiayaanDetail'])->name('api.search.pembiayaan.detail');
    Route::get('/tabungan/{id}', [SearchController::class, 'getTabunganDetail'])->name('api.search.tabungan.detail');
    Route::get('/deposito/{id}', [SearchController::class, 'getDepositoDetail'])->name('api.search.deposito.detail');
    Route::get('/financial-highlights/{id}', [SearchController::class, 'getFinancialHighlightDetail'])->name('api.search.financial-highlights.detail');
});
