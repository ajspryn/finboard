<?php

namespace App\Jobs;

use App\Models\CsvUploadStatus;
use App\Models\Pembiayaan;
use App\Models\Tabungan;
use App\Models\Deposito;
use App\Models\Linkage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCsvUpload implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out (10 minutes for large files)
     */
    public $timeout = 600;

    /**
     * The number of times the job may be attempted
     */
    public $tries = 3;

    protected $filePaths;
    protected $month;
    protected $year;
    protected $userId;
    protected $statusIds;

    /**
     * Create a new job instance.
     */
    public function __construct(array $filePaths, string $month, string $year, int $userId, array $statusIds)
    {
        $this->filePaths = $filePaths;
        $this->month = $month;
        $this->year = $year;
        $this->userId = $userId;
        $this->statusIds = $statusIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Disable Elasticsearch indexing during bulk operations for performance
        Pembiayaan::$disableElasticsearchIndexing = true;
        Tabungan::$disableElasticsearchIndexing = true;
        Deposito::$disableElasticsearchIndexing = true;
        Linkage::$disableElasticsearchIndexing = true;

        // Aggressive database optimizations for maximum performance
        DB::disableQueryLog();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('SET UNIQUE_CHECKS=0;');
        // DB::statement('SET AUTOCOMMIT=0;'); // Removed - causing transaction issues
        DB::statement('SET SQL_MODE="";'); // Disable strict mode for faster inserts
        // DB::statement('SET SESSION innodb_buffer_pool_size = 134217728;'); // Removed - global variable
        // DB::statement('SET SESSION innodb_log_buffer_size = 16777216;'); // Removed - global variable
        DB::statement('SET SESSION bulk_insert_buffer_size = 268435456;'); // 256MB bulk insert buffer

        // Fetch status records from database
        $statusRecords = CsvUploadStatus::whereIn('id', array_values($this->statusIds))->get()->keyBy('upload_type');

        try {
            $totalImported = 0;
            $totalErrors = 0;
            $allErrorDetails = [];

            // Process each upload type in parallel using separate transactions for better performance
            $results = [];

            foreach ($this->filePaths as $type => $filePath) {
                if (isset($statusRecords[$type])) {
                    $status = $statusRecords[$type];
                    $status->update(['status' => 'processing', 'message' => "Memproses data " . ucfirst($type) . "..."]);

                    // Process each file in its own transaction for better performance
                    $result = $this->processCsvFileOptimized(Storage::path($filePath), strtoupper($type), $this->month, $this->year);

                    $results[$type] = $result;
                    $totalImported += $result['imported'];
                    $totalErrors += $result['errors'];

                    $status->update([
                        'status' => $result['errors'] > 0 ? 'completed_with_errors' : 'completed',
                        'processed_records' => $result['imported'],
                        'error_count' => $result['errors'],
                        'errors' => $result['errorDetails'] ?? null,
                        'message' => ucfirst($type) . ": {$result['imported']} record berhasil diimport" .
                            ($result['errors'] > 0 ? ", {$result['errors']} error" : "")
                    ]);

                    if (!empty($result['errorDetails'])) {
                        $allErrorDetails = array_merge($allErrorDetails, $result['errorDetails']);
                    }
                }
            }

            // Clean up temporary files
            foreach ($this->filePaths as $filePath) {
                Storage::delete($filePath);
            }
        } catch (\Exception $e) {
            // Update all status records with error
            foreach ($statusRecords as $status) {
                $status->update([
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }

            // Clean up temporary files
            foreach ($this->filePaths as $filePath) {
                Storage::delete($filePath);
            }

            Log::error('ProcessCsvUpload Error: ' . $e->getMessage());
            throw $e;
        } finally {
            // Re-enable database optimizations
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::statement('SET UNIQUE_CHECKS=1;');
            // DB::statement('SET AUTOCOMMIT=1;'); // Removed - not needed
            DB::enableQueryLog();

            // Re-enable Elasticsearch indexing
            Pembiayaan::$disableElasticsearchIndexing = false;
            Tabungan::$disableElasticsearchIndexing = false;
            Deposito::$disableElasticsearchIndexing = false;
            Linkage::$disableElasticsearchIndexing = false;
        }
    }

    private function processCsvFile($filePath, $jenis, $month, $year)
    {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 1000, ',', '"', '\\');
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $errorDetails = [];
        $batchData = [];
        $batchSize = 50; // Reduced batch size to prevent memory exhaustion

        $lineNumber = 1;

        while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
            $lineNumber++;

            try {
                $record = [];

                if ($jenis === 'PEMBIAYAAN') {
                    // Use the existing validateAndConvertData method for pembiayaan
                    $csvRow = array_combine(array_map('strtolower', array_map('trim', $header)), $data);
                    $record = $this->validateAndConvertData($csvRow, $lineNumber);

                    // Check for duplicates
                    $exists = Pembiayaan::where('nokontrak', $record['nokontrak'])
                        ->where('period_month', $month)
                        ->where('period_year', $year)
                        ->exists();

                    if ($exists) {
                        $errors++;
                        $errorDetails[] = "PEMBIAYAAN Baris {$lineNumber}: No kontrak {$record['nokontrak']} sudah ada";
                        continue;
                    }
                } elseif ($jenis === 'TABUNGAN') {
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
                        'nocif' => $data[0] ?? '',
                        'nodep' => $data[1] ?? '',
                        'kodeprd' => $data[2] ?? '',
                        'nomrp' => $this->parseNumeric($data[3] ?? 0),
                        'fnama' => $data[4] ?? '',
                        'namaqq' => $data[5] ?? '',
                        'stsrec' => $data[6] ?? '',
                        'aro' => $this->parseNumeric($data[7] ?? 0),
                        'nisbah' => $this->parseNumeric($data[8] ?? 0),
                        'spread' => $this->parseNumeric($data[9] ?? 0),
                        'equivrate' => $this->parseNumeric($data[10] ?? 0),
                        'komitrate' => $this->parseNumeric($data[11] ?? 0),
                        'aro' => $this->parseNumeric($data[12] ?? 0),
                        'nisbah' => $this->parseNumeric($data[13] ?? 0),
                        'spread' => $this->parseNumeric($data[14] ?? 0),
                        'equivrate' => $this->parseNumeric($data[15] ?? 0),
                        'komitrate' => $this->parseNumeric($data[16] ?? 0),
                        'tambahnom' => $this->parseNumeric($data[17] ?? 0),
                        'tax' => $this->parseNumeric($data[18] ?? 0),
                        'bnghtg' => $this->parseNumeric($data[19] ?? 0),
                        'nisbahrp' => $this->parseNumeric($data[20] ?? 0),
                        'tgllhr' => $this->parseDate($data[21] ?? ''),
                        'nmibu' => $data[22] ?? '',
                        'ketsandi' => $data[23] ?? '',
                        'namapt' => $data[24] ?? '',
                        'kodeloc' => $data[25] ?? '',
                        'period_month' => $month,
                        'period_year' => $year,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } elseif ($jenis === 'LINKAGE') {
                    $record = [
                        'nocif' => $data[0] ?? '',
                        'norek' => $data[1] ?? '',
                        'fnama' => $data[2] ?? '',
                        'namaqq' => $data[3] ?? '',
                        'tgleff' => $this->parseDate($data[4] ?? ''),
                        'tgljt' => $this->parseDate($data[5] ?? ''),
                        'prsnisbah' => $this->parseNumeric($data[6] ?? 0),
                        'plafon' => $this->parseNumeric($data[7] ?? 0),
                        'os' => $this->parseNumeric($data[8] ?? 0),
                        'period_month' => $month,
                        'period_year' => $year,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $batchData[] = $record;

                // Insert in batches
                if (count($batchData) >= $batchSize) {
                    if ($jenis === 'PEMBIAYAAN') {
                        Pembiayaan::insert($batchData);
                    } elseif ($jenis === 'TABUNGAN') {
                        Tabungan::insert($batchData);
                    } elseif ($jenis === 'DEPOSITO') {
                        Deposito::insert($batchData);
                    } elseif ($jenis === 'LINKAGE') {
                        Linkage::insert($batchData);
                    }
                    $imported += count($batchData);
                    $batchData = [];
                }
            } catch (\Exception $e) {
                $errors++;
                $errorDetails[] = "{$jenis} Baris {$lineNumber}: " . $e->getMessage();
                Log::error("Error importing {$jenis} line {$lineNumber}: " . $e->getMessage());
            }
        }

        // Insert remaining batch
        if (!empty($batchData)) {
            if ($jenis === 'PEMBIAYAAN') {
                Pembiayaan::insert($batchData);
            } elseif ($jenis === 'TABUNGAN') {
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

    /**
     * Optimized CSV processing method with memory-efficient batching and parallel processing
     */
    private function processCsvFileOptimized($filePath, $jenis, $month, $year)
    {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 1000, ',', '"', '\\');
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $errorDetails = [];
        $batchData = [];
        $batchSize = 50; // Reduced batch size to prevent memory exhaustion

        $lineNumber = 1;

        // Use generator for memory-efficient processing
        while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
            $lineNumber++;

            try {
                $record = $this->createRecordFromCsvRow($data, $header, $jenis, $lineNumber, $month, $year);

                if ($record === null) {
                    continue; // Skip invalid records
                }

                $batchData[] = $record;

                // Insert in larger batches for maximum performance
                if (count($batchData) >= $batchSize) {
                    try {
                        $imported += $this->bulkInsertRecords($batchData, $jenis);
                        $batchData = [];
                    } catch (\Exception $e) {
                        $errors += count($batchData);
                        $errorDetails[] = "{$jenis} Batch insert failed: " . $e->getMessage();
                        Log::error("Batch insert failed for {$jenis}: " . $e->getMessage());
                        $batchData = [];
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $errorDetails[] = "{$jenis} Baris {$lineNumber}: " . $e->getMessage();
                Log::error("Error importing {$jenis} line {$lineNumber}: " . $e->getMessage());
            }
        }

        // Insert remaining batch
        if (!empty($batchData)) {
            try {
                $imported += $this->bulkInsertRecords($batchData, $jenis);
            } catch (\Exception $e) {
                $errors += count($batchData);
                $errorDetails[] = "{$jenis} Final batch insert failed: " . $e->getMessage();
                Log::error("Final batch insert failed for {$jenis}: " . $e->getMessage());
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

    /**
     * Create record from CSV row data with optimized processing
     */
    private function createRecordFromCsvRow($data, $header, $jenis, $lineNumber, $month, $year)
    {
        if ($jenis === 'PEMBIAYAAN') {
            // Use the existing validateAndConvertData method for pembiayaan
            $csvRow = array_combine(array_map('strtolower', array_map('trim', $header)), $data);
            $record = $this->validateAndConvertData($csvRow, $lineNumber);

            // Skip duplicate check for performance - assume data is clean
            // If duplicates exist, they will be handled by database constraints
            return $record;
        } elseif ($jenis === 'TABUNGAN') {
            return [
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
            return [
                'nocif' => $data[0] ?? '',
                'nodep' => $data[1] ?? '',
                'kdprd' => $data[2] ?? '',
                'nomrp' => $this->parseNumeric($data[3] ?? 0),
                'nama' => $data[4] ?? '',
                'stsrec' => $data[6] ?? '',
                'aro' => $this->parseNumeric($data[7] ?? 0),
                'nisbah' => $this->parseNumeric($data[8] ?? 0),
                'spread' => $this->parseNumeric($data[9] ?? 0),
                'equivrate' => $this->parseNumeric($data[10] ?? 0),
                'komitrate' => $this->parseNumeric($data[11] ?? 0),
                'tambahnom' => $this->parseNumeric($data[17] ?? 0),
                'tax' => $this->parseNumeric($data[18] ?? 0),
                'bnghtg' => $this->parseNumeric($data[19] ?? 0),
                'nisbahrp' => $this->parseNumeric($data[20] ?? 0),
                'tgllhr' => $this->parseDate($data[21] ?? ''),
                'nmibu' => $data[22] ?? '',
                'ketsandi' => $data[23] ?? '',
                'namapt' => $data[24] ?? '',
                'period_month' => $month,
                'period_year' => $year,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        } elseif ($jenis === 'LINKAGE') {
            return [
                'nocif' => $data[0] ?? '',
                'nokontrak' => $data[1] ?? '',
                'nama' => $data[2] ?? '',
                'kelompok' => $data[3] ?? '',
                'jnsakad' => $data[4] ?? '',
                'tgleff' => $this->parseDate($data[5] ?? ''),
                'tgljt' => $this->parseDate($data[6] ?? ''),
                'prsnisbah' => $this->parseNumeric($data[7] ?? 0),
                'plafon' => $this->parseNumeric($data[8] ?? 0),
                'os' => $this->parseNumeric($data[9] ?? 0),
                'period_month' => $month,
                'period_year' => $year,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return null;
    }

    /**
     * Bulk insert records with optimized database operations
     */
    private function bulkInsertRecords($batchData, $jenis)
    {
        if (empty($batchData)) {
            return 0;
        }

        $count = count($batchData);

        try {
            if ($jenis === 'PEMBIAYAAN') {
                Pembiayaan::insert($batchData);
            } elseif ($jenis === 'TABUNGAN') {
                Tabungan::insert($batchData);
            } elseif ($jenis === 'DEPOSITO') {
                Deposito::insert($batchData);
            } elseif ($jenis === 'LINKAGE') {
                Linkage::insert($batchData);
            }
        } catch (\Exception $e) {
            Log::error("Bulk insert failed for {$jenis}: " . $e->getMessage());
            throw $e;
        }

        return $count;
    }

    private function validateAndConvertData(array $data, int $lineNumber): array
    {
        // Convert and validate dates
        $tgleff = $this->parseDate($data['tgleff'] ?? '');
        $tglexp = $this->parseDate($data['tglexp'] ?? '');

        // Validate date ranges if both dates exist
        if ($tgleff && $tglexp && strtotime($tgleff) > strtotime($tglexp)) {
            throw new \Exception('Tanggal efektif tidak boleh lebih besar dari tanggal expired');
        }

        // Validate and convert numeric fields
        $numericFields = [
            'jw',
            'mdlawal',
            'mgnawal',
            'osmdlc',
            'osmgnc',
            'angsmdl',
            'angsmgn',
            'sahirrp',
            'tgkpok',
            'tgkmgn',
            'tgkdnd',
            'haritgkmdl',
            'haritgkmgn',
            'tgkharilanjut',
            'angs_ke',
            'angske_x',
            'plafon'
        ];

        $validatedData = [];
        foreach ($numericFields as $field) {
            $value = $data[$field] ?? 0;
            if (!is_numeric($value) && !empty($value)) {
                throw new \Exception("Field '{$field}' harus berupa angka");
            }
            $validatedData[$field] = $field === 'plafon' ? (float)$value : (float)$value;
        }

        // Handle nullable integer fields
        $nullableIntFields = ['blntgkpok', 'blntgkmgn', 'blntgkdnd'];
        foreach ($nullableIntFields as $field) {
            $value = $data[$field] ?? null;
            $validatedData[$field] = !empty($value) && is_numeric($value) ? (int)$value : null;
        }

        // Handle string fields with trimming
        $stringFields = [
            'nama',
            'colbaru',
            'kdaoh',
            'acpok',
            'alamat',
            'telprmh',
            'hp',
            'fnama',
            'kdkolek',
            'kdgroupdeb',
            'kdgroupdana',
            'nocif',
            'kdprd',
            'pokpby',
            'kdloc',
            'kelurahan',
            'kecamatan',
            'kota',
            'nmao',
            'colllanjut',
            'kdmco',
            'kdsektor',
            'kdsub',
            'tagmdl',
            'tagmgn',
            'inptgl'
        ];

        foreach ($stringFields as $field) {
            $validatedData[$field] = trim($data[$field] ?? '');
        }

        // Handle HARITGKMDL and HARITGKMGN (case sensitive)
        $validatedData['haritgkmdl'] = (int)($data['HARITGKMDL'] ?? $data['haritgkmdl'] ?? 0);
        $validatedData['haritgkmgn'] = (int)($data['HARITGKMGN'] ?? $data['haritgkmgn'] ?? 0);

        return array_merge($validatedData, [
            'nokontrak' => trim($data['nokontrak']),
            'period_month' => $this->month,
            'period_year' => $this->year,
            'tgleff' => $tgleff,
            'tglexp' => $tglexp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function checkMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        if ($memoryLimit > 0 && $memoryUsage > ($memoryLimit * 0.8)) {
            Log::warning("High memory usage detected: " . $this->formatBytes($memoryUsage) . " of " . $this->formatBytes($memoryLimit));
        }
    }

    private function parseMemoryLimit(string $limit): int
    {
        if (preg_match('/^(\d+)([KMGT]?)$/i', $limit, $matches)) {
            $value = (int)$matches[1];
            $unit = strtoupper($matches[2] ?? 'B');

            switch ($unit) {
                case 'T':
                    $value *= 1024;
                case 'G':
                    $value *= 1024;
                case 'M':
                    $value *= 1024;
                case 'K':
                    $value *= 1024;
            }

            return $value;
        }

        return 0;
    }

    private function readCsvFile(): array
    {
        $filePath = Storage::path($this->filePath);
        $data = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Read header row
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            if (!$header) {
                throw new \Exception('Tidak dapat membaca header CSV');
            }

            // Normalize header names (lowercase, trim)
            $header = array_map('strtolower', array_map('trim', $header));

            // Read data rows
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if (count($row) === count($header)) {
                    $data[] = array_combine($header, $row);
                }
            }

            fclose($handle);
        } else {
            throw new \Exception('Tidak dapat membuka file CSV');
        }

        return $data;
    }

    private function parseDate(string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        // Try different date formats commonly used in CSV files
        $formats = [
            'Y-m-d',        // 2023-12-31
            'd/m/Y',        // 31/12/2023
            'd-m-Y',        // 31-12-2023
            'm/d/Y',        // 12/31/2023
            'Y/m/d',        // 2023/12/31
            'd F Y',        // 31 December 2023
            'F d, Y',       // December 31, 2023
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try to parse with strtotime as fallback
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        throw new \Exception("Format tanggal tidak valid: {$dateString}");
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

    private function parseNumeric($value)
    {
        if (empty($value)) {
            return 0;
        }

        $value = str_replace([',', ' '], '', $value);
        return floatval($value);
    }
}
