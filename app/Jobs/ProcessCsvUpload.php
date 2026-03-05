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
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::statement('SET UNIQUE_CHECKS=0;');
            // DB::statement('SET AUTOCOMMIT=0;'); // Removed - causing transaction issues
            DB::statement('SET SQL_MODE="";'); // Disable strict mode for faster inserts
            // DB::statement('SET SESSION innodb_buffer_pool_size = 134217728;'); // Removed - global variable
            // DB::statement('SET SESSION innodb_log_buffer_size = 16777216;'); // Removed - global variable
            DB::statement('SET SESSION bulk_insert_buffer_size = 268435456;'); // 256MB bulk insert buffer
        }

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
                    $status->update([
                        'status' => 'processing',
                        'message' => "Memvalidasi dan memproses data " . ucfirst($type) . "..."
                    ]);

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

            // Dispatch event to trigger FinancialHighlight recalculation
            \App\Events\FinancialDataUpdated::dispatch([
                'period_month' => $this->month,
                'period_year' => $this->year,
                'types_updated' => array_keys($this->filePaths),
                'total_imported' => $totalImported,
                'total_errors' => $totalErrors
            ], 'data_import');
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
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                DB::statement('SET UNIQUE_CHECKS=1;');
            }
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
        // Delete existing data for this period before importing new data
        $this->deleteExistingDataForPeriod($jenis, $month, $year);

        $handle = fopen($filePath, 'r');
        // Skip BOM if present
        if (fgets($handle, 4) !== "\xef\xbb\xbf") {
            rewind($handle);
        }
        $header = fgetcsv($handle, 0, ',', '"');
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $errorDetails = [];
        $batchData = [];
        $batchSize = 50; // Reduced batch size to prevent memory exhaustion

        $lineNumber = 1;

        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            $lineNumber++;

            try {
                $record = [];

                if ($jenis === 'PEMBIAYAAN') {
                    // Use the existing validateAndConvertData method for pembiayaan
                    $csvRow = array_combine(array_map('strtolower', array_map('trim', $header)), $data);
                    $record = $this->validateAndConvertData($csvRow, $lineNumber);

                    // No duplicate check needed since we delete existing data first
                } elseif ($jenis === 'TABUNGAN') {
                    // Validate that we have the expected number of columns
                    if (count($data) < 21) {
                        throw new \Exception("Baris tidak lengkap, expected 21 kolom, got " . count($data));
                    }

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
                    // Validate that we have the expected number of columns
                    if (count($data) < 44) {
                        throw new \Exception("Baris tidak lengkap, expected 44 kolom, got " . count($data));
                    }

                    // Map CSV columns to database fields based on actual CSV header
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
                        'noid' => $data[23] ?? '', // First noid field
                        'alamat' => $data[24] ?? '',
                        'kota' => $data[25] ?? '',
                        'telprmh' => $data[26] ?? '',
                        'hp' => $data[27] ?? '', // First hp field
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
                        // Skip duplicate noid (38) and hp (39) fields
                        'tgllhr' => $this->parseDate($data[40] ?? ''),
                        'nmibu' => $data[41] ?? '',
                        'ketsandi' => $data[42] ?? '',
                        'namapt' => $data[43] ?? '',
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
        // Delete existing data for this period before importing new data
        $this->deleteExistingDataForPeriod($jenis, $month, $year);

        $handle = fopen($filePath, 'r');
        // Skip BOM if present
        if (fgets($handle, 4) !== "\xef\xbb\xbf") {
            rewind($handle);
        }
        $header = fgetcsv($handle, 0, ',', '"');
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $errorDetails = [];
        $batchData = [];
        $batchSize = 50; // Reduced batch size to prevent memory exhaustion

        $lineNumber = 1;

        // Use generator for memory-efficient processing
        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            $lineNumber++;

            try {
                $record = $this->createRecordFromCsvRow($data, $header, $jenis, $lineNumber, $month, $year);

                if ($record === null) {
                    continue;
                }

                if (is_array($record) && ($record['__invalid'] ?? false)) {
                    $errors++;
                    $errorDetails[] = "{$jenis} Baris {$lineNumber}: " . ($record['message'] ?? 'Data tidak valid');
                    continue;
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
            if (count($header) !== count($data)) {
                $message = "Mismatch kolom header vs data: header " . count($header) . ", data " . count($data);
                Log::warning("{$message} (baris {$lineNumber})");
                return ['__invalid' => true, 'message' => $message];
            }
            $csvRow = array_combine(array_map('strtolower', array_map('trim', $header)), $data);
            try {
                $record = $this->validateAndConvertData($csvRow, $lineNumber);
                return $record;
            } catch (\Exception $e) {
                Log::warning("Error parsing PEMBIAYAAN row {$lineNumber}: " . $e->getMessage());
                return ['__invalid' => true, 'message' => $e->getMessage()];
            }
        } elseif ($jenis === 'TABUNGAN') {
            // Use array_combine and validation like PEMBIAYAAN
            if (count($header) !== count($data)) {
                $message = "Mismatch kolom header vs data: header " . count($header) . ", data " . count($data);
                Log::warning("{$message} (baris {$lineNumber})");
                return ['__invalid' => true, 'message' => $message];
            }
            $csvRow = array_combine(array_map('strtolower', array_map('trim', $header)), $data);
            try {
                $record = $this->validateTabunganData($csvRow, $lineNumber);
                return $record;
            } catch (\Exception $e) {
                Log::warning("Error parsing TABUNGAN row {$lineNumber}: " . $e->getMessage());
                return ['__invalid' => true, 'message' => $e->getMessage()];
            }
        } elseif ($jenis === 'DEPOSITO') {
            // Use array_combine and validation like PEMBIAYAAN
            if (count($header) !== count($data)) {
                $message = "Mismatch kolom header vs data: header " . count($header) . ", data " . count($data);
                Log::warning("{$message} (baris {$lineNumber})");
                return ['__invalid' => true, 'message' => $message];
            }
            $csvRow = array_combine(array_map('strtolower', array_map('trim', $header)), $data);
            try {
                $record = $this->validateDepositoData($csvRow, $lineNumber);
                return $record;
            } catch (\Exception $e) {
                Log::warning("Error parsing DEPOSITO row {$lineNumber}: " . $e->getMessage());
                return ['__invalid' => true, 'message' => $e->getMessage()];
            }
        } elseif ($jenis === 'LINKAGE') {
            // Use array_combine and validation like PEMBIAYAAN
            if (count($header) !== count($data)) {
                $message = "Mismatch kolom header vs data: header " . count($header) . ", data " . count($data);
                Log::warning("{$message} (baris {$lineNumber})");
                return ['__invalid' => true, 'message' => $message];
            }
            $csvRow = array_combine(array_map('strtolower', array_map('trim', $header)), $data);
            try {
                $record = $this->validateLinkageData($csvRow, $lineNumber);
                return $record;
            } catch (\Exception $e) {
                Log::warning("Error parsing LINKAGE row {$lineNumber}: " . $e->getMessage());
                return ['__invalid' => true, 'message' => $e->getMessage()];
            }
        }

        return null;
    }

    /**
     * Bulk insert records with optimized database operations and duplicate handling
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
                // Handle duplicates for linkage table
                $filteredData = [];
                foreach ($batchData as $record) {
                    $nokontrak = $record['nokontrak'] ?? '';
                    $periodYear = $record['period_year'] ?? '';
                    $periodMonth = $record['period_month'] ?? '';

                    if (empty($nokontrak) || empty($periodYear) || empty($periodMonth)) {
                        continue; // Skip invalid records
                    }

                    // Check if record already exists
                    $exists = DB::table('linkages')
                        ->where('nokontrak', $nokontrak)
                        ->where('period_year', $periodYear)
                        ->where('period_month', $periodMonth)
                        ->exists();

                    if (!$exists) {
                        $filteredData[] = $record;
                    } else {
                        Log::info("Skipping duplicate linkage record: {$nokontrak} for {$periodYear}-{$periodMonth}");
                    }
                }

                if (!empty($filteredData)) {
                    Linkage::insert($filteredData);
                    $count = count($filteredData); // Update count to reflect actual inserted records
                } else {
                    $count = 0;
                }
            }
        } catch (\Exception $e) {
            Log::error("Bulk insert failed for {$jenis}: " . $e->getMessage());
            throw $e;
        }

        return $count;
    }

    private function validateAndConvertData(array $data, int $lineNumber): array
    {
        // Convert and validate dates with tolerance
        $tgleff = $this->parseDate($data['tgleff'] ?? '');
        $tglexp = $this->parseDate($data['tglexp'] ?? '');

        // If a date is provided but cannot be parsed, treat it as a validation error
        if (!empty($data['tgleff'] ?? null) && $tgleff === null) {
            throw new \Exception("Field 'tgleff' tidak valid");
        }
        if (!empty($data['tglexp'] ?? null) && $tglexp === null) {
            throw new \Exception("Field 'tglexp' tidak valid");
        }

        // Strict validation for core numeric fields when provided
        foreach (['jw', 'plafon'] as $field) {
            $raw = $data[$field] ?? null;
            if ($raw === null) {
                continue;
            }
            $raw = str_replace(' ', '', (string) $raw);
            if ($raw === '') {
                continue;
            }

            $normalized = $raw;
            if (preg_match('/^-?\d+,\d{1,2}$/', $normalized)) {
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }

            if (!preg_match('/^-?\d+(?:\.\d+)?$/', $normalized)) {
                throw new \Exception("Field '{$field}' tidak valid");
            }
        }

        // Validate date ranges if both dates exist (but don't throw exception)
        if ($tgleff && $tglexp && strtotime($tgleff) > strtotime($tglexp)) {
            Log::warning("Tanggal efektif lebih besar dari tanggal expired pada baris {$lineNumber}");
        }

        // Validate and convert numeric fields with tolerance
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
            $parsedValue = $this->parseNumeric($value);
            if (!is_numeric($parsedValue) && !empty($value)) {
                Log::warning("Field '{$field}' tidak valid pada baris {$lineNumber}, menggunakan 0");
                $parsedValue = 0;
            }
            $validatedData[$field] = $parsedValue;
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

    private function validateTabunganData(array $data, int $lineNumber): array
    {
        // Validate and convert numeric fields
        $numericFields = ['sahirrp', 'saldoblok', 'tax', 'avgeom'];
        $validatedData = [];
        foreach ($numericFields as $field) {
            $value = $data[$field] ?? 0;
            $parsedValue = $this->parseNumeric($value);
            if (!is_numeric($parsedValue) && !empty($value)) {
                Log::warning("Field '{$field}' tidak valid pada baris {$lineNumber}, menggunakan 0");
                $parsedValue = 0;
            }
            $validatedData[$field] = $parsedValue;
        }

        // Validate and convert date fields
        $dateFields = ['tgltrnakh', 'tgllhr'];
        foreach ($dateFields as $field) {
            $validatedData[$field] = $this->parseDate($data[$field] ?? '');
        }

        // Handle string fields with trimming
        $stringFields = ['nocif', 'notab', 'kodeprd', 'fnama', 'namaqq', 'stsrec', 'stsrest', 'stspep', 'kdrisk', 'noid', 'hp', 'nmibu', 'ketsandi', 'namapt', 'kodeloc'];
        foreach ($stringFields as $field) {
            $validatedData[$field] = trim($data[$field] ?? '');
        }

        return array_merge($validatedData, [
            'period_month' => $this->month,
            'period_year' => $this->year,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validateDepositoData(array $data, int $lineNumber): array
    {
        // Validate and convert numeric fields
        $numericFields = ['nomrp', 'nisbah', 'spread', 'equivrate', 'komitrate', 'tambahnom', 'tax', 'bnghtg', 'nisbahrp'];
        $validatedData = [];
        foreach ($numericFields as $field) {
            $value = $data[$field] ?? 0;
            $parsedValue = $this->parseNumeric($value);
            if (!is_numeric($parsedValue) && !empty($value)) {
                Log::warning("Field '{$field}' tidak valid pada baris {$lineNumber}, menggunakan 0");
                $parsedValue = 0;
            }
            $validatedData[$field] = $parsedValue;
        }

        // Validate and convert date fields
        $dateFields = ['tglbuka', 'tgleff', 'tgljtempo', 'tgllhr'];
        foreach ($dateFields as $field) {
            $validatedData[$field] = $this->parseDate($data[$field] ?? '');
        }

        // Handle string fields with trimming
        $stringFields = ['nodep', 'nocif', 'nobilyet', 'nama', 'stsrec', 'kdprd', 'jkwaktu', 'jnsjkwaktu', 'aro', 'ststrn', 'kdwil', 'kodeaoh', 'kodeaop', 'noacbng', 'noid', 'alamat', 'kota', 'telprmh', 'hp', 'stskait', 'golcustbi', 'kelurahan', 'kecamatan', 'kdpos', 'kdrisk', 'stspep', 'nmibu', 'ketsandi', 'namapt'];
        foreach ($stringFields as $field) {
            $validatedData[$field] = trim($data[$field] ?? '');
        }

        return array_merge($validatedData, [
            'period_month' => $this->month,
            'period_year' => $this->year,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validateLinkageData(array $data, int $lineNumber): array
    {
        // Validate that we have the expected number of columns (minimal check)
        if (count($data) < 10) {
            throw new \Exception("Baris tidak lengkap, expected minimal 10 kolom, got " . count($data));
        }

        // Validate and convert numeric fields
        $numericFields = ['prsnisbah', 'plafon', 'os'];
        $validatedData = [];
        foreach ($numericFields as $field) {
            $value = $data[$field] ?? 0;
            $parsedValue = $this->parseNumeric($value);
            if (!is_numeric($parsedValue) && !empty($value)) {
                Log::warning("Field '{$field}' tidak valid pada baris {$lineNumber}, menggunakan 0");
                $parsedValue = 0;
            }
            $validatedData[$field] = $parsedValue;
        }

        // Validate and convert date fields with tolerance
        $tgleff = $data['tgleff'] ?? '';
        $tgljt = $data['tgljt'] ?? '';
        if (!empty($tgleff) && !preg_match('/^\d{8}$/', $tgleff)) {
            Log::warning("Format tanggal efektif tidak valid untuk linkage row {$lineNumber}: {$tgleff}");
        }
        if (!empty($tgljt) && !preg_match('/^\d{8}$/', $tgljt)) {
            Log::warning("Format tanggal jatuh tempo tidak valid untuk linkage row {$lineNumber}: {$tgljt}");
        }
        $validatedData['tgleff'] = $this->parseDate($tgleff);
        $validatedData['tgljt'] = $this->parseDate($tgljt);

        // Handle string fields with trimming
        $stringFields = ['nocif', 'nama', 'nokontrak', 'kelompok', 'jnsakad'];
        foreach ($stringFields as $field) {
            $validatedData[$field] = trim($data[$field] ?? '');
        }

        return array_merge($validatedData, [
            'period_month' => $this->month,
            'period_year' => $this->year,
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

        // Remove any non-numeric characters except dashes, slashes, and dots
        $cleanDate = preg_replace('/[^0-9\-\/\.]/', '', $dateString);

        // Try different date formats commonly used in CSV files
        $formats = [
            'Y-m-d',        // 2023-12-31
            'd/m/Y',        // 31/12/2023
            'd-m-Y',        // 31-12-2023
            'm/d/Y',        // 12/31/2023
            'Y/m/d',        // 2023/12/31
            'd F Y',        // 31 December 2023
            'F d, Y',       // December 31, 2023
            'Ymd',          // 20231231
            'dmY',          // 31122023
            'mdY',          // 12312023
            'ymd',          // 231231 (2-digit year)
            'dmy',          // 311223 (2-digit year)
            'mdy',          // 123123 (2-digit year)
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $cleanDate);
            if ($date !== false) {
                // Validate the date is reasonable (not in future for birth dates, etc.)
                $year = $date->format('Y');
                if ($year >= 1900 && $year <= (date('Y') + 10)) {
                    return $date->format('Y-m-d');
                }
            }
        }

        // Handle special cases like "0180514" -> "20180514"
        if (preg_match('/^(\d{2})(\d{4})$/', $cleanDate, $matches)) {
            $year = '20' . $matches[1];
            $monthDay = $matches[2];
            $date = \DateTime::createFromFormat('Ymd', $year . $monthDay);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Handle cases like "180514" -> "20180514" (assuming 20xx)
        if (preg_match('/^(\d{6})$/', $cleanDate, $matches)) {
            $year = '20' . substr($matches[1], 0, 2);
            $date = \DateTime::createFromFormat('Ymd', $year . substr($matches[1], 2));
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try to parse with strtotime as fallback
        $timestamp = strtotime($cleanDate);
        if ($timestamp !== false) {
            $year = date('Y', $timestamp);
            if ($year >= 1900 && $year <= (date('Y') + 10)) {
                return date('Y-m-d', $timestamp);
            }
        }

        // If all parsing fails, log warning but return null instead of throwing exception
        Log::warning("Format tanggal tidak dapat diparsing, menggunakan null: {$dateString}");
        return null;
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

        // Remove spaces
        $value = str_replace(' ', '', $value);

        // Check if this looks like European decimal format (comma as decimal separator)
        // Pattern: number with single comma followed by 1-2 digits at the end
        if (preg_match('/^-?\d+,\d{1,2}$/', $value)) {
            // European format: replace comma with period
            $value = str_replace(',', '.', $value);
        } else {
            // Remove commas (thousands separators)
            $value = str_replace(',', '', $value);
        }

        return floatval($value);
    }

    /**
     * Delete existing data for the specified period before importing new data
     */
    private function deleteExistingDataForPeriod($jenis, $month, $year)
    {
        try {
            $deletedCount = 0;

            if ($jenis === 'PEMBIAYAAN') {
                $deletedCount = Pembiayaan::where('period_month', $month)
                    ->where('period_year', $year)
                    ->delete();
            } elseif ($jenis === 'TABUNGAN') {
                $deletedCount = Tabungan::where('period_month', $month)
                    ->where('period_year', $year)
                    ->delete();
            } elseif ($jenis === 'DEPOSITO') {
                $deletedCount = Deposito::where('period_month', $month)
                    ->where('period_year', $year)
                    ->delete();
            } elseif ($jenis === 'LINKAGE') {
                $deletedCount = Linkage::where('period_month', $month)
                    ->where('period_year', $year)
                    ->delete();
            }

            if ($deletedCount > 0) {
                Log::info("Deleted {$deletedCount} existing {$jenis} records for period {$month}/{$year}");
            }
        } catch (\Exception $e) {
            Log::error("Error deleting existing data for {$jenis} period {$month}/{$year}: " . $e->getMessage());
            throw $e;
        }
    }
}
