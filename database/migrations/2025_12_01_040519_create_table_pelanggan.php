<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id('pelanggan_id');
            
            // Data Dasar
            $table->string('nama_lengkap');
            $table->string('alamat')->nullable();
            $table->string('nomor_telepon')->nullable();
            $table->string('email')->nullable()->unique(); // Email dibuat unique (opsional)

            // Data Statistik & Status
            $table->integer('jumlah_sampah_sudah_dibakar')->default(0)->comment('Total sampah (dalam satuan kg/ton)');
            $table->enum('status_pelanggan', ['aktif', 'masa_tenggang', 'nonaktif'])->default('aktif');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('pelanggan');
    }
};