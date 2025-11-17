<?php

namespace App\Http\Controllers;

use App\Models\Tabungan;
use App\Models\Deposito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FundingController extends Controller
{
    public function index()
    {
        // Get last upload from both tables
        $lastUploadTabungan = Tabungan::max('updated_at');
        $lastUploadDeposito = Deposito::max('updated_at');
        $lastUpload = max($lastUploadTabungan, $lastUploadDeposito);

        // Get total data
        $totalTabungan = Tabungan::count();
        $totalDeposito = Deposito::count();
        $totalData = $totalTabungan + $totalDeposito;

        // Get sum saldo
        $totalSaldoTabungan = Tabungan::sum('sahirrp');
        $totalSaldoDeposito = Deposito::sum('nomrp');

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
            ]
        ]);

        return view('funding.index', compact('lastUpload', 'totalData', 'stats'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'month' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12',
            'year' => 'required|digits:4|integer|min:2020|max:2030',
            'csv_tabungan' => 'required|file|mimes:csv,txt|max:10240',
            'csv_deposito' => 'required|file|mimes:csv,txt|max:10240',
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

            Log::info("Deleted existing data for period {$year}-{$month}: Tabungan: {$deletedTabungan}, Deposito: {$deletedDeposito}");

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

            DB::commit();

            $message = "Import berhasil untuk periode {$year}-{$month}!\n\n";
            if ($deletedTabungan > 0 || $deletedDeposito > 0) {
                $message .= "🗑️  Data lama dihapus: Tabungan: {$deletedTabungan}, Deposito: {$deletedDeposito}\n\n";
            }
            $message .= "📊 TABUNGAN: Imported: {$resultTabungan['imported']}, Updated: {$resultTabungan['updated']}, Errors: {$resultTabungan['errors']}\n";
            $message .= "📊 DEPOSITO: Imported: {$resultDeposito['imported']}, Updated: {$resultDeposito['updated']}, Errors: {$resultDeposito['errors']}\n";
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

    private function processCSV($file, $month, $year, $jenis)
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
                        'stspep' => $data['stspep'] ?? null,
                        'tgllhr' => $tgllhr,
                        'nmibu' => $data['nmibu'] ?? null,
                        'ketsandi' => $data['ketsandi'] ?? null,
                        'namapt' => $data['namapt'] ?? null,
                        'period_month' => $month,
                        'period_year' => $year,
                    ];
                }

                // Save ke tabel yang sesuai
                if ($jenis === 'TABUNGAN') {
                    Tabungan::create($fundingData);
                    $imported++;
                } else {
                    Deposito::create($fundingData);
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
