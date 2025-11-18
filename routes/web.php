<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\FundingController;
use App\Http\Controllers\DailyActivityController;
use App\Http\Controllers\UserSettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication Routes
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/auth/send-pin', [AuthController::class, 'sendPin'])->name('auth.send-pin');
Route::get('/auth/verify-pin', [AuthController::class, 'showVerifyPinForm'])->name('auth.verify-pin');
Route::post('/auth/verify-pin', [AuthController::class, 'verifyPin'])->name('auth.verify-pin');
Route::post('/auth/resend-pin', [AuthController::class, 'resendPin'])->name('auth.resend-pin');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Protected Dashboard Routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard-simple', [DashboardController::class, 'indexSimple'])->name('dashboard.simple');
    Route::get('/dashboard/segmentasi-detail/{category}/{type}', [DashboardController::class, 'getSegmentasiDetail'])->name('dashboard.segmentasi.detail');
    Route::get('/dashboard/segmentasi-kol-detail/{category}/{type}/{kol}', [DashboardController::class, 'getSegmentasiKolDetail'])->name('dashboard.segmentasi.kol.detail');
    Route::get('/dashboard/kecamatan-detail/{kecamatan}', [DashboardController::class, 'getKecamatanDetail'])->name('dashboard.kecamatan.detail');
    Route::get('/dashboard/ao-detail/{nmao}', [DashboardController::class, 'getAODetail'])->name('dashboard.ao.detail');
    Route::get('/dashboard/ao-npf-detail/{nmao}', [DashboardController::class, 'getAONpfDetail'])->name('dashboard.ao.npf.detail');
    Route::get('/dashboard/nasabah-status-detail/{status}', [DashboardController::class, 'getNasabahStatusDetail'])->name('dashboard.nasabah.status.detail');
    Route::get('/dashboard/trend-kontrak-detail', [DashboardController::class, 'getTrendKontrakDetail'])->name('dashboard.trend.kontrak.detail');
    Route::get('/dashboard/trend-funding-detail', [DashboardController::class, 'getTrendFundingDetail'])->name('dashboard.trend.funding.detail');
    Route::get('/dashboard/trend-product-detail', [DashboardController::class, 'getTrendProductDetail'])->name('dashboard.trend.product.detail');

    // Daily Activity Routes (Admin and Pengurus only)
    Route::middleware(['role:admin,pengurus'])->group(function () {
        Route::get('/daily-activity', [DailyActivityController::class, 'index'])->name('daily.activity.index');
    });

    // Upload Routes (Admin and Lending roles)
    Route::middleware(['role:admin,lending'])->group(function () {
        Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
        Route::post('/upload', [UploadController::class, 'upload'])->name('upload.store');
        Route::delete('/upload/clear', [UploadController::class, 'clear'])->name('upload.clear');
    });

    // User Settings Routes (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/user-settings', [UserSettingsController::class, 'index'])->name('user.settings.index');
        Route::post('/user-settings', [UserSettingsController::class, 'store'])->name('user.settings.store');
        Route::put('/user-settings/{user}', [UserSettingsController::class, 'update'])->name('user.settings.update');
        Route::delete('/user-settings/{user}', [UserSettingsController::class, 'destroy'])->name('user.settings.destroy');
    });

    // Funding Routes (Admin and Funding roles)
    Route::middleware(['role:admin,funding'])->group(function () {
        Route::get('/funding', [FundingController::class, 'index'])->name('funding.index');
        Route::post('/funding/upload', [FundingController::class, 'upload'])->name('funding.upload');
    });
});

// Logout Route
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
