<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TobongController;
use App\Http\Controllers\DaftarAnggotaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\RiwayatController; 
use App\Http\Controllers\PembayaranController; 
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\DashboardController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ======================
// 🔓 1. Auth Routes (PUBLIC)
// ======================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/public/stats', [DashboardController::class, 'publicStats']);


// ======================
// 🔒 2. Protected Routes (JWT Required)
// ======================
Route::middleware('auth:api')->group(function () {

    // --- 2.1 Sesi Pengguna ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- 2.2 Endpoint Lookup Cepat (Khusus untuk Dropdown FE) ---
    
    // Anggota Lookup: Mengambil ID dan Nama saja (untuk dropdown Keuangan & Pembakaran)
    // Memanggil TobongController::index atau membuat method baru TobongController::lookup
    Route::get('/lookup/anggota', [DaftarAnggotaController::class, 'index']); // Bisa menggunakan index jika index sudah efisien
    
    // Tobong Lookup: Mengambil ID dan Nama Tobong (untuk dropdown Pembakaran)
    // Route::get('/lookup/tobong', [TobongController::class, 'index']); // Bisa menggunakan index jika index sudah efisien
    // // FIX: Rute Lookup Tobong
    Route::get('/daftartobong', [TobongController::class, 'lookup']); // <--- INI PERBAIKANNYA

    // --- 2.3 Master Data ---
    Route::apiResource('users', UserController::class); 
    Route::apiResource('tobong', TobongController::class); 
    Route::apiResource('daftaranggota', DaftarAnggotaController::class); 

    
    // --- 2.4 Transaksi & Riwayat ---
    Route::apiResource('riwayat', RiwayatController::class); // Pembakaran Sampah
    // TAMBAHKAN INI (Route Custom untuk Bulk Update)
    Route::post('/riwayat/bulk-update', [RiwayatController::class, 'bulkUpdateStatus']);
    Route::apiResource('pembayaran', PembayaranController::class); // Keuangan
    
    // --- 2.5 Laporan ---
    Route::get('/predict-growth', [PredictionController::class, 'predictGrowth']);
    Route::get('/dashboard-stats', [DashboardController::class, 'index']);
});