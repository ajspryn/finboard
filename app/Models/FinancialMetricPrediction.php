<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialMetricPrediction extends Model
{
     protected $fillable = [
          'metric_key',
          'period_year',
          'period_month',
          'predicted_value',
          'model_name',
          'r2',
          'mape',
          'train_size',
          'test_size',
          'train_end_year',
          'train_end_month',
          'details',
     ];

     protected $casts = [
          'period_year' => 'integer',
          'period_month' => 'integer',
          'predicted_value' => 'decimal:6',
          'r2' => 'decimal:6',
          'mape' => 'decimal:6',
          'train_size' => 'integer',
          'test_size' => 'integer',
          'train_end_year' => 'integer',
          'train_end_month' => 'integer',
          'details' => 'array',
     ];
}
