<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pembakaran', function (Blueprint $table) {
            $table->id('pembakaran_id');
            $table->unsignedBigInteger('aktivitas_id');
            $table->enum('jenis', ['tempat', 'langganan']);
            $table->integer('total_biaya')->nullable();
            $table->string('jadwal_langganan')->nullable();
            $table->timestamps();

            $table->foreign('aktivitas_id')->references('aktivitas_id')->on('aktivitas')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('pembakaran');
    }
};
