<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MacroIndicator extends Model
{
     protected $table = 'macro_indicators';

     protected $fillable = [
          'period_year',
          'period_month',
          'bi_rate',
          'inflation_yoy',
          'source',
          'source_details',
          'fetched_at',
     ];

     protected $casts = [
          'bi_rate' => 'float',
          'inflation_yoy' => 'float',
          'source_details' => 'array',
          'fetched_at' => 'datetime',
     ];
}
