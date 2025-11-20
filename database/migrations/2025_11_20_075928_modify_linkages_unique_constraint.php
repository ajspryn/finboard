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
        Schema::table('linkages', function (Blueprint $table) {
            // Drop the existing unique constraint on nokontrak
            $table->dropUnique(['nokontrak']);

            // Add composite unique constraint on nokontrak, period_year, period_month
            $table->unique(['nokontrak', 'period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('linkages', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['nokontrak', 'period_year', 'period_month']);

            // Restore the original unique constraint on nokontrak
            $table->unique(['nokontrak']);
        });
    }
};
