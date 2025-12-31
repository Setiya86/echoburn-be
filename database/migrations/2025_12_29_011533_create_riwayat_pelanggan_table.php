<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('riwayat_pelanggan', function (Blueprint $table) {
            $table->id();
            $table->date('periode_rekap'); // Misal: 2023-10-01 (Mewakili data Oktober)
            $table->integer('total_pelanggan_aktif');
            $table->integer('jumlah_baru_bulan_ini')->default(0); // Opsional: Berguna untuk analisis
            $table->integer('jumlah_keluar_bulan_ini')->default(0); // Opsional: Berguna untuk analisis churn
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('riwayat_pelanggan');
    }
};