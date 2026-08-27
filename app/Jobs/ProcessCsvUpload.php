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

    public $timeout = 600;
    public $tries = 3;

    protected $filePaths;
    protected $month;
    protected $year;
    protected $userId;
    protected $statusIds;
    protected ?string $filePath = null;

    public function __construct(array $filePaths, string $month, string $year, int $userId, array $statusIds)
    {
        $this->filePaths = $filePaths;
        $this->month = $month;
        $this->year = $year;
        $this->userId = $userId;
        $this->statusIds = $statusIds;
    }

    public function handle(): void
    {
        Pembiayaan::$disableElasticsearchIndexing = true;
        Tabungan::$disableElasticsearchIndexing = true;
        Deposito::$disableElasticsearchIndexing = true;
        Linkage::$disableElasticsearchIndexing = true;

        DB::disableQueryLog();
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::statement('SET SQL_MODE="";');
            DB::statement('SET SESSION bulk_insert_buffer_size = 268435456;');
        }

        $statusRecords = CsvUploadStatus::whereIn('id', array_values($this->statusIds))->get()->keyBy('upload_type');

        $lockName = "csv_import_{$this->year}_{$this->month}";
        $gotLock = false;
        try {
            $res = DB::select('SELECT GET_LOCK(?, 10) as got_lock', [$lockName]);
            if (!empty($res) && isset($res[0]->got_lock) && intval($res[0]->got_lock) === 1) {
                $gotLock = true;
            } else {
                Log::warning("Could not acquire import lock {$lockName}, another import may be running.");
                throw new \Exception("Import lock busy for period {$this->month}/{$this->year}");
            }
        } catch (\Exception $e) {
            throw $e;
        }

        try {
            $totalImported = 0;
            $totalErrors = 0;
            $allErrorDetails = [];
            $results = [];

            foreach ($this->filePaths as $type => $filePath) {
                if (isset($statusRecords[$type])) {
                    $status = $statusRecords[$type];
                    $status->update([
                        'status' => 'processing',
                        'message' => "Memvalidasi dan memproses data " . ucfirst($type) . "..."
                    ]);

                    if (!Storage::exists($filePath)) {
                        $msg = "File tidak ditemukan: {$filePath}";
                        Log::error($msg);
                        $status->update([
                            'status' => 'error',
                            'message' => $msg,
                            'error_count' => 1,
                            'errors' => [$msg]
                        ]);
                        continue;
                    }

                    $this->deleteExistingDataForPeriod(strtoupper($type), $this->month, $this->year);

                    $result = $this->processCsvFileOptimized(Storage::path($filePath), strtoupper($type), $this->month, $this->year);

                    $results[$type] = $result;
                    $totalImported += $result['imported'];
                    $totalErrors += $result['errors'];

                    $rawErrors = $result['errorDetails'] ?? [];
                    $sanitizedErrors = [];
                    foreach ($rawErrors as $err) {
                        if (is_string($err)) {
                            $sanitizedErrors[] = @iconv('UTF-8', 'UTF-8//IGNORE', $err);
                        } else {
                            $sanitizedErrors[] = $err;
                        }
                    }

                    $status->update([
                        'status' => $result['errors'] > 0 ? 'completed_with_errors' : 'completed',
                        'processed_records' => $result['imported'],
                        'error_count' => $result['errors'],
                        'errors' => $sanitizedErrors ?: null,
                        'message' => ucfirst($type) . ": {$result['imported']} record berhasil diimport" .
                            ($result['errors'] > 0 ? ", {$result['errors']} error" : "")
                    ]);

                    if (!empty($result['errorDetails'])) {
                        $allErrorDetails = array_merge($allErrorDetails, $result['errorDetails']);
                    }
                }
            }

            foreach ($this->filePaths as $filePath) {
                Storage::delete($filePath);
            }

            \App\Events\FinancialDataUpdated::dispatch([
                'period_month' => $this->month,
                'period_year' => $this->year,
                'types_updated' => array_keys($this->filePaths),
                'total_imported' => $totalImported,
                'total_errors' => $totalErrors
            ], 'data_import');
        } catch (\Exception $e) {
            foreach ($statusRecords as $status) {
                $status->update([
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            foreach ($this->filePaths as $filePath) {
                Storage::delete($filePath);
            }
            Log::error('ProcessCsvUpload Error: ' . $e->getMessage());
            throw $e;
        } finally {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                DB::statement('SET UNIQUE_CHECKS=1;');
            }
            DB::disableQueryLog();

            Pembiayaan::$disableElasticsearchIndexing = false;
            Tabungan::$disableElasticsearchIndexing = false;
            Deposito::$disableElasticsearchIndexing = false;
            Linkage::$disableElasticsearchIndexing = false;

            try {
                if (!empty($gotLock) && !empty($lockName)) {
                    DB::select('SELECT RELEASE_LOCK(?)', [$lockName]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to release import lock: ' . $e->getMessage());
            }
        }
    }

    private function processCsvFileOptimized($filePath, $jenis, $month, $year)
    {
        DB::beginTransaction();
        $transactionOpened = true;

        $handle = fopen($filePath, 'r');
        if (fgets($handle, 4) !== "\xef\xbb\xbf") {
            rewind($handle);
        }
        $header = fgetcsv($handle, 0, ',', '"');
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $errorDetails = [];
        $batchData = [];
        $batchSize = 20;

        $lineNumber = 1;

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

                if (count($batchData) >= $batchSize) {
                    try {
                        $imported += $this->bulkInsertRecords($batchData, $jenis);
                    } catch (\Exception $e) {
                        Log::warning("Batch insert failed for {$jenis}, falling back to single inserts.");
                        foreach ($batchData as $singleRecord) {
                            try {
                                $imported += $this->bulkInsertRecords([$singleRecord], $jenis);
                            } catch (\Exception $innerE) {
                                $errors++;
                                $errorDetails[] = "{$jenis} Gagal di baris tertentu: " . $innerE->getMessage();
                            }
                        }
                    }
                    $batchData = [];
                }
            } catch (\Exception $e) {
                $errors++;
                $errorDetails[] = "{$jenis} Baris {$lineNumber}: " . $e->getMessage();
                Log::error("Error importing {$jenis} line {$lineNumber}: " . $e->getMessage());
            }
        }

        if (!empty($batchData)) {
            try {
                $imported += $this->bulkInsertRecords($batchData, $jenis);
            } catch (\Exception $e) {
                Log::warning("Final batch insert failed for {$jenis}, falling back to single inserts.");
                foreach ($batchData as $singleRecord) {
                    try {
                        $imported += $this->bulkInsertRecords([$singleRecord], $jenis);
                    } catch (\Exception $innerE) {
                        $errors++;
                        $errorDetails[] = "{$jenis} Gagal di sisa baris: " . $innerE->getMessage();
                    }
                }
            }
        }

        fclose($handle);

        try {
            if (!empty($transactionOpened)) {
                DB::commit();
            }
        } catch (\Exception $e) {
            if (!empty($transactionOpened)) {
                DB::rollBack();
            }
            throw $e;
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
            'errorDetails' => $errorDetails
        ];
    }

    private function createRecordFromCsvRow($data, $header, $jenis, $lineNumber, $month, $year)
    {
        $data = $this->normalizeCsvRowLength($header, $data, $jenis, $lineNumber);
        $csvRow = array_combine(array_map('strtolower', array_map('trim', $header)), $data);

        try {
            if ($jenis === 'PEMBIAYAAN') {
                return $this->validateAndConvertData($csvRow, $lineNumber);
            } elseif ($jenis === 'TABUNGAN') {
                return $this->validateTabunganData($csvRow, $lineNumber);
            } elseif ($jenis === 'DEPOSITO') {
                return $this->validateDepositoData($csvRow, $lineNumber);
            } elseif ($jenis === 'LINKAGE') {
                return $this->validateLinkageData($csvRow, $lineNumber);
            }
        } catch (\Exception $e) {
            Log::warning("Error parsing {$jenis} row {$lineNumber}: " . $e->getMessage());
            return ['__invalid' => true, 'message' => $e->getMessage()];
        }

        return null;
    }

    private function normalizeCsvRowLength(array $header, array $data, string $jenis, int $lineNumber): array
    {
        $headerCount = count($header);
        $dataCount = count($data);

        if ($headerCount === $dataCount) {
            return $data;
        }

        if ($dataCount > $headerCount) {
            Log::warning("CSV {$jenis} row {$lineNumber} extra columns ({$dataCount} > {$headerCount}).");
            return array_slice($data, 0, $headerCount);
        }

        Log::warning("CSV {$jenis} row {$lineNumber} missing columns ({$dataCount} < {$headerCount}).");
        return array_pad($data, $headerCount, '');
    }

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
                $filteredData = [];
                foreach ($batchData as $record) {
                    $nokontrak = $record['nokontrak'] ?? '';
                    $periodYear = $record['period_year'] ?? '';
                    $periodMonth = $record['period_month'] ?? '';

                    if (empty($nokontrak) || empty($periodYear) || empty($periodMonth)) continue;

                    $exists = DB::table('linkages')
                        ->where('nokontrak', $nokontrak)
                        ->where('period_year', $periodYear)
                        ->where('period_month', $periodMonth)
                        ->exists();

                    if (!$exists) {
                        $filteredData[] = $record;
                    }
                }
                if (!empty($filteredData)) {
                    Linkage::insert($filteredData);
                    $count = count($filteredData);
                } else {
                    $count = 0;
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $count;
    }

    private function validateAndConvertData(array $data, int $lineNumber): array
    {
        $tgleff = $this->parseDate($data['tgleff'] ?? '');
        $tglexp = $this->parseDate($data['tglexp'] ?? '');

        if (!empty($data['tgleff'] ?? null) && $tgleff === null) {
            throw new \Exception("Field 'tgleff' tidak valid");
        }
        if (!empty($data['tglexp'] ?? null) && $tglexp === null) {
            throw new \Exception("Field 'tglexp' tidak valid");
        }

        $numericFields = ['jw', 'mdlawal', 'mgnawal', 'osmdlc', 'osmgnc', 'angsmdl', 'angsmgn', 'sahirrp', 'tgkpok', 'tgkmgn', 'tgkdnd', 'haritgkmdl', 'haritgkmgn', 'tgkharilanjut', 'angs_ke', 'angske_x', 'plafon'];
        $validatedData = [];

        foreach ($numericFields as $field) {
            $value = $data[$field] ?? 0;
            $validatedData[$field] = $this->parseNumeric($value);
        }

        $nullableIntFields = ['blntgkpok', 'blntgkmgn', 'blntgkdnd'];
        foreach ($nullableIntFields as $field) {
            $value = $data[$field] ?? null;
            $validatedData[$field] = (!empty($value) && is_numeric($value)) ? (int)$value : null;
        }

        $stringFields = ['nama', 'colbaru', 'kdaoh', 'acpok', 'alamat', 'telprmh', 'hp', 'fnama', 'kdkolek', 'kdgroupdeb', 'kdgroupdana', 'nocif', 'kdprd', 'pokpby', 'kdloc', 'kelurahan', 'kecamatan', 'kota', 'nmao', 'colllanjut', 'kdmco', 'kdsektor', 'kdsub', 'tagmdl', 'tagmgn', 'inptgl'];
        foreach ($stringFields as $field) {
            $validatedData[$field] = trim($data[$field] ?? '');
        }

        $validatedData['haritgkmdl'] = (int)($data['HARITGKMDL'] ?? $data['haritgkmdl'] ?? 0);
        $validatedData['haritgkmgn'] = (int)($data['HARITGKMGN'] ?? $data['haritgkmgn'] ?? 0);

        return array_merge($validatedData, [
            'nokontrak' => trim($data['nokontrak'] ?? ''),
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
        $numericFields = ['sahirrp', 'saldoblok', 'tax', 'avgeom'];
        $validatedData = [];
        foreach ($numericFields as $field) {
            $value = $data[$field] ?? 0;
            $validatedData[$field] = $this->parseNumeric($value);
        }

        $dateFields = ['tgltrnakh', 'tgllhr'];
        foreach ($dateFields as $field) {
            $validatedData[$field] = $this->parseDate($data[$field] ?? '');
        }

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
        $numericFields = ['nomrp', 'nisbah', 'spread', 'equivrate', 'komitrate', 'tambahnom', 'tax', 'bnghtg', 'nisbahrp'];
        $validatedData = [];
        foreach ($numericFields as $field) {
            $value = $data[$field] ?? 0;
            $validatedData[$field] = $this->parseNumeric($value);
        }

        $dateFields = ['tglbuka', 'tgleff', 'tgljtempo', 'tgllhr'];
        foreach ($dateFields as $field) {
            $validatedData[$field] = $this->parseDate($data[$field] ?? '');
        }

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
        $numericFields = ['prsnisbah', 'plafon', 'os'];
        $validatedData = [];
        foreach ($numericFields as $field) {
            $value = $data[$field] ?? 0;
            $validatedData[$field] = $this->parseNumeric($value);
        }

        $validatedData['tgleff'] = $this->parseDate($data['tgleff'] ?? '');
        $validatedData['tgljt'] = $this->parseDate($data['tgljt'] ?? '');

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

    private function parseDate(string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        $cleanDate = preg_replace('/[^0-9\-\/\.]/', '', $dateString);
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'Ymd', 'dmY', 'mdY', 'ymd', 'dmy', 'mdy'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $cleanDate);
            if ($date !== false) {
                $year = $date->format('Y');
                if ($year >= 1900) { // Batas maksimal dihapus agar bisa menampung tanggal masa depan berapapun
                    return $date->format('Y-m-d');
                }
            }
        }

        if (preg_match('/^(\d{2})(\d{4})$/', $cleanDate, $matches)) {
            $year = '20' . $matches[1];
            $date = \DateTime::createFromFormat('Ymd', $year . $matches[2]);
            if ($date !== false) return $date->format('Y-m-d');
        }

        if (preg_match('/^(\d{6})$/', $cleanDate, $matches)) {
            $year = '20' . substr($matches[1], 0, 2);
            $date = \DateTime::createFromFormat('Ymd', $year . substr($matches[1], 2));
            if ($date !== false) return $date->format('Y-m-d');
        }

        return null;
    }

    private function parseNumeric($value)
    {
        if (empty($value)) return 0;

        $value = trim((string) $value);

        if ($value === 'N' || $value === '-' || $value === 'null') return 0;

        if (preg_match('/^[0-9\-\.]+,\d{1,4}$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        return floatval($value);
    }

    private function deleteExistingDataForPeriod($jenis, $month, $year)
    {
        try {
            if ($jenis === 'PEMBIAYAAN') {
                Pembiayaan::where('period_month', $month)->where('period_year', $year)->delete();
            } elseif ($jenis === 'TABUNGAN') {
                Tabungan::where('period_month', $month)->where('period_year', $year)->delete();
            } elseif ($jenis === 'DEPOSITO') {
                Deposito::where('period_month', $month)->where('period_year', $year)->delete();
            } elseif ($jenis === 'LINKAGE') {
                Linkage::where('period_month', $month)->where('period_year', $year)->delete();
            }
        } catch (\Exception $e) {
            throw $e;
        }
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

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format($value, 2) . ' ' . $units[$unitIndex];
    }

    private function readCsvFile(): array
    {
        $filePath = Storage::path($this->filePath);
        $data = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            if (!$header) throw new \Exception('Tidak dapat membaca header CSV');

            $header = array_map('strtolower', array_map('trim', $header));

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
