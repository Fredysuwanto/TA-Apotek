<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales_data', function (Blueprint $table) {
            $table->decimal('x2', 10, 1)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('sales_data', function (Blueprint $table) {
            $table->decimal('x2', 10, 1)->nullable(false)->change();
        });
    }
};
