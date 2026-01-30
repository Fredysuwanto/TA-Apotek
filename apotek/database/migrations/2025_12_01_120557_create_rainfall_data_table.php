<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rainfall_data', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->decimal('curahhujan', 10, 1);
            $table->timestamps();

            $table->unique('tanggal'); // satu data curah hujan per tanggal
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainfall_data');
    }
};
