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
        Schema::table('financial_highlights', function (Blueprint $table) {
            $table->dropColumn('kpmm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_highlights', function (Blueprint $table) {
            $table->decimal('kpmm', 8, 4)->nullable()->after('cash_ratio');
        });
    }
};
