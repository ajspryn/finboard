<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
     {
          Schema::create('financial_metric_predictions', function (Blueprint $table) {
               $table->id();

               $table->string('metric_key', 64);
               $table->unsignedSmallInteger('period_year');
               $table->unsignedTinyInteger('period_month');

               // Use high precision to support both large currency values and ratios.
               $table->decimal('predicted_value', 24, 6);

               $table->string('model_name', 32)->default('svr');
               $table->decimal('r2', 10, 6)->nullable();
               $table->decimal('mape', 10, 6)->nullable();

               $table->unsignedInteger('train_size')->nullable();
               $table->unsignedInteger('test_size')->nullable();

               $table->unsignedSmallInteger('train_end_year')->nullable();
               $table->unsignedTinyInteger('train_end_month')->nullable();

               $table->json('details')->nullable();

               $table->timestamps();

               $table->unique(['metric_key', 'period_year', 'period_month', 'model_name'], 'metric_period_model_unique');
               $table->index(['metric_key', 'period_year', 'period_month'], 'metric_period_idx');
          });
     }

     public function down(): void
     {
          Schema::dropIfExists('financial_metric_predictions');
     }
};
