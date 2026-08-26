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
               $table->index(['period_year', 'period_month', 'tgleff'], 'idx_pembiayaans_period_tgleff');
               $table->index(['period_year', 'period_month', 'angs_ke', 'jw'], 'idx_pembiayaans_period_angs_jw');
          });

          Schema::table('tabungans', function (Blueprint $table) {
               $table->index(['period_year', 'period_month', 'tgltrnakh'], 'idx_tabungans_period_tgltrnakh');
          });

          Schema::table('depositos', function (Blueprint $table) {
               $table->index('nobilyet', 'idx_depositos_nobilyet');
               $table->index(['nobilyet', 'period_year', 'period_month'], 'idx_depositos_nobilyet_period');
               $table->index(['period_year', 'period_month', 'tglbuka'], 'idx_depositos_period_tglbuka');
          });
     }

     /**
      * Reverse the migrations.
      */
     public function down(): void
     {
          Schema::table('pembiayaans', function (Blueprint $table) {
               $table->dropIndex('idx_pembiayaans_period_tgleff');
               $table->dropIndex('idx_pembiayaans_period_angs_jw');
          });

          Schema::table('tabungans', function (Blueprint $table) {
               $table->dropIndex('idx_tabungans_period_tgltrnakh');
          });

          Schema::table('depositos', function (Blueprint $table) {
               $table->dropIndex('idx_depositos_nobilyet');
               $table->dropIndex('idx_depositos_nobilyet_period');
               $table->dropIndex('idx_depositos_period_tglbuka');
          });
     }
};
