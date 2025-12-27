<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tobong', function (Blueprint $table) {
            // 1. Primary Key
            $table->id('tobong_id');

            // 2. Nama Tobong (Identitas utama)
            $table->string('nama_tobong');

            // 3. Tanggal Pembuatan (Pakai type Date agar lengkap tgl/bln/thn)
            $table->date('tanggal_pembuatan')->nullable();

            // 4. Lokasi
            $table->string('lokasi');

            // 5. Kapasitas (Dalam satuan integer, misal kg atau ton)
            $table->integer('kapasitas')->nullable(); 

            // 6. Kapasitas Abu
            $table->integer('kapasitas_abu')->nullable();

            // 7. Status Operasional (Default 'aktif')
            // Opsi nanti: 'aktif', 'perbaikan', 'non-aktif'
            $table->string('status_operasional')->default('aktif');

            // Timestamp (created_at & updated_at)
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('tobong');
    }
};