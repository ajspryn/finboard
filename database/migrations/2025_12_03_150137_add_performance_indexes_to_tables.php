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
        // Add indexes to pembiayaans table for better query performance
        Schema::table('pembiayaans', function (Blueprint $table) {
            // Composite index for period queries (most frequent)
            $table->index(['period_year', 'period_month'], 'pembiayaans_period_idx');

            // Index for NPF calculations (colbaru filtering)
            $table->index(['period_year', 'period_month', 'colbaru'], 'pembiayaans_npf_idx');

            // Index for AO performance queries
            $table->index(['nmao', 'period_year', 'period_month'], 'pembiayaans_ao_idx');

            // Index for product analysis
            $table->index(['kdprd', 'period_year', 'period_month'], 'pembiayaans_product_idx');

            // Index for location-based queries
            $table->index(['kecamatan', 'period_year', 'period_month'], 'pembiayaans_location_idx');
        });

        // Add additional indexes to tabungans table
        Schema::table('tabungans', function (Blueprint $table) {
            // Index for product analysis
            $table->index(['kodeprd', 'period_year', 'period_month'], 'tabungans_product_idx');

            // Index for location queries
            $table->index(['kodeloc', 'period_year', 'period_month'], 'tabungans_location_idx');

            // Index for active accounts only
            $table->index(['stsrec', 'period_year', 'period_month'], 'tabungans_active_idx');
        });

        // Add additional indexes to depositos table
        Schema::table('depositos', function (Blueprint $table) {
            // Index for AO performance
            $table->index(['kodeaoh', 'period_year', 'period_month'], 'depositos_ao_idx');

            // Index for product analysis
            $table->index(['kdprd', 'period_year', 'period_month'], 'depositos_product_idx');

            // Index for maturity analysis
            $table->index(['tgljtempo', 'period_year', 'period_month'], 'depositos_maturity_idx');

            // Index for active deposits
            $table->index(['stsrec', 'period_year', 'period_month'], 'depositos_active_idx');

            // Index for ABP exclusion (kdprd != '41')
            $table->index(['kdprd', 'period_year', 'period_month'], 'depositos_abp_filter_idx');
        });

        // Add additional indexes to financial_highlights table
        Schema::table('financial_highlights', function (Blueprint $table) {
            // Index for time-series queries
            $table->index(['period_year', 'period_month'], 'financial_highlights_period_idx');

            // Partial indexes for frequently queried fields
            $table->index(['period_year', 'period_month', 'npf'], 'financial_highlights_npf_idx');
            $table->index(['period_year', 'period_month', 'car'], 'financial_highlights_car_idx');
            $table->index(['period_year', 'period_month', 'roa'], 'financial_highlights_roa_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from pembiayaans table
        Schema::table('pembiayaans', function (Blueprint $table) {
            $table->dropIndex('pembiayaans_period_idx');
            $table->dropIndex('pembiayaans_npf_idx');
            $table->dropIndex('pembiayaans_ao_idx');
            $table->dropIndex('pembiayaans_product_idx');
            $table->dropIndex('pembiayaans_location_idx');
        });

        // Drop indexes from tabungans table
        Schema::table('tabungans', function (Blueprint $table) {
            $table->dropIndex('tabungans_product_idx');
            $table->dropIndex('tabungans_location_idx');
            $table->dropIndex('tabungans_active_idx');
        });

        // Drop indexes from depositos table
        Schema::table('depositos', function (Blueprint $table) {
            $table->dropIndex('depositos_ao_idx');
            $table->dropIndex('depositos_product_idx');
            $table->dropIndex('depositos_maturity_idx');
            $table->dropIndex('depositos_active_idx');
            $table->dropIndex('depositos_abp_filter_idx');
        });

        // Drop indexes from financial_highlights table
        Schema::table('financial_highlights', function (Blueprint $table) {
            $table->dropIndex('financial_highlights_period_idx');
            $table->dropIndex('financial_highlights_npf_idx');
            $table->dropIndex('financial_highlights_car_idx');
            $table->dropIndex('financial_highlights_roa_idx');
        });
    }
};
