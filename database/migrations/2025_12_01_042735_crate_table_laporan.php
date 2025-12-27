<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('laporan', function (Blueprint $table) {
            $table->id('laporan_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('aktivitas_id');
            $table->string('jenis_laporan');
            $table->date('tanggal');
            $table->float('volume_sampah_masuk')->default(0);
            $table->float('volume_sampah_setelah_dibakar')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('aktivitas_id')->references('aktivitas_id')->on('aktivitas')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('laporan');
    }
};
