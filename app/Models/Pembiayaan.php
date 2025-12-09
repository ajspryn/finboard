<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;

class Pembiayaan extends Model
{
    use Searchable;
    protected $fillable = [
        'period_month',
        'period_year',
        'nokontrak',
        'nama',
        'tgleff',
        'jw',
        'tglexp',
        'mdlawal',
        'mgnawal',
        'osmdlc',
        'osmgnc',
        'colbaru',
        'kdaoh',
        'acpok',
        'angsmdl',
        'angsmgn',
        'alamat',
        'telprmh',
        'hp',
        'fnama',
        'sahirrp',
        'tgkpok',
        'tgkmgn',
        'tgkdnd',
        'blntgkpok',
        'blntgkmgn',
        'blntgkdnd',
        'kdkolek',
        'kdgroupdeb',
        'kdgroupdana',
        'haritgkmdl',
        'haritgkmgn',
        'nocif',
        'kdprd',
        'pokpby',
        'kdloc',
        'kelurahan',
        'kecamatan',
        'kota',
        'nmao',
        'colllanjut',
        'tgkharilanjut',
        'angs_ke',
        'angske_x',
        'kdmco',
        'kdsektor',
        'kdsub',
        'plafon',
    ];

    protected $casts = [
        'tgleff' => 'date',
        'tglexp' => 'date',
        'mdlawal' => 'decimal:2',
        'mgnawal' => 'decimal:2',
        'osmdlc' => 'decimal:2',
        'osmgnc' => 'decimal:2',
        'sahirrp' => 'decimal:2',
        'tgkpok' => 'decimal:2',
        'tgkmgn' => 'decimal:2',
        'tgkdnd' => 'decimal:2',
        'angsmdl' => 'decimal:2',
        'angsmgn' => 'decimal:2',
        'plafon' => 'decimal:2',
    ];

    /**
     * Get the fields that should be searched
     */
    protected function getSearchFields(): array
    {
        return [
            'nokontrak',
            'nama',
            'nmao',
            'alamat',
            'fnama',
            'nocif',
        ];
    }
}
