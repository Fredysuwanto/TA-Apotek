<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('regression_results', function (Blueprint $table) {
        if (!Schema::hasColumn('regression_results', 'metrics')) {
        $table->json('metrics')->nullable(); // simpan mse, rmse, mape, r2
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regression_results', function (Blueprint $table) {
            //
        });
    }
};
