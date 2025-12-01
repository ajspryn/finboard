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
        Schema::create('financial_highlights', function (Blueprint $table) {
            $table->id();
            $table->integer('period_year');
            $table->integer('period_month');
            $table->decimal('car', 8, 4)->nullable(); // Capital Adequacy Ratio (%)
            $table->decimal('roa', 8, 4)->nullable(); // Return on Assets (%)
            $table->decimal('roe', 8, 4)->nullable(); // Return on Equity (%)
            $table->decimal('aset', 20, 2)->nullable(); // Total Assets
            $table->decimal('pembiayaan', 20, 2)->nullable(); // Financing/Loans
            $table->decimal('laba_rugi', 20, 2)->nullable(); // Profit/Loss
            $table->decimal('dpk', 20, 2)->nullable(); // Dana Pihak Ketiga (Third Party Funds)
            $table->decimal('fdr', 8, 4)->nullable(); // Financing to Deposit Ratio (%)
            $table->decimal('npf', 8, 4)->nullable(); // Non-Performing Financing (%)
            $table->decimal('bopo', 8, 4)->nullable(); // Biaya Operasional terhadap Pendapatan Operasional (%)
            $table->timestamps();

            // Unique constraint untuk periode
            $table->unique(['period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_highlights');
    }
};
