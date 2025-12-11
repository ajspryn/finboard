<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvUpload;
use App\Models\CsvUploadStatus;
use App\Models\Tabungan;
use App\Models\Deposito;
use App\Models\Pembiayaan;
use App\Models\Linkage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function index()
    {
        // Get last upload from all tables
        $lastUploadTabungan = Tabungan::max('updated_at');
        $lastUploadDeposito = Deposito::max('updated_at');
        $lastUploadLinkage = Linkage::max('updated_at');
        $lastUploadPembiayaan = Pembiayaan::max('updated_at');
        $lastUpload = max($lastUploadTabungan, $lastUploadDeposito, $lastUploadLinkage, $lastUploadPembiayaan);

        // Get total data
        $totalTabungan = Tabungan::count();
        $totalDeposito = Deposito::count();
        $totalLinkage = Linkage::count();
        $totalPembiayaan = Pembiayaan::count();
        $totalData = $totalTabungan + $totalDeposito + $totalLinkage + $totalPembiayaan;

        // Get sum saldo
        $totalSaldoTabungan = Tabungan::sum('sahirrp');
        $totalSaldoDeposito = Deposito::sum('nomrp');
        $totalSaldoLinkage = Linkage::sum('os');
        $totalSaldoPembiayaan = Pembiayaan::sum('plafon');

        // Get upload history with periods
        $uploadHistory = collect();

        // Get distinct periods from all tables
        $tabunganPeriods = Tabungan::selectRaw("DISTINCT YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count, SUM(sahirrp) as total_saldo, MAX(created_at) as last_upload")
            ->groupByRaw("YEAR(created_at), MONTH(created_at)")
            ->orderByRaw("YEAR(created_at) DESC, MONTH(created_at) DESC")
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

        $depositoPeriods = Deposito::selectRaw("DISTINCT YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count, SUM(nomrp) as total_saldo, MAX(created_at) as last_upload")
            ->groupByRaw("YEAR(created_at), MONTH(created_at)")
            ->orderByRaw("YEAR(created_at) DESC, MONTH(created_at) DESC")
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

        $linkagePeriods = Linkage::selectRaw("DISTINCT YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count, SUM(os) as total_saldo, MAX(created_at) as last_upload")
            ->groupByRaw("YEAR(created_at), MONTH(created_at)")
            ->orderByRaw("YEAR(created_at) DESC, MONTH(created_at) DESC")
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

        $pembiayaanPeriods = Pembiayaan::selectRaw("DISTINCT YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count, SUM(plafon) as total_saldo, MAX(created_at) as last_upload")
            ->groupByRaw("YEAR(created_at), MONTH(created_at)")
            ->orderByRaw("YEAR(created_at) DESC, MONTH(created_at) DESC")
            ->get()
            ->map(function ($item) {
                return (object)[
                    'jenis' => 'PEMBIAYAAN',
                    'year' => $item->year,
                    'month' => $item->month,
                    'count' => $item->count,
                    'total_saldo' => $item->total_saldo,
                    'last_upload' => $item->last_upload
                ];
            });

        // Merge all periods
        $allPeriods = array_map(function ($item) {
            return (array) $item;
        }, $tabunganPeriods->toArray());

        $allPeriods = array_merge($allPeriods, array_map(function ($item) {
            return (array) $item;
        }, $depositoPeriods->toArray()));

        $allPeriods = array_merge($allPeriods, array_map(function ($item) {
            return (array) $item;
        }, $linkagePeriods->toArray()));

        $allPeriods = array_merge($allPeriods, array_map(function ($item) {
            return (array) $item;
        }, $pembiayaanPeriods->toArray()));

        // Get recent upload statuses for pembiayaan background jobs
        $recentUploads = CsvUploadStatus::where('user_id', auth()->id())
            ->get()
            ->sortByDesc('created_at');

        // Combine upload history and recent uploads into one collection
        $combinedUploads = collect();

        // Add completed uploads (from periods)
        foreach ($allPeriods as $period) {
            $combinedUploads->push([
                'type' => 'completed',
                'period' => str_pad($period['month'], 2, '0', STR_PAD_LEFT) . '-' . $period['year'],
                'jenis' => $period['jenis'],
                'count' => $period['count'],
                'total_saldo' => $period['total_saldo'],
                'status' => 'completed',
                'message' => 'Upload berhasil',
                'created_at' => $period['last_upload'],
                'progress' => null,
                'processed_records' => $period['count'],
                'total_records' => $period['count']
            ]);
        }

        // Add processing uploads (from CsvUploadStatus)
        foreach ($recentUploads as $upload) {
            $combinedUploads->push([
                'type' => 'processing',
                'period' => str_pad($upload->month, 2, '0', STR_PAD_LEFT) . '-' . $upload->year,
                'jenis' => ucfirst($upload->upload_type ?? 'pembiayaan'),
                'count' => $upload->processed_records ?? 0,
                'total_saldo' => null,
                'status' => $upload->status,
                'message' => $upload->message,
                'created_at' => $upload->created_at,
                'progress' => $upload->total_records > 0 ? round(($upload->processed_records / $upload->total_records) * 100) : 0,
                'processed_records' => $upload->processed_records ?? 0,
                'total_records' => $upload->total_records ?? 0
            ]);
        }

        // Sort by created_at descending and paginate
        $sortedUploads = $combinedUploads->sortByDesc('created_at')->values();
        $perPage = 10;
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $paginatedItems = $sortedUploads->slice($offset, $perPage);
        $uploadHistory = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $sortedUploads->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('upload.index', compact('lastUpload', 'totalData', 'totalSaldoTabungan', 'totalSaldoDeposito', 'totalSaldoLinkage', 'totalSaldoPembiayaan', 'uploadHistory'));
    }

    public function upload(Request $request)
    {
        // $request->validate([
        //     'month' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12',
        //     'year' => 'required|digits:4|integer|min:2020|max:2030',
        //     'upload_types' => 'required|array|min:1',
        //     'upload_types.*' => 'in:pembiayaan,tabungan,deposito,linkage',
        //     'csv_file' => 'nullable|file|mimes:csv,txt,text/csv,text/plain|max:51200', // Increased to 50MB
        //     'csv_tabungan' => 'nullable|file|mimes:csv,txt,text/csv,text/plain|max:51200',
        //     'csv_deposito' => 'nullable|file|mimes:csv,txt,text/csv,text/plain|max:51200',
        //     'csv_linkage' => 'nullable|file|mimes:csv,txt,text/csv,text/plain|max:51200',
        // ]);

        $uploadTypes = $request->input('upload_types', []);

        // Validate that required files are present for selected types
        $validationErrors = [];
        if (in_array('pembiayaan', $uploadTypes) && !$request->hasFile('csv_file')) {
            $validationErrors[] = 'File CSV Pembiayaan wajib diupload jika jenis Pembiayaan dipilih.';
        }
        if (in_array('tabungan', $uploadTypes) && !$request->hasFile('csv_tabungan')) {
            $validationErrors[] = 'File CSV Tabungan wajib diupload jika jenis Tabungan dipilih.';
        }
        if (in_array('deposito', $uploadTypes) && !$request->hasFile('csv_deposito')) {
            $validationErrors[] = 'File CSV Deposito wajib diupload jika jenis Deposito dipilih.';
        }
        if (in_array('linkage', $uploadTypes) && !$request->hasFile('csv_linkage')) {
            $validationErrors[] = 'File CSV Linkage wajib diupload jika jenis Linkage dipilih.';
        }

        if (!empty($validationErrors)) {
            return back()->with('error', implode('<br>', $validationErrors));
        }

        try {
            $month = $request->input('month');
            $year = $request->input('year');

            // Handle pembiayaan (background processing)
            if (in_array('pembiayaan', $uploadTypes)) {
                $userId = auth()->id();
                $file = $request->file('csv_file');

                // Store the uploaded file with optimized streaming
                $filePath = $this->storeFileStreamOptimized($file, 'csv_upload', $userId);

                // Create status record
                $status = CsvUploadStatus::create([
                    'user_id' => $userId,
                    'month' => $month,
                    'year' => $year,
                    'status' => 'processing',
                    'message' => 'File sedang diproses dengan performa optimal...',
                    'upload_type' => 'pembiayaan'
                ]);

                // Dispatch the background job with optimized config
                $queueConfig = $this->getOptimizedQueueConfig();
                ProcessCsvUpload::dispatch(['pembiayaan' => $filePath], $month, $year, $userId, ['pembiayaan' => $status->id])
                    ->onQueue($queueConfig['queue']);
            }

            // Handle funding data (asynchronous processing with status tracking)
            $fundingTypes = array_intersect($uploadTypes, ['tabungan', 'deposito', 'linkage']);
            if (!empty($fundingTypes)) {
                $userId = auth()->id();

                // Create status records for each funding type
                $statusIds = [];
                $filePaths = [];

                if (in_array('tabungan', $fundingTypes) && $request->hasFile('csv_tabungan')) {
                    $statusIds['tabungan'] = CsvUploadStatus::create([
                        'user_id' => $userId,
                        'month' => $month,
                        'year' => $year,
                        'status' => 'processing',
                        'message' => 'File Tabungan sedang diproses...',
                        'upload_type' => 'tabungan'
                    ])->id;
                    $filePaths['tabungan'] = $this->storeFileStreamOptimized($request->file('csv_tabungan'), 'funding_tabungan', $userId);
                }
                if (in_array('deposito', $fundingTypes) && $request->hasFile('csv_deposito')) {
                    $statusIds['deposito'] = CsvUploadStatus::create([
                        'user_id' => $userId,
                        'month' => $month,
                        'year' => $year,
                        'status' => 'processing',
                        'message' => 'File Deposito sedang diproses dengan performa optimal...',
                        'upload_type' => 'deposito'
                    ])->id;
                    $filePaths['deposito'] = $this->storeFileStreamOptimized($request->file('csv_deposito'), 'funding_deposito', $userId);
                }
                if (in_array('linkage', $fundingTypes) && $request->hasFile('csv_linkage')) {
                    $statusIds['linkage'] = CsvUploadStatus::create([
                        'user_id' => $userId,
                        'month' => $month,
                        'year' => $year,
                        'status' => 'processing',
                        'message' => 'File Linkage sedang diproses dengan performa optimal...',
                        'upload_type' => 'linkage'
                    ])->id;
                    $filePaths['linkage'] = $this->storeFileStreamOptimized($request->file('csv_linkage'), 'funding_linkage', $userId);
                }

                // Dispatch the background job with optimized config
                $queueConfig = $this->getOptimizedQueueConfig();
                ProcessCsvUpload::dispatch($filePaths, $month, $year, $userId, $statusIds)
                    ->onQueue($queueConfig['queue']);
            }

            $monthName = $this->getMonthName($month);

            $successMessage = "Upload berhasil untuk periode {$monthName} {$year}.\n\n";

            if (in_array('pembiayaan', $uploadTypes)) {
                $successMessage .= "Data Pembiayaan sedang diproses di background.\n";
            }

            if (!empty($fundingTypes)) {
                $successMessage .= "Data Funding sedang diproses di background.\n";
            }

            return redirect()->back()->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Upload Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal upload: ' . $e->getMessage());
        }
    }

    public function clear(Request $request)
    {
        try {
            $countTabungan = Tabungan::count();
            $countDeposito = Deposito::count();
            $countLinkage = Linkage::count();
            $countPembiayaan = Pembiayaan::count();
            $totalCount = $countTabungan + $countDeposito + $countLinkage + $countPembiayaan;

            Tabungan::truncate();
            Deposito::truncate();
            Linkage::truncate();
            Pembiayaan::truncate();

            return redirect()->back()->with('success', "Berhasil menghapus {$totalCount} data (Tabungan: {$countTabungan}, Deposito: {$countDeposito}, Linkage: {$countLinkage}, Pembiayaan: {$countPembiayaan}).");
        } catch (\Exception $e) {
            Log::error('Clear Data Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    private function processCsvFile($file, $jenis, $month, $year)
    {
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle, 1000, ',');
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $errorDetails = [];
        $batchData = [];
        $batchSize = 100;

        $lineNumber = 1; // Start from 1 since header is already read

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNumber++;

            try {
                $record = [];

                if ($jenis === 'TABUNGAN') {
                    $record = [
                        'nocif' => $data[0] ?? '',
                        'notab' => $data[1] ?? '',
                        'kodeprd' => $data[2] ?? '',
                        'sahirrp' => $this->parseNumeric($data[3] ?? 0),
                        'fnama' => $data[4] ?? '',
                        'namaqq' => $data[5] ?? '',
                        'stsrec' => $data[6] ?? '',
                        'saldoblok' => $this->parseNumeric($data[7] ?? 0),
                        'stsrest' => $data[8] ?? '',
                        'tax' => $this->parseNumeric($data[9] ?? 0),
                        'tgltrnakh' => $this->parseDate($data[10] ?? ''),
                        'avgeom' => $this->parseNumeric($data[11] ?? 0),
                        'stspep' => $data[12] ?? '',
                        'kdrisk' => $data[13] ?? '',
                        'noid' => $data[14] ?? '',
                        'hp' => $data[15] ?? '',
                        'tgllhr' => $this->parseDate($data[16] ?? ''),
                        'nmibu' => $data[17] ?? '',
                        'ketsandi' => $data[18] ?? '',
                        'namapt' => $data[19] ?? '',
                        'kodeloc' => $data[20] ?? '',
                        'period_month' => $month,
                        'period_year' => $year,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } elseif ($jenis === 'DEPOSITO') {
                    $record = [
                        'nodep' => $data[0] ?? '',
                        'nocif' => $data[1] ?? '',
                        'nobilyet' => $data[2] ?? '',
                        'nama' => $data[3] ?? '',
                        'nomrp' => $this->parseNumeric($data[4] ?? 0),
                        'stsrec' => $data[5] ?? '',
                        'kdprd' => $data[6] ?? '',
                        'jkwaktu' => $data[7] ?? '',
                        'jnsjkwaktu' => $data[8] ?? '',
                        'tglbuka' => $this->parseDate($data[9] ?? ''),
                        'tgleff' => $this->parseDate($data[10] ?? ''),
                        'tgljtempo' => $this->parseDate($data[11] ?? ''),
                        'aro' => $this->parseNumeric($data[12] ?? 0),
                        'nisbah' => $this->parseNumeric($data[13] ?? 0),
                        'spread' => $this->parseNumeric($data[14] ?? 0),
                        'equivrate' => $this->parseNumeric($data[15] ?? 0),
                        'komitrate' => $this->parseNumeric($data[16] ?? 0),
                        'ststrn' => $data[17] ?? '',
                        'kdwil' => $data[18] ?? '',
                        'kodeaoh' => $data[19] ?? '',
                        'kodeaop' => $data[20] ?? '',
                        'noacbng' => $data[21] ?? '',
                        'tambahnom' => $this->parseNumeric($data[22] ?? 0),
                        'noid' => $data[23] ?? '',
                        'alamat' => $data[24] ?? '',
                        'kota' => $data[25] ?? '',
                        'telprmh' => $data[26] ?? '',
                        'hp' => $data[27] ?? '',
                        'stskait' => $data[28] ?? '',
                        'golcustbi' => $data[29] ?? '',
                        'kelurahan' => $data[30] ?? '',
                        'kecamatan' => $data[31] ?? '',
                        'kdpos' => $data[32] ?? '',
                        'kdrisk' => $data[33] ?? '',
                        'tax' => $this->parseNumeric($data[34] ?? 0),
                        'bnghtg' => $this->parseNumeric($data[35] ?? 0),
                        'nisbahrp' => $this->parseNumeric($data[36] ?? 0),
                        'stspep' => $data[37] ?? '',
                        'tgllhr' => $this->parseDate($data[38] ?? ''),
                        'nmibu' => $data[39] ?? '',
                        'ketsandi' => $data[40] ?? '',
                        'namapt' => $data[41] ?? '',
                        'period_month' => $month,
                        'period_year' => $year,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } elseif ($jenis === 'LINKAGE') {
                    $record = [
                        'nokontrak' => $data[0] ?? '',
                        'nocif' => $data[1] ?? '',
                        'nama' => $data[2] ?? '',
                        'tgleff' => $this->parseDate($data[3] ?? ''),
                        'tgljt' => $this->parseDate($data[4] ?? ''),
                        'kelompok' => $data[5] ?? '',
                        'jnsakad' => $data[6] ?? '',
                        'prsnisbah' => $this->parseNumeric($data[7] ?? 0),
                        'plafon' => $this->parseNumeric($data[8] ?? 0),
                        'os' => $this->parseNumeric($data[9] ?? 0),
                        'period_month' => $month,
                        'period_year' => $year,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $batchData[] = $record;

                // Insert in batches
                if (count($batchData) >= $batchSize) {
                    if ($jenis === 'TABUNGAN') {
                        Tabungan::insert($batchData);
                    } elseif ($jenis === 'DEPOSITO') {
                        Deposito::insert($batchData);
                    } elseif ($jenis === 'LINKAGE') {
                        Linkage::insert($batchData);
                    }
                    $imported += count($batchData);
                    $batchData = [];

                    // Keep connection alive during processing
                    echo str_pad('', 1024, ' ');
                    ob_flush();
                    flush();
                }
            } catch (\Exception $e) {
                $errors++;
                $errorDetails[] = "{$jenis} Baris {$lineNumber}: " . $e->getMessage();
                Log::error("Error importing {$jenis} line {$lineNumber}: " . $e->getMessage());
            }
        }

        // Insert remaining batch
        if (!empty($batchData)) {
            if ($jenis === 'TABUNGAN') {
                Tabungan::insert($batchData);
            } elseif ($jenis === 'DEPOSITO') {
                Deposito::insert($batchData);
            } elseif ($jenis === 'LINKAGE') {
                Linkage::insert($batchData);
            }
            $imported += count($batchData);
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

    /**
     * Optimized file storage with streaming for large files
     */
    private function storeFileStreamOptimized($file, $prefix, $userId)
    {
        $fileName = $prefix . '_' . time() . '_' . $userId . '.csv';

        // Use streaming storage for better memory efficiency
        $path = $file->storeAs('temp/csv_uploads', $fileName);

        // Validate file is readable and not corrupted
        if (!Storage::exists($path)) {
            throw new \Exception('File upload failed - unable to store file');
        }

        // Quick validation - check if file has content
        $handle = fopen(Storage::path($path), 'r');
        $firstLine = fgets($handle);
        fclose($handle);

        if (empty(trim($firstLine))) {
            Storage::delete($path);
            throw new \Exception('File appears to be empty or corrupted');
        }

        return $path;
    }

    /**
     * Get optimized queue configuration for high-throughput processing
     */
    private function getOptimizedQueueConfig()
    {
        return [
            'memory' => 512, // 512MB memory limit
            'queue' => 'default' // Use default queue
        ];
    }
}
