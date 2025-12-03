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
        // Index for pembiayaans table - used in NPF calculations and period queries
        Schema::table('pembiayaans', function (Blueprint $table) {
            $table->index(['period_year', 'period_month'], 'idx_pembiayaans_period');
            $table->index('colbaru', 'idx_pembiayaans_colbaru');
            $table->index(['period_year', 'period_month', 'colbaru'], 'idx_pembiayaans_period_colbaru');
        });

        // Index for tabungans table - used in funding calculations
        Schema::table('tabungans', function (Blueprint $table) {
            $table->index(['period_year', 'period_month'], 'idx_tabungans_period');
        });

        // Index for depositos table - used in funding calculations
        Schema::table('depositos', function (Blueprint $table) {
            $table->index(['period_year', 'period_month'], 'idx_depositos_period');
        });

        // Index for financial_highlights table - used in dashboard queries
        Schema::table('financial_highlights', function (Blueprint $table) {
            $table->index(['period_year', 'period_month'], 'idx_financial_highlights_period');
            $table->unique(['period_year', 'period_month'], 'unique_financial_highlights_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for pembiayaans table
        Schema::table('pembiayaans', function (Blueprint $table) {
            $table->dropIndex('idx_pembiayaans_period');
            $table->dropIndex('idx_pembiayaans_colbaru');
            $table->dropIndex('idx_pembiayaans_period_colbaru');
        });

        // Drop indexes for tabungans table
        Schema::table('tabungans', function (Blueprint $table) {
            $table->dropIndex('idx_tabungans_period');
        });

        // Drop indexes for depositos table
        Schema::table('depositos', function (Blueprint $table) {
            $table->dropIndex('idx_depositos_period');
        });

        // Drop indexes for financial_highlights table
        Schema::table('financial_highlights', function (Blueprint $table) {
            $table->dropIndex('idx_financial_highlights_period');
            $table->dropUnique('unique_financial_highlights_period');
        });
    }
};
