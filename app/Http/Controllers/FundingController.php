<?php

namespace App\Http\Controllers;

use App\Models\Tabungan;
use App\Models\Deposito;
use App\Models\Pembiayaan;
use App\Models\Linkage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FundingController extends Controller
{
    public function index()
    {
        // Get last upload from all tables
        $lastUploadTabungan = Tabungan::max('updated_at');
        $lastUploadDeposito = Deposito::max('updated_at');
        $lastUploadLinkage = Linkage::max('updated_at');
        $lastUpload = max($lastUploadTabungan, $lastUploadDeposito, $lastUploadLinkage);

        // Get total data
        $totalTabungan = Tabungan::count();
        $totalDeposito = Deposito::count();
        $totalLinkage = Linkage::count();
        $totalData = $totalTabungan + $totalDeposito + $totalLinkage;

        // Get sum saldo
        $totalSaldoTabungan = Tabungan::sum('sahirrp');
        $totalSaldoDeposito = Deposito::sum('nomrp');
        $totalSaldoLinkage = Linkage::sum('os');

        // Get upload history with periods
        $uploadHistory = collect();

        // Get distinct periods from tabungan
        $tabunganPeriods = Tabungan::selectRaw("DISTINCT strftime('%Y', created_at) as year, strftime('%m', created_at) as month, COUNT(*) as count, SUM(sahirrp) as total_saldo, MAX(created_at) as last_upload")
            ->groupByRaw("strftime('%Y', created_at), strftime('%m', created_at)")
            ->orderByRaw("strftime('%Y', created_at) DESC, strftime('%m', created_at) DESC")
            ->get()
            ->map(function ($item) {
                return (object)[
                    'jenis' => 'TABUNGAN',
                    'year' => $item->year,
                    'month' => $item->month,
                    'count' => $item->count,
                    'total_saldo' => $item->total_saldo,
                    'last_upload' => $item->last_upload
                ];
            });

        // Get distinct periods from deposito
        $depositoPeriods = Deposito::selectRaw("DISTINCT strftime('%Y', created_at) as year, strftime('%m', created_at) as month, COUNT(*) as count, SUM(nomrp) as total_saldo, MAX(created_at) as last_upload")
            ->groupByRaw("strftime('%Y', created_at), strftime('%m', created_at)")
            ->orderByRaw("strftime('%Y', created_at) DESC, strftime('%m', created_at) DESC")
            ->get()
            ->map(function ($item) {
                return (object)[
                    'jenis' => 'DEPOSITO',
                    'year' => $item->year,
                    'month' => $item->month,
                    'count' => $item->count,
                    'total_saldo' => $item->total_saldo,
                    'last_upload' => $item->last_upload
                ];
            });

        // Get distinct periods from linkage
        $linkagePeriods = Linkage::selectRaw("DISTINCT strftime('%Y', created_at) as year, strftime('%m', created_at) as month, COUNT(*) as count, SUM(os) as total_saldo, MAX(created_at) as last_upload")
            ->groupByRaw("strftime('%Y', created_at), strftime('%m', created_at)")
            ->orderByRaw("strftime('%Y', created_at) DESC, strftime('%m', created_at) DESC")
            ->get()
            ->map(function ($item) {
                return (object)[
                    'jenis' => 'LINKAGE',
                    'year' => $item->year,
                    'month' => $item->month,
                    'count' => $item->count,
                    'total_saldo' => $item->total_saldo,
                    'last_upload' => $item->last_upload
                ];
            });

        $uploadHistory = $tabunganPeriods->merge($depositoPeriods)->merge($linkagePeriods)->sortByDesc(function ($item) {
            return $item->year * 100 + $item->month;
        })->take(10); // Show last 10 uploads

        // Build stats manually
        $stats = collect([
            (object)[
                'jenis' => 'TABUNGAN',
                'jumlah' => $totalTabungan,
                'total_saldo' => $totalSaldoTabungan
            ],
            (object)[
                'jenis' => 'DEPOSITO',
                'jumlah' => $totalDeposito,
                'total_saldo' => $totalSaldoDeposito
            ],
            (object)[
                'jenis' => 'LINKAGE',
                'jumlah' => $totalLinkage,
                'total_saldo' => $totalSaldoLinkage
            ]
        ]);

        return view('funding.index', compact('lastUpload', 'totalData', 'stats', 'uploadHistory'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'month' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12',
            'year' => 'required|digits:4|integer|min:2020|max:2030',
            'csv_tabungan' => 'required|file|mimes:csv,txt|max:10240',
            'csv_deposito' => 'required|file|mimes:csv,txt|max:10240',
            'csv_linkage' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $month = $request->input('month');
            $year = $request->input('year');

            $totalImported = 0;
            $totalUpdated = 0;
            $totalErrors = 0;
            $allErrorDetails = [];

            DB::beginTransaction();

            // Hapus data periode yang sama sebelum upload
            $deletedTabungan = Tabungan::where('period_month', $month)
                ->where('period_year', $year)
                ->delete();

            $deletedDeposito = Deposito::where('period_month', $month)
                ->where('period_year', $year)
                ->delete();

            $deletedLinkage = Linkage::where('period_month', $month)
                ->where('period_year', $year)
                ->delete();

            Log::info("Deleted existing data for period {$year}-{$month}: Tabungan: {$deletedTabungan}, Deposito: {$deletedDeposito}, Linkage: {$deletedLinkage}");

            // Process Tabungan
            $resultTabungan = $this->processCSV($request->file('csv_tabungan'), $month, $year, 'TABUNGAN');
            $totalImported += $resultTabungan['imported'];
            $totalUpdated += $resultTabungan['updated'];
            $totalErrors += $resultTabungan['errors'];
            $allErrorDetails = array_merge($allErrorDetails, $resultTabungan['errorDetails']);

            // Process Deposito
            $resultDeposito = $this->processCSV($request->file('csv_deposito'), $month, $year, 'DEPOSITO');
            $totalImported += $resultDeposito['imported'];
            $totalUpdated += $resultDeposito['updated'];
            $totalErrors += $resultDeposito['errors'];
            $allErrorDetails = array_merge($allErrorDetails, $resultDeposito['errorDetails']);

            // Process Linkage
            $resultLinkage = $this->processCSV($request->file('csv_linkage'), $month, $year, 'LINKAGE');
            $totalImported += $resultLinkage['imported'];
            $totalUpdated += $resultLinkage['updated'];
            $totalErrors += $resultLinkage['errors'];
            $allErrorDetails = array_merge($allErrorDetails, $resultLinkage['errorDetails']);

            DB::commit();

            $message = "Import berhasil untuk periode {$year}-{$month}!\n\n";
            if ($deletedTabungan > 0 || $deletedDeposito > 0 || $deletedLinkage > 0) {
                $message .= "🗑️  Data lama dihapus: Tabungan: {$deletedTabungan}, Deposito: {$deletedDeposito}, Linkage: {$deletedLinkage}\n\n";
            }
            $message .= "📊 TABUNGAN: Imported: {$resultTabungan['imported']}, Updated: {$resultTabungan['updated']}, Errors: {$resultTabungan['errors']}\n";
            $message .= "📊 DEPOSITO: Imported: {$resultDeposito['imported']}, Updated: {$resultDeposito['updated']}, Errors: {$resultDeposito['errors']}\n";
            $message .= "📊 LINKAGE: Imported: {$resultLinkage['imported']}, Updated: {$resultLinkage['updated']}, Errors: {$resultLinkage['errors']}\n";
            $message .= "📈 TOTAL: Imported: {$totalImported}, Updated: {$totalUpdated}, Errors: {$totalErrors}";

            if ($totalErrors > 0 && count($allErrorDetails) > 0) {
                $message .= "\n\n⚠️ Detail error (10 pertama):\n" . implode("\n", array_slice($allErrorDetails, 0, 10));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing funding CSV: ' . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }



    private function processCSV($file, $month, $year, $jenis, $sumberDana = null)
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \Exception("Tidak dapat membuka file CSV {$jenis}");
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');

        if ($header === false || empty($header)) {
            throw new \Exception("File CSV {$jenis} tidak memiliki header yang valid");
        }

        $imported = 0;
        $updated = 0;
        $errors = 0;
        $errorDetails = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $lineNumber++;

            if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                continue;
            }

            if (count($row) < count($header)) {
                $errors++;
                $errorDetails[] = "{$jenis} Baris {$lineNumber}: Jumlah kolom tidak sesuai";
                continue;
            }

            while (count($row) < count($header)) {
                $row[] = '';
            }

            $row = array_slice($row, 0, count($header));
            $data = array_combine($header, $row);

            // Validasi nomor rekening berdasarkan jenis
            $norek = '';
            if ($jenis === 'TABUNGAN') {
                if (empty($data['notab']) || trim($data['notab']) === '') {
                    $errors++;
                    $errorDetails[] = "{$jenis} Baris {$lineNumber}: Nomor tabungan kosong";
                    continue;
                }
                $norek = trim($data['notab']);
            } elseif ($jenis === 'DEPOSITO') {
                if (empty($data['nodep']) || trim($data['nodep']) === '') {
                    $errors++;
                    $errorDetails[] = "{$jenis} Baris {$lineNumber}: Nomor deposito kosong";
                    continue;
                }
                $norek = trim($data['nodep']);
            } elseif ($jenis === 'PEMBIAYAAN') {
                if (empty($data['nokontrak']) || trim($data['nokontrak']) === '') {
                    $errors++;
                    $errorDetails[] = "{$jenis} Baris {$lineNumber}: Nomor kontrak kosong";
                    continue;
                }
                $norek = trim($data['nokontrak']);
            } elseif ($jenis === 'LINKAGE') {
                if (empty($data['nokontrak']) || trim($data['nokontrak']) === '') {
                    $errors++;
                    $errorDetails[] = "{$jenis} Baris {$lineNumber}: Nomor kontrak kosong";
                    continue;
                }
                $norek = trim($data['nokontrak']);
            }

            try {
                // Parse dates - berbeda untuk tabungan dan deposito
                $tglbuka = !empty($data['tglbuka']) ? $this->parseDate($data['tglbuka']) : null;
                $tgleff = !empty($data['tgleff']) ? $this->parseDate($data['tgleff']) : null;
                $tgljtempo = !empty($data['tgljtempo']) ? $this->parseDate($data['tgljtempo']) : null;
                $tgltrnakh = !empty($data['tgltrnakh']) ? $this->parseDate($data['tgltrnakh']) : null;
                $tgllhr = !empty($data['tgllhr']) ? $this->parseDate($data['tgllhr']) : null;

                $fundingData = [];

                // Data spesifik untuk TABUNGAN
                if ($jenis === 'TABUNGAN') {
                    $fundingData = [
                        'notab' => $norek,
                        'nocif' => $data['nocif'] ?? null,
                        'kodeprd' => $data['kodeprd'] ?? null,
                        'sahirrp' => $this->parseNumeric($data['sahirrp'] ?? 0),
                        'saldoblok' => $this->parseNumeric($data['saldoblok'] ?? 0),
                        'fnama' => $data['fnama'] ?? null,
                        'namaqq' => $data['namaqq'] ?? null,
                        'stsrec' => $data['stsrec'] ?? null,
                        'stsrest' => $data['stsrest'] ?? null,
                        'tax' => $this->parseNumeric($data['tax'] ?? 0),
                        'tgltrnakh' => $tgltrnakh,
                        'avgeom' => $this->parseNumeric($data['avgeom'] ?? 0),
                        'linkage' => $this->parseNumeric($data['linkage'] ?? 0),
                        'stspep' => $data['stspep'] ?? null,
                        'kdrisk' => $data['kdrisk'] ?? null,
                        'noid' => $data['noid'] ?? null,
                        'hp' => $data['hp'] ?? null,
                        'tgllhr' => $tgllhr,
                        'nmibu' => $data['nmibu'] ?? null,
                        'ketsandi' => $data['ketsandi'] ?? null,
                        'namapt' => $data['namapt'] ?? null,
                        'kodeloc' => $data['kodeloc'] ?? null,
                        'period_month' => $month,
                        'period_year' => $year,
                    ];
                }

                // Data spesifik untuk DEPOSITO
                if ($jenis === 'DEPOSITO') {
                    $fundingData = [
                        'nodep' => $norek,
                        'nocif' => $data['nocif'] ?? null,
                        'nobilyet' => $data['nobilyet'] ?? null,
                        'nama' => $data['nama'] ?? null,
                        'nomrp' => $this->parseNumeric($data['nomrp'] ?? 0),
                        'stsrec' => $data['stsrec'] ?? null,
                        'kdprd' => $data['kdprd'] ?? null,
                        'jkwaktu' => $data['jkwaktu'] ?? null,
                        'jnsjkwaktu' => $data['jnsjkwaktu'] ?? null,
                        'tglbuka' => $tglbuka,
                        'tgleff' => $tgleff,
                        'tgljtempo' => $tgljtempo,
                        'aro' => $data['aro'] ?? null,
                        'nisbah' => $this->parseNumeric($data['nisbah'] ?? 0),
                        'spread' => $this->parseNumeric($data['spread'] ?? 0),
                        'equivrate' => $this->parseNumeric($data['equivrate'] ?? 0),
                        'komitrate' => $this->parseNumeric($data['komitrate'] ?? 0),
                        'ststrn' => $data['ststrn'] ?? null,
                        'kdwil' => $data['kdwil'] ?? null,
                        'kodeaoh' => $data['kodeaoh'] ?? null,
                        'kodeaop' => $data['kodeaop'] ?? null,
                        'noacbng' => $data['noacbng'] ?? null,
                        'tambahnom' => $data['tambahnom'] ?? null,
                        'noid' => $data['noid'] ?? null,
                        'alamat' => $data['alamat'] ?? null,
                        'kota' => $data['kota'] ?? null,
                        'telprmh' => $data['telprmh'] ?? null,
                        'hp' => $data['hp'] ?? null,
                        'stskait' => $data['stskait'] ?? null,
                        'golcustbi' => $data['golcustbi'] ?? null,
                        'kelurahan' => $data['kelurahan'] ?? null,
                        'kecamatan' => $data['kecamatan'] ?? null,
                        'kdpos' => $data['kdpos'] ?? null,
                        'kdrisk' => $data['kdrisk'] ?? null,
                        'tax' => $this->parseNumeric($data['tax'] ?? 0),
                        'bnghtg' => $this->parseNumeric($data['bnghtg'] ?? 0),
                        'nisbahrp' => $this->parseNumeric($data['nisbahrp'] ?? 0),
                        'linkage' => $this->parseNumeric($data['linkage'] ?? 0),
                        'stspep' => $data['stspep'] ?? null,
                        'tgllhr' => $tgllhr,
                        'nmibu' => $data['nmibu'] ?? null,
                        'ketsandi' => $data['ketsandi'] ?? null,
                        'namapt' => $data['namapt'] ?? null,
                        'period_month' => $month,
                        'period_year' => $year,
                    ];
                }

                // Data spesifik untuk PEMBIAYAAN
                if ($jenis === 'PEMBIAYAAN') {
                    $fundingData = [
                        'nokontrak' => $norek,
                        'nocif' => $data['nocif'] ?? null,
                        'nama' => $data['nama'] ?? null,
                        'tgleff' => $tgleff,
                        'tglexp' => $this->parseDate($data['tgljt'] ?? null),
                        'kelompok' => $data['kelompok'] ?? null,
                        'jnsakad' => $data['jnsakad'] ?? null,
                        'prsnisbah' => $this->parseNumeric($data['prsnisbah'] ?? 0),
                        'plafon' => $this->parseNumeric($data['plafon'] ?? 0),
                        'osmdlc' => $this->parseNumeric($data['os'] ?? 0),
                        'linkage' => $this->parseNumeric($data['linkage'] ?? 0),
                        'period_month' => $month,
                        'period_year' => $year,
                    ];
                }

                // Data spesifik untuk LINKAGE
                if ($jenis === 'LINKAGE') {
                    $fundingData = [
                        'nokontrak' => $norek,
                        'nocif' => $data['nocif'] ?? null,
                        'nama' => $data['nama'] ?? null,
                        'tgleff' => $tgleff,
                        'tgljt' => $this->parseDate($data['tgljt'] ?? null),
                        'kelompok' => $data['kelompok'] ?? null,
                        'jnsakad' => $data['jnsakad'] ?? null,
                        'prsnisbah' => $this->parseNumeric($data['prsnisbah'] ?? 0),
                        'plafon' => $this->parseNumeric($data['plafon'] ?? 0),
                        'os' => $this->parseNumeric($data['os'] ?? 0),
                        'period_month' => $month,
                        'period_year' => $year,
                    ];
                }

                // Save ke tabel yang sesuai
                if ($jenis === 'TABUNGAN') {
                    Tabungan::create($fundingData);
                    $imported++;
                } elseif ($jenis === 'DEPOSITO') {
                    Deposito::create($fundingData);
                    $imported++;
                } elseif ($jenis === 'PEMBIAYAAN') {
                    Pembiayaan::create($fundingData);
                    $imported++;
                } elseif ($jenis === 'LINKAGE') {
                    Linkage::create($fundingData);
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors++;
                $errorDetails[] = "{$jenis} Baris {$lineNumber}: " . $e->getMessage();
                Log::error("Error importing {$jenis} line {$lineNumber}: " . $e->getMessage());
            }
        }

        fclose($handle);

        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
            'errorDetails' => $errorDetails
        ];
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function parseNumeric($value)
    {
        if (empty($value)) {
            return 0;
        }

        $value = str_replace([',', ' '], '', $value);
        return floatval($value);
    }
}
