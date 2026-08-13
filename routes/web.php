<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DailyActivityController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\FinancialHighlightController;
use App\Http\Controllers\ExportController;

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

// ── DEV-ONLY: auto-login (remove before production) ──────────────────────
if (app()->isLocal()) {
    Route::get('/dev-login/{id?}', function ($id = 1) {
        $user = App\Models\User::findOrFail($id);
        return redirect()->route('dashboard');
    })->name('dev.login');
}

// Authentication Routes
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/auth/send-pin', [AuthController::class, 'sendPin'])->name('auth.send-pin');
Route::get('/auth/verify-pin', [AuthController::class, 'showVerifyPinForm'])->name('auth.verify-pin.form');
Route::post('/auth/verify-pin', [AuthController::class, 'verifyPin'])->name('auth.verify-pin');
Route::post('/auth/resend-pin', [AuthController::class, 'resendPin'])->name('auth.resend-pin');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Display Board Kiosk — akses tanpa login menggunakan token rahasia di URL
// Contoh: https://finboard.test/tv?token=<DISPLAY_BOARD_TOKEN>
Route::get('/tv', [DashboardController::class, 'displayBoard'])
    ->middleware('display.token')
    ->name('display.board.kiosk');

// Display Board Render — AJAX endpoint (same token middleware)
Route::get('/tv/render', [DashboardController::class, 'displayBoard'])
    ->middleware('display.token')
    ->name('display.board.render');

// Protected Dashboard Routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/render', [DashboardController::class, 'renderContent'])->name('dashboard.render');
    Route::get('/dashboard-simple', [DashboardController::class, 'indexSimple'])->name('dashboard.simple');
    Route::get('/dashboard/segmentasi-detail/{category}/{type}', [DashboardController::class, 'getSegmentasiDetail'])->name('dashboard.segmentasi.detail');
    Route::get('/dashboard/segmentasi-kol-detail/{category}/{type}/{kol}', [DashboardController::class, 'getSegmentasiKolDetail'])->name('dashboard.segmentasi.kol.detail');
    Route::get('/dashboard/kecamatan-detail/{kecamatan}', [DashboardController::class, 'getKecamatanDetail'])->name('dashboard.kecamatan.detail');
    Route::get('/dashboard/ao-detail/{nmao}', [DashboardController::class, 'getAODetail'])->name('dashboard.ao.detail');
    Route::get('/dashboard/ao-npf-detail/{nmao}', [DashboardController::class, 'getAONpfDetail'])->name('dashboard.ao.npf.detail');
    Route::get('/dashboard/ao-funding-detail/{kodeaoh}', [DashboardController::class, 'getAOFundingDetail'])->name('dashboard.ao.funding.detail');
    Route::get('/dashboard/ao-customer-details/{ao}/{month}/{category}', [DashboardController::class, 'getAOCustomerDetails'])->name('dashboard.ao.customer.details');
    Route::get('/dashboard/nasabah-status-detail/{status}', [DashboardController::class, 'getNasabahStatusDetail'])->name('dashboard.nasabah.status.detail');
    Route::get('/dashboard/trend-kontrak-detail', [DashboardController::class, 'getTrendKontrakDetail'])->name('dashboard.trend.kontrak.detail');
    Route::get('/dashboard/trend-funding-detail', [DashboardController::class, 'getTrendFundingDetail'])->name('dashboard.trend.funding.detail');
    Route::get('/dashboard/trend-product-detail', [DashboardController::class, 'getTrendProductDetail'])->name('dashboard.trend.product.detail');
    Route::get('/dashboard/customer-details', [DashboardController::class, 'getCustomerDetails'])->name('dashboard.customer.details');
    Route::get('/dashboard/kolektibilitas-details', [DashboardController::class, 'getKolektibilitasDetails'])->name('dashboard.kolektibilitas.details');

    // Financial Highlights Dashboard API (accessible by all authenticated users)
    Route::get('/api/financial-highlights/dashboard', [FinancialHighlightController::class, 'getDashboardData'])->name('financial-highlights.dashboard');

    // Export Routes
    Route::get('/export/dashboard', [ExportController::class, 'exportDashboard'])->name('export.dashboard');
    Route::get('/export/data', [ExportController::class, 'showDataExportForm'])->name('export.data.form');
    Route::post('/export/data', [ExportController::class, 'exportSelectedData'])->name('export.data.download');
    Route::get('/api/financial-highlights/calculate', [FinancialHighlightController::class, 'calculateDerivedValues'])->name('financial-highlights.calculate');

    // TEMPORARY: Simple test route
    Route::get('/test', function () {
        return response()->json(['status' => 'ok', 'message' => 'Laravel is working']);
    });    // Daily Activity Routes (Admin and Pengurus only)
    Route::middleware(['role:admin,pengurus'])->group(function () {
        Route::get('/daily-activity', [DailyActivityController::class, 'index'])->name('daily.activity.index');
        Route::get('/display-board', [DashboardController::class, 'displayBoard'])->name('display.board');
        Route::get('/display-board/render', [DashboardController::class, 'displayBoard'])->name('display.board.render.auth');
    });

    // Upload Routes (Admin and Lending roles)
    Route::middleware(['role:admin,lending'])->group(function () {
        Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
        Route::get('/upload/history', [UploadController::class, 'history'])->name('upload.history');
        Route::post('/upload', [UploadController::class, 'upload'])->name('upload.store');
        Route::delete('/upload/clear', [UploadController::class, 'clear'])->name('upload.clear');
        Route::get('/upload/template/{type}', [UploadController::class, 'downloadTemplate'])->name('upload.template');

        // Legacy route kept for backward compatibility, but handled by the
        // async upload pipeline so old funding pages do not process CSV inline.
        Route::post('/funding/upload', [UploadController::class, 'upload'])->name('funding.upload');
    });

    // User Settings Routes (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/user-settings', [UserSettingsController::class, 'index'])->name('user.settings.index');
        Route::post('/user-settings', [UserSettingsController::class, 'store'])->name('user.settings.store');
        Route::put('/user-settings/{user}', [UserSettingsController::class, 'update'])->name('user.settings.update');
        Route::delete('/user-settings/{user}', [UserSettingsController::class, 'destroy'])->name('user.settings.destroy');

        // Financial Highlights Management Routes (Admin only)
        Route::resource('financial-highlights', FinancialHighlightController::class);

        // Admin Backup/Restore UI
        Route::get('/admin/backups', [\App\Http\Controllers\BackupController::class, 'index'])->name('admin.backups.index');
        Route::post('/admin/backups/create', [\App\Http\Controllers\BackupController::class, 'createBackup'])->name('admin.backups.create');
        Route::post('/admin/backups/restore', [\App\Http\Controllers\BackupController::class, 'restore'])->name('admin.backups.restore');
        Route::post('/admin/backups/delete', [\App\Http\Controllers\BackupController::class, 'delete'])->name('admin.backups.delete');
        Route::get('/admin/backups/download/{file}', [\App\Http\Controllers\BackupController::class, 'download'])->name('admin.backups.download');
        Route::post('/admin/backups/upload', [\App\Http\Controllers\BackupController::class, 'upload'])->name('admin.backups.upload');
    });
});

// Logout Route
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
