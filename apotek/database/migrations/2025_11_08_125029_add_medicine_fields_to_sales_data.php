<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales_data', function (Blueprint $table) {
            $table->string('nama_obat')->after('period')->nullable();
            $table->date('tanggal')->after('period')->nullable(); // Ganti period dengan tanggal
            $table->dropColumn('period'); // Hapus kolom period
        });
    }

    public function down()
    {
        Schema::table('sales_data', function (Blueprint $table) {
            $table->string('period')->after('id');
            $table->dropColumn(['nama_obat', 'tanggal']);
        });
    }
};