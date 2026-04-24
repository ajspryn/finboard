<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
     public function up(): void
     {
          Schema::create('macro_indicators', function (Blueprint $table) {
               $table->id();
               $table->unsignedSmallInteger('period_year');
               $table->unsignedTinyInteger('period_month');

               // Percent values (e.g. 6.2500)
               $table->decimal('bi_rate', 8, 4)->nullable();
               $table->decimal('inflation_yoy', 8, 4)->nullable();

               $table->string('source', 32)->default('FRED');
               $table->json('source_details')->nullable();
               $table->timestamp('fetched_at')->nullable();

               $table->timestamps();

               $table->unique(['period_year', 'period_month'], 'macro_indicators_period_unique');
               $table->index(['period_year', 'period_month'], 'macro_indicators_period_idx');
          });
     }

     public function down(): void
     {
          Schema::dropIfExists('macro_indicators');
     }
};
