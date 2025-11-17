<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposito extends Model
{
    protected $fillable = [
        'nodep',
        'nocif',
        'nobilyet',
        'nama',
        'nomrp',
        'tax',
        'bnghtg',
        'nisbahrp',
        'nisbah',
        'spread',
        'equivrate',
        'komitrate',
        'kdprd',
        'jkwaktu',
        'jnsjkwaktu',
        'aro',
        'stsrec',
        'ststrn',
        'stspep',
        'kdrisk',
        'stskait',
        'golcustbi',
        'tglbuka',
        'tgleff',
        'tgljtempo',
        'tgllhr',
        'kdwil',
        'kodeaoh',
        'kodeaop',
        'alamat',
        'kota',
        'kelurahan',
        'kecamatan',
        'kdpos',
        'noid',
        'telprmh',
        'hp',
        'nmibu',
        'noacbng',
        'tambahnom',
        'ketsandi',
        'namapt',
        'period_month',
        'period_year',
    ];

    protected $casts = [
        'nomrp' => 'decimal:2',
        'tax' => 'decimal:2',
        'bnghtg' => 'decimal:2',
        'nisbahrp' => 'decimal:2',
        'nisbah' => 'decimal:2',
        'spread' => 'decimal:2',
        'equivrate' => 'decimal:3',
        'komitrate' => 'decimal:3',
        'tglbuka' => 'date',
        'tgleff' => 'date',
        'tgljtempo' => 'date',
        'tgllhr' => 'date',
    ];
}
