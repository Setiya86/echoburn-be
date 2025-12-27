<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('aktivitas', function (Blueprint $table) {
            $table->id('aktivitas_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pelanggan_id')->nullable();
            $table->unsignedBigInteger('tobong_id');
            $table->float('jumlah_kg')->default(0);
            $table->string('status_proses')->default('pending');
            $table->timestamp('waktu_pencatatan')->useCurrent();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('pelanggan_id')->references('pelanggan_id')->on('pelanggan')->onDelete('set null');
            $table->foreign('tobong_id')->references('tobong_id')->on('tobong')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('aktivitas');
    }
};
