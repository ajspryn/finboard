<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\FundingController;
use App\Http\Controllers\DailyActivityController;

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

// Login PIN Routes
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Protected Dashboard Routes (require PIN authentication)
Route::middleware(['App\Http\Middleware\CheckPin'])->group(function () {
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

     // Upload Routes
     Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
     Route::post('/upload', [UploadController::class, 'upload'])->name('upload.store');
     Route::delete('/upload/clear', [UploadController::class, 'clear'])->name('upload.clear');

     // Funding Routes
     Route::get('/funding', [FundingController::class, 'index'])->name('funding.index');
     Route::post('/funding/upload', [FundingController::class, 'upload'])->name('funding.upload');

     // Daily Activity Routes
     Route::get('/daily-activity', [DailyActivityController::class, 'index'])->name('daily.activity.index');
});

// Logout Route
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
