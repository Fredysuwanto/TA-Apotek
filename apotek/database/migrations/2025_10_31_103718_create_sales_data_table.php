<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_data', function (Blueprint $table) {
            $table->id();
            $table->string('tanggal');
                $table->string('nama_obat'); // ✅ ADD THIS  
            $table->decimal('x1', 10, 2)->comment('Variable independen 1');
            $table->decimal('x2', 10, 1)->comment('Variable independen 2');
            $table->decimal('y', 10, 0)->comment('Variable dependen (penjualan)');
            $table->timestamps();
                $table->index(['nama_obat', 'tanggal']); // ✅ Add index for performance

        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_data');
    }
};