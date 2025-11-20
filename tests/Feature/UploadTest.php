<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Linkage;
use Illuminate\Http\UploadedFile;

class UploadTest extends TestCase
{
     use RefreshDatabase;

     /** @test */
     public function it_can_upload_linkage_data_with_same_nokontrak_for_different_periods()
     {
          // Create and authenticate a user with admin role
          $user = User::factory()->create(['role' => 'admin']);
          $this->actingAs($user);

          // Create CSV content for period 1
          $csvContent1 = "nokontrak,nocif,nama,tgleff,tgljt,kelompok,jnsakad,prsnisbah,plafon,os\nLINK001,CIF001,John Doe,2024-01-01,2024-12-31,A,Type1,5.00,1000000.00,500000.00";

          // Create CSV content for period 2 with same nokontrak
          $csvContent2 = "nokontrak,nocif,nama,tgleff,tgljt,kelompok,jnsakad,prsnisbah,plafon,os\nLINK001,CIF001,John Doe,2024-01-01,2024-12-31,A,Type1,5.00,1000000.00,500000.00";

          // Create uploaded files
          $file1 = UploadedFile::fake()->createWithContent('linkage_period1.csv', $csvContent1);
          $file2 = UploadedFile::fake()->createWithContent('linkage_period2.csv', $csvContent2);

          // Upload for period 1
          $response1 = $this->post('/funding/upload', [
               'month' => '01',
               'year' => 2024,
               'upload_types' => ['linkage'],
               'csv_linkage' => $file1
          ]);

          $response1->assertStatus(302); // Redirect after successful upload

          // Verify data was inserted for period 1
          $this->assertDatabaseHas('linkages', [
               'nokontrak' => 'LINK001',
               'period_year' => 2024,
               'period_month' => 1
          ]);

          // Upload for period 2 with same nokontrak
          $response2 = $this->post('/funding/upload', [
               'month' => '02',
               'year' => 2024,
               'upload_types' => ['linkage'],
               'csv_linkage' => $file2
          ]);

          $response2->assertStatus(302); // Should not fail with constraint violation

          // Verify both records exist
          $this->assertDatabaseHas('linkages', [
               'nokontrak' => 'LINK001',
               'period_year' => 2024,
               'period_month' => 1
          ]);

          $this->assertDatabaseHas('linkages', [
               'nokontrak' => 'LINK001',
               'period_year' => 2024,
               'period_month' => 2
          ]);

          // Verify we have 2 records total
          $this->assertEquals(2, Linkage::where('nokontrak', 'LINK001')->count());
     }
}
