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
            $table->string('period_month', 2)->nullable()->after('id'); // 01-12
            $table->string('period_year', 4)->nullable()->after('period_month'); // 2024, 2025, etc
            $table->index(['period_year', 'period_month']); // Index untuk performa query
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembiayaans', function (Blueprint $table) {
            $table->dropIndex(['period_year', 'period_month']);
            $table->dropColumn(['period_month', 'period_year']);
        });
    }
};
