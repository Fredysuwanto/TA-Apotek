<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales_data', function (Blueprint $table) {
            $table->string('satuan')->nullable()->after('nama_obat');
        });
    }

    public function down()
    {
        Schema::table('sales_data', function (Blueprint $table) {
            $table->dropColumn('satuan');
        });
    }
};
