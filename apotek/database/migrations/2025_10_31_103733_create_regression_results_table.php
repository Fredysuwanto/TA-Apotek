<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('regression_results', function (Blueprint $table) {
            $table->id();
            $table->string('nama_obat'); // Pastikan ini ada
            $table->text('equation')->comment('Persamaan regresi');
            $table->json('coefficients')->comment('Koefisien regresi');
            $table->json('calculations')->nullable()->comment('Perhitungan intermediate');
            $table->json('predictions')->nullable()->comment('Hasil prediksi');
            $table->json('metrics')->nullable();
            $table->timestamps();
            
            // Index untuk performa
            $table->index('nama_obat');
        });
    }

    public function down()
    {
        Schema::dropIfExists('regression_results');
    }
};