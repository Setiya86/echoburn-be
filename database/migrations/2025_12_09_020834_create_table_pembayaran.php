<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('pembayaran_id');
            
            // PERBAIKAN: Harus nullable untuk mencatat transaksi non-anggota (sekali bakar)
            $table->unsignedBigInteger('pelanggan_id')->nullable(); 
            
            // Foreign key menggunakan onDelete('set null') agar record pembayaran tetap ada jika pelanggan dihapus
            $table->foreign('pelanggan_id')->references('pelanggan_id')->on('pelanggan')->onDelete('set null');

            $table->enum('sumber_pemasukan', ['pendaftaran', 'perpanjang', 'ditempat']);
            $table->enum('status_pembayaran', ['lunas', 'pending', 'batal'])->default('lunas');

            $table->decimal('jumlah_bayar', 15, 2);
            $table->dateTime('tanggal_pembayaran');
            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('pembayaran');
    }
};