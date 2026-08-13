<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$productMapping = [
    '02' => 'TABUNGAN BERIMAN',
    '04' => 'TABUNGAN BERIMAN GAYATRI',
    '05' => 'TABUNGAN BERIMAN PEGAWAI',
    '10' => 'Tabungan Simpanan Pelajar',
    '11' => 'Tabungan Simpanan Masyarakat',
    '12' => 'Tabungan Haji',
    '13' => 'Tabungan Umum',
    '14' => 'Tabungan Berjangka',
    '15' => 'Tabungan SiMuda',
    '16' => 'Tabungan SiDewasa',
    '17' => 'Tabungan SiAnak',
    '18' => 'Tabungan SiPintar',
    '19' => 'Tabungan SiCerdas',
    '20' => 'Tabungan SiBijak',
    '21' => 'TABUNGAN TEGAR',
    '22' => 'TABUNGAN SIMPANAN PELAJAR',
    '25' => 'TABUNGAN PASAR',
    '50' => 'TAB BANSOS BUPATI BOGOR',
];

function normalizePhone(?string $value): string
{
    if ($value === null) {
        return '';
    }

    $digits = preg_replace('/\D+/', '', trim($value));
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '62')) {
        return $digits;
    }

    if (str_starts_with($digits, '0')) {
        return '62' . substr($digits, 1);
    }

    if (str_starts_with($digits, '8')) {
        return '62' . $digits;
    }

    return $digits;
}

$userEmailByPhone = [];
if (DB::getSchemaBuilder()->hasTable('users')) {
    DB::table('users')
        ->select(['whatsapp_number', 'email'])
        ->whereNotNull('whatsapp_number')
        ->where('whatsapp_number', '<>', '')
        ->orderBy('id')
        ->lazy(500)
        ->each(function ($user) use (&$userEmailByPhone): void {
            $normalized = normalizePhone($user->whatsapp_number);
            if ($normalized !== '' && !isset($userEmailByPhone[$normalized])) {
                $userEmailByPhone[$normalized] = trim((string) ($user->email ?? ''));
            }
        });
}

$depositoCifMap = [];
DB::table('depositos')
    ->select('nocif')
    ->whereNotNull('nocif')
    ->where('nocif', '<>', '')
    ->distinct()
    ->orderBy('nocif')
    ->lazy(5000)
    ->each(function ($row) use (&$depositoCifMap): void {
        $nocif = trim((string) ($row->nocif ?? ''));
        if ($nocif !== '') {
            $depositoCifMap[$nocif] = true;
        }
    });

$pembiayaanCifMap = [];
DB::table('pembiayaans')
    ->select('nocif')
    ->whereNotNull('nocif')
    ->where('nocif', '<>', '')
    ->distinct()
    ->orderBy('nocif')
    ->lazy(5000)
    ->each(function ($row) use (&$pembiayaanCifMap): void {
        $nocif = trim((string) ($row->nocif ?? ''));
        if ($nocif !== '') {
            $pembiayaanCifMap[$nocif] = true;
        }
    });

$outPath = __DIR__ . '/../storage/app/public/tabungan_crm_export.csv';
$handle = fopen($outPath, 'w');

if ($handle === false) {
    fwrite(STDERR, "Gagal membuat file output\n");
    exit(1);
}

$header = [
    'wa_phone',
    'nama_nasabah',
    'email',
    'produk_tabungan',
    'nomor_rekening',
    'nomor_cif',
    'saldo_akhir',
    'status_record',
    'tgl_transaksi_akhir',
    'nomor_identitas',
    'kota',
    'memiliki_deposito',
    'memiliki_pembiayaan',
    'periode',
];
fputcsv($handle, $header);

$written = 0;
$seenNasabah = [];

$query = DB::table('tabungans')
    ->select([
        'id',
        'hp',
        'fnama',
        'kodeprd',
        'notab',
        'nocif',
        'sahirrp',
        'stsrec',
        'tgltrnakh',
        'noid',
        'kodeloc',
        'period_month',
        'period_year',
    ])
    ->whereNotNull('hp')
    ->where('hp', '<>', '')
    ->whereRaw('TRIM(hp) <> ""')
    ->orderBy('nocif')
    ->orderByDesc('period_year')
    ->orderByDesc('period_month')
    ->orderByDesc('id');

foreach ($query->lazy(1000) as $row) {
    $waPhone = normalizePhone($row->hp);
    if ($waPhone === '') {
        continue;
    }

    $nocif = trim((string) ($row->nocif ?? ''));
    $seenKey = $nocif !== '' ? 'nocif:' . $nocif : 'phone:' . $waPhone;
    if (isset($seenNasabah[$seenKey])) {
        continue;
    }
    $seenNasabah[$seenKey] = true;

    $produk = $productMapping[$row->kodeprd] ?? ('Tabungan ' . ($row->kodeprd ?? ''));
    $periode = trim(($row->period_year ?? '') . '-' . ($row->period_month ?? ''));
    $tgl = $row->tgltrnakh ? date('Y-m-d', strtotime($row->tgltrnakh)) : '';
    $email = $userEmailByPhone[$waPhone] ?? '';
    $hasDeposito = ($nocif !== '' && isset($depositoCifMap[$nocif])) ? 'Ya' : 'Tidak';
    $hasPembiayaan = ($nocif !== '' && isset($pembiayaanCifMap[$nocif])) ? 'Ya' : 'Tidak';

    fputcsv($handle, [
        $waPhone,
        trim((string) ($row->fnama ?? '')),
        $email,
        $produk,
        $row->notab ?? '',
        $row->nocif ?? '',
        number_format((float) ($row->sahirrp ?? 0), 2, '.', ''),
        $row->stsrec ?? '',
        $tgl,
        $row->noid ?? '',
        $row->kodeloc ?? '',
        $hasDeposito,
        $hasPembiayaan,
        $periode,
    ]);
    $written++;
}

fclose($handle);

echo "Export selesai: {$written} baris -> {$outPath}\n";
