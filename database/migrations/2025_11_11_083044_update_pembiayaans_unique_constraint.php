<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pembiayaans', function (Blueprint $table) {
            // Drop unique constraint dari nokontrak saja
            $table->dropUnique(['nokontrak']);

            // Buat unique constraint untuk kombinasi nokontrak + period
            $table->unique(['nokontrak', 'period_year', 'period_month'], 'pembiayaans_nokontrak_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembiayaans', function (Blueprint $table) {
            // Drop unique constraint kombinasi
            $table->dropUnique('pembiayaans_nokontrak_period_unique');

            // Kembalikan unique constraint ke nokontrak saja
            $table->unique('nokontrak');
        });
    }
};
