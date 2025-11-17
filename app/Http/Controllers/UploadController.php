<?php

namespace App\Http\Controllers;

use App\Models\Pembiayaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function index()
    {
        $lastUpload = Pembiayaan::max('updated_at');
        $totalData = Pembiayaan::count();

        return view('upload.index', compact('lastUpload', 'totalData'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'month' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12',
            'year' => 'required|digits:4|integer|min:2020|max:2030',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $month = $request->input('month');
            $year = $request->input('year');

            $file = $request->file('csv_file');
            $path = $file->getRealPath();

            // Buka file CSV dengan fopen untuk handling yang lebih baik
            $handle = fopen($path, 'r');

            if ($handle === false) {
                throw new \Exception('Tidak dapat membuka file CSV');
            }

            // Ambil header
            $header = fgetcsv($handle, 0, ',', '"', '\\');

            if ($header === false || empty($header)) {
                throw new \Exception('File CSV tidak memiliki header yang valid');
            }

            // Track statistics
            $imported = 0;
            $updated = 0;
            $errors = 0;
            $errorDetails = [];

            DB::beginTransaction();

            // Hapus data periode yang sama sebelum upload
            $deletedCount = Pembiayaan::where('period_month', $month)
                ->where('period_year', $year)
                ->delete();

            Log::info("Deleted existing pembiayaan data for period {$year}-{$month}: {$deletedCount} records");

            $lineNumber = 1; // Start from 1 (after header)

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $lineNumber++;

                // Skip baris kosong
                if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                    continue;
                }

                // Skip jika jumlah kolom tidak sesuai
                if (count($row) < count($header)) {
                    $errors++;
                    $errorDetails[] = "Baris {$lineNumber}: Jumlah kolom tidak sesuai (" . count($row) . " vs " . count($header) . ")";
                    continue;
                }

                // Pad row jika lebih pendek dari header
                while (count($row) < count($header)) {
                    $row[] = '';
                }

                // Ambil hanya sejumlah kolom header
                $row = array_slice($row, 0, count($header));

                $data = array_combine($header, $row);

                // Validasi data wajib
                if (empty($data['nokontrak']) || trim($data['nokontrak']) === '') {
                    $errors++;
                    $errorDetails[] = "Baris {$lineNumber}: Nomor kontrak kosong";
                    continue;
                }

                try {
                    // Convert tanggal dari format YYYYMMDD ke Y-m-d
                    $tgleff = !empty($data['tgleff']) ? $this->parseDate($data['tgleff']) : null;
                    $tglexp = !empty($data['tglexp']) ? $this->parseDate($data['tglexp']) : null;

                    Pembiayaan::create([
                        'nokontrak' => trim($data['nokontrak']),
                        'period_month' => $month,
                        'period_year' => $year,
                        'nama' => $data['nama'] ?? '',
                        'tgleff' => $tgleff,
                        'jw' => (int)($data['jw'] ?? 0),
                        'tglexp' => $tglexp,
                        'mdlawal' => (float)($data['mdlawal'] ?? 0),
                        'mgnawal' => (float)($data['mgnawal'] ?? 0),
                        'osmdlc' => (float)($data['osmdlc'] ?? 0),
                        'osmgnc' => (float)($data['osmgnc'] ?? 0),
                        'colbaru' => $data['colbaru'] ?? '',
                        'kdaoh' => $data['kdaoh'] ?? '',
                        'acpok' => $data['acpok'] ?? '',
                        'angsmdl' => (float)($data['angsmdl'] ?? 0),
                        'angsmgn' => (float)($data['angsmgn'] ?? 0),
                        'alamat' => $data['alamat'] ?? '',
                        'telprmh' => $data['telprmh'] ?? '',
                        'hp' => $data['hp'] ?? '',
                        'fnama' => $data['fnama'] ?? '',
                        'sahirrp' => (float)($data['sahirrp'] ?? 0),
                        'tgkpok' => (float)($data['tgkpok'] ?? 0),
                        'tgkmgn' => (float)($data['tgkmgn'] ?? 0),
                        'tgkdnd' => (float)($data['tgkdnd'] ?? 0),
                        'blntgkpok' => !empty($data['blntgkpok']) ? (int)$data['blntgkpok'] : null,
                        'blntgkmgn' => !empty($data['blntgkmgn']) ? (int)$data['blntgkmgn'] : null,
                        'blntgkdnd' => !empty($data['blntgkdnd']) ? (int)$data['blntgkdnd'] : null,
                        'kdkolek' => $data['kdkolek'] ?? '',
                        'kdgroupdeb' => $data['kdgroupdeb'] ?? '',
                        'kdgroupdana' => $data['kdgroupdana'] ?? '',
                        'haritgkmdl' => (int)($data['HARITGKMDL'] ?? 0),
                        'haritgkmgn' => (int)($data['HARITGKMGN'] ?? 0),
                        'nocif' => $data['nocif'] ?? '',
                        'kdprd' => $data['kdprd'] ?? '',
                        'pokpby' => $data['pokpby'] ?? '',
                        'kdloc' => $data['kdloc'] ?? '',
                        'kelurahan' => $data['kelurahan'] ?? '',
                        'kecamatan' => $data['kecamatan'] ?? '',
                        'kota' => $data['kota'] ?? '',
                        'nmao' => $data['nmao'] ?? '',
                        'colllanjut' => $data['colllanjut'] ?? '',
                        'tgkharilanjut' => (int)($data['tgkharilanjut'] ?? 0),
                        'angs_ke' => (int)($data['angs_ke'] ?? 0),
                        'angske_x' => (int)($data['angske_x'] ?? 0),
                        'kdmco' => $data['kdmco'] ?? '',
                        'kdsektor' => $data['kdsektor'] ?? '',
                        'kdsub' => $data['kdsub'] ?? '',
                        'plafon' => (float)($data['plafon'] ?? 0),
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    $errorDetails[] = "Baris {$lineNumber} (Kontrak: " . ($data['nokontrak'] ?? 'N/A') . "): " . $e->getMessage();
                }
            }

            fclose($handle);
            DB::commit();

            $monthName = $this->getMonthName($month);
            $message = "Import data periode {$monthName} {$year} berhasil!\n\n";

            if ($deletedCount > 0) {
                $message .= "🗑️  Data lama dihapus: {$deletedCount} kontrak\n\n";
            }

            $message .= "✅ Berhasil diimport: {$imported} kontrak\n";
            $message .= "❌ Error: {$errors} baris";

            if (!empty($errorDetails)) {
                Log::warning('CSV Import Errors', ['errors' => $errorDetails]);
                $message .= "\n\nDetail error:\n" . implode("\n", array_slice($errorDetails, 0, 10));
                if (count($errorDetails) > 10) {
                    $message .= "\n... dan " . (count($errorDetails) - 10) . " error lainnya";
                }
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            DB::rollBack();
            Log::error('CSV Upload Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal upload file: ' . $e->getMessage());
        }
    }

    public function clear(Request $request)
    {
        try {
            $count = Pembiayaan::count();
            Pembiayaan::truncate();

            return redirect()->back()->with('success', "Berhasil menghapus {$count} data pembiayaan.");
        } catch (\Exception $e) {
            Log::error('Clear Data Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // Format YYYYMMDD
        if (strlen($date) == 8 && is_numeric($date)) {
            $year = substr($date, 0, 4);
            $month = substr($date, 4, 2);
            $day = substr($date, 6, 2);
            return "{$year}-{$month}-{$day}";
        }

        return $date;
    }

    private function getMonthName($month)
    {
        $months = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];

        return $months[$month] ?? $month;
    }
}
