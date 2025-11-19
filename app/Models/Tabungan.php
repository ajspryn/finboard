<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tabungan extends Model
{
    protected $fillable = [
        'notab',
        'nocif',
        'kodeprd',
        'fnama',
        'namaqq',
        'sahirrp',
        'saldoblok',
        'tax',
        'avgeom',
        'linkage',
        'stsrec',
        'stsrest',
        'stspep',
        'kdrisk',
        'tgltrnakh',
        'tgllhr',
        'noid',
        'hp',
        'nmibu',
        'ketsandi',
        'namapt',
        'kodeloc',
        'period_month',
        'period_year',
    ];

    protected $casts = [
        'sahirrp' => 'decimal:2',
        'saldoblok' => 'decimal:2',
        'tax' => 'decimal:2',
        'avgeom' => 'decimal:2',
        'tgltrnakh' => 'date',
        'tgllhr' => 'date',
    ];
}
