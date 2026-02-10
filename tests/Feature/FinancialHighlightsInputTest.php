<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\FinancialHighlight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialHighlightsInputTest extends TestCase
{
     use RefreshDatabase;

     protected function setUp(): void
     {
          parent::setUp();

          // Posting to web routes requires CSRF; disable for feature tests.
          $this->withoutMiddleware(VerifyCsrfToken::class);
     }

     public function test_admin_can_create_financial_highlight_with_valid_input(): void
     {
          $this->actingAs(User::factory()->create(['role' => 'admin']));

          $payload = [
               'period_year' => 2026,
               'period_month' => 2,
               'car' => 12.5,
               'roa' => 1.75,
               'roe' => 15.2,
               'aset' => 1000000,
               'laba_rugi' => 50000,
               'biaya' => 10000,
               'pendapatan' => 60000,
               'bopo' => 85.1,
               'cash_ratio' => 12.0,
               // Provide auto-calculated fields explicitly to keep the test focused on input.
               'dpk' => 200000,
               'pembiayaan' => 150000,
               'npf' => 1.5,
               'fdr' => 75.0,
          ];

          $response = $this->post('/financial-highlights', $payload);

          $response->assertStatus(302);
          $response->assertRedirect('/financial-highlights');

          $this->assertDatabaseHas('financial_highlights', [
               'period_year' => 2026,
               'period_month' => 2,
          ]);
     }

     public function test_admin_cannot_create_duplicate_period(): void
     {
          $this->actingAs(User::factory()->create(['role' => 'admin']));

          FinancialHighlight::create([
               'period_year' => 2026,
               'period_month' => 2,
               'car' => 10,
          ]);

          $response = $this->from('/financial-highlights/create')
               ->post('/financial-highlights', [
                    'period_year' => 2026,
                    'period_month' => 2,
                    'car' => 11,
               ]);

          $response->assertStatus(302);
          $response->assertRedirect('/financial-highlights/create');
          $response->assertSessionHasErrors(['period']);
     }

     public function test_validation_rejects_out_of_range_values(): void
     {
          $this->actingAs(User::factory()->create(['role' => 'admin']));

          $response = $this->from('/financial-highlights/create')
               ->post('/financial-highlights', [
                    'period_year' => 2019, // min 2020
                    'period_month' => 13, // max 12
                    'car' => 150, // max 100
                    'npf' => -1, // min 0
                    'bopo' => 999, // max 200
               ]);

          $response->assertStatus(302);
          $response->assertRedirect('/financial-highlights/create');
          $response->assertSessionHasErrors(['period_year', 'period_month', 'car', 'npf', 'bopo']);
     }

     public function test_store_autocalculates_fields_when_missing(): void
     {
          $this->actingAs(User::factory()->create(['role' => 'admin']));

          $response = $this->post('/financial-highlights', [
               'period_year' => 2026,
               'period_month' => 1,
               'car' => 10,
          ]);

          $response->assertStatus(302);

          $highlight = FinancialHighlight::where('period_year', 2026)
               ->where('period_month', 1)
               ->first();

          $this->assertNotNull($highlight);
          $this->assertNotNull($highlight->dpk);
          $this->assertNotNull($highlight->pembiayaan);
          $this->assertNotNull($highlight->npf);
          $this->assertNotNull($highlight->fdr);
     }

     public function test_non_admin_cannot_access_store_route(): void
     {
          $this->actingAs(User::factory()->create(['role' => 'funding']));

          $response = $this->post('/financial-highlights', [
               'period_year' => 2026,
               'period_month' => 2,
          ]);

          $response->assertStatus(403);
     }

     public function test_admin_can_update_financial_highlight_input(): void
     {
          $this->actingAs(User::factory()->create(['role' => 'admin']));

          $highlight = FinancialHighlight::create([
               'period_year' => 2026,
               'period_month' => 2,
               'car' => 10,
               'roa' => 1,
               'roe' => 10,
               'aset' => 1000,
               'laba_rugi' => 10,
               'biaya' => 5,
               'pendapatan' => 15,
               'bopo' => 80,
               'cash_ratio' => 10,
               'dpk' => 123,
               'pembiayaan' => 456,
               'npf' => 1.2,
               'fdr' => 50,
          ]);

          $response = $this->put("/financial-highlights/{$highlight->id}", [
               'car' => 12.25,
               'roa' => 2.0,
               'roe' => 14.0,
               'aset' => 2000,
               'laba_rugi' => 20,
               'biaya' => 8,
               'pendapatan' => 28,
               'bopo' => 75.5,
               'cash_ratio' => 11.5,
          ]);

          $response->assertStatus(302);
          $response->assertRedirect('/financial-highlights');

          $highlight->refresh();
          $this->assertEquals(12.25, (float) $highlight->car);
          $this->assertEquals(11.5, (float) $highlight->cash_ratio);
          // Update endpoint always recalculates these fields (sqlite sums -> 0).
          $this->assertNotNull($highlight->dpk);
          $this->assertNotNull($highlight->pembiayaan);
          $this->assertNotNull($highlight->npf);
          $this->assertNotNull($highlight->fdr);
     }

     public function test_update_validation_rejects_out_of_range_values(): void
     {
          $this->actingAs(User::factory()->create(['role' => 'admin']));

          $highlight = FinancialHighlight::create([
               'period_year' => 2026,
               'period_month' => 2,
               'car' => 10,
          ]);

          $response = $this->from("/financial-highlights/{$highlight->id}/edit")
               ->put("/financial-highlights/{$highlight->id}", [
                    'car' => 150,
                    'npf' => -1,
                    'bopo' => 999,
                    'cash_ratio' => 999,
               ]);

          $response->assertStatus(302);
          $response->assertRedirect("/financial-highlights/{$highlight->id}/edit");
          $response->assertSessionHasErrors(['car', 'npf', 'bopo', 'cash_ratio']);
     }

     public function test_non_admin_cannot_update_financial_highlight(): void
     {
          $this->actingAs(User::factory()->create(['role' => 'funding']));

          $highlight = FinancialHighlight::create([
               'period_year' => 2026,
               'period_month' => 2,
               'car' => 10,
          ]);

          $response = $this->put("/financial-highlights/{$highlight->id}", [
               'car' => 11,
          ]);

          $response->assertStatus(403);
     }
}
