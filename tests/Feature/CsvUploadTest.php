<?php

namespace Tests\Feature;

use App\Jobs\ProcessCsvUpload;
use App\Models\CsvUploadStatus;
use App\Models\Pembiayaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvUploadTest extends TestCase
{
     use RefreshDatabase;

     public function test_csv_upload_processes_successfully()
     {
          // Create a sample CSV content
          $csvContent = "nokontrak,nama,tgleff,tglexp,jw,plafon\n" .
               "TEST001,John Doe,2023-01-01,2024-01-01,12,1000000\n" .
               "TEST002,Jane Smith,2023-02-01,2024-02-01,24,2000000";

          // Store the CSV file
          Storage::put('test_upload.csv', $csvContent);

          // Create status record
          $status = CsvUploadStatus::create([
               'user_id' => 1,
               'filename' => 'test_upload.csv',
               'status' => 'queued',
               'total_rows' => 2,
               'month' => '12',
               'year' => '2023',
               'upload_type' => 'pembiayaan'
          ]);

          // Process the job
          $filePaths = ['pembiayaan' => 'test_upload.csv'];
          $statusIds = ['pembiayaan' => $status->id];
          $job = new ProcessCsvUpload($filePaths, '12', '2023', 1, $statusIds);
          $job->handle();

          // Refresh status from database
          $status->refresh();

          // Assert the job completed successfully
          $this->assertEquals('completed', $status->status);
          $this->assertEquals(2, $status->processed_records);
          $this->assertEquals(0, $status->error_count);

          // Assert data was inserted
          $this->assertDatabaseHas('pembiayaans', [
               'nokontrak' => 'TEST001',
               'nama' => 'John Doe',
               'period_month' => '12',
               'period_year' => '2023'
          ]);

          $this->assertDatabaseHas('pembiayaans', [
               'nokontrak' => 'TEST002',
               'nama' => 'Jane Smith',
               'period_month' => '12',
               'period_year' => '2023'
          ]);
     }

     public function test_csv_upload_handles_validation_errors()
     {
          // Create CSV with invalid data
          $csvContent = "nokontrak,nama,tgleff,tglexp,jw,plafon\n" .
               "TEST001,John Doe,invalid-date,2024-01-01,12,1000000\n" .
               "TEST002,Jane Smith,2023-02-01,2024-02-01,not-a-number,2000000";

          Storage::put('test_upload_errors.csv', $csvContent);

          $status = CsvUploadStatus::create([
               'user_id' => 1,
               'filename' => 'test_upload_errors.csv',
               'status' => 'queued',
               'total_rows' => 2,
               'month' => '12',
               'year' => '2023',
               'upload_type' => 'pembiayaan'
          ]);

          $filePaths = ['pembiayaan' => 'test_upload_errors.csv'];
          $statusIds = ['pembiayaan' => $status->id];
          $job = new ProcessCsvUpload($filePaths, '12', '2023', 1, $statusIds);
          $job->handle();

          $status->refresh();

          // Should complete with errors
          $this->assertEquals('completed_with_errors', $status->status);
          $this->assertTrue(count($status->errors ?? []) > 0);

          // No data should be inserted due to validation errors
          $this->assertDatabaseMissing('pembiayaans', [
               'nokontrak' => 'TEST001'
          ]);
     }
}
