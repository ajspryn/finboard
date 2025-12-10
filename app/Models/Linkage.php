<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;

class Linkage extends Model
{
    use Searchable;

    protected $fillable = [
        'nokontrak',
        'nocif',
        'nama',
        'tgleff',
        'tgljt',
        'kelompok',
        'jnsakad',
        'prsnisbah',
        'plafon',
        'os',
        'period_month',
        'period_year',
    ];

    protected $casts = [
        'tgleff' => 'date',
        'tgljt' => 'date',
        'prsnisbah' => 'decimal:2',
        'plafon' => 'decimal:2',
        'os' => 'decimal:2',
    ];
}
