<?php

namespace App\Listeners;

use App\Events\FinancialDataUpdated;
use App\Models\FinancialHighlight;
use App\Services\FinancialCacheService;
use Illuminate\Support\Facades\Log;

class RecalculateFinancialHighlights
{
     public function __construct(private readonly FinancialCacheService $cacheService) {}

     /**
      * Handle the event.
      */
     public function handle(FinancialDataUpdated $event): void
     {
          // Only process if this is a data import event
          if ($event->type === 'data_import') {
               $data = $event->data;

               $periodMonth = $data['period_month'];
               $periodYear = $data['period_year'];

               Log::info("Recalculating FinancialHighlights for period {$periodMonth}/{$periodYear}");

               try {
                    // Find or create FinancialHighlight for this period
                    $highlight = FinancialHighlight::where('period_month', $periodMonth)
                         ->where('period_year', $periodYear)
                         ->first();

                    if ($highlight) {
                         // Recalculate auto-calculated fields (dpk, pembiayaan, npf, fdr)
                         // Update only when new calculation > 0. If the new value is 0 and the
                         // existing highlight field is 0 (or empty), fall back to the previous
                         // month's value so a missing import doesn't wipe meaningful data.
                         $prevHighlight = FinancialHighlight::getPreviousPeriod($periodYear, $periodMonth, 'MOM');

                         $autoFields = [
                              'dpk' => 'calculateDpk',
                              'pembiayaan' => 'calculatePembiayaan',
                              'npf' => 'calculateNpf',
                              'fdr' => 'calculateFdr',
                         ];

                         foreach ($autoFields as $field => $calcMethod) {
                              $newVal = FinancialHighlight::{$calcMethod}($periodYear, $periodMonth);

                              if ($newVal > 0) {
                                   $highlight->$field = $newVal;
                              } else {
                                   // newVal is zero: if current value is zero or null, try previous month
                                   if (empty($highlight->$field) || $highlight->$field == 0) {
                                        if ($prevHighlight && !empty($prevHighlight->$field) && $prevHighlight->$field > 0) {
                                             $highlight->$field = $prevHighlight->$field;
                                        }
                                   }
                                   // otherwise keep existing non-zero value
                              }
                         }

                         $highlight->save();

                         $this->cacheService->invalidateDashboardRenderCache();

                         Log::info("Updated FinancialHighlight for period {$periodMonth}/{$periodYear}");
                    } else {
                         // Create new record – seed manually-entered fields from the previous month
                         // so they are not left blank after an import.
                         $highlight = new FinancialHighlight();
                         $highlight->period_month = $periodMonth;
                         $highlight->period_year  = $periodYear;

                         $prevHighlight = FinancialHighlight::getPreviousPeriod($periodYear, $periodMonth, 'MOM');
                         if ($prevHighlight) {
                              $manualFields = ['car', 'roa', 'roe', 'aset', 'laba_rugi', 'biaya', 'pendapatan', 'bopo', 'cash_ratio'];
                              foreach ($manualFields as $field) {
                                   $highlight->$field = $prevHighlight->$field;
                              }
                              Log::info("Seeded manual fields from previous period for {$periodMonth}/{$periodYear}");
                         }

                         // Always calculate the auto-calculated fields from the newly imported data
                         $highlight->calculateDerivedValues();

                         // If any auto-calculated field ended up 0, try to seed from previous month
                         if ($prevHighlight) {
                              $autoFields = ['dpk', 'pembiayaan', 'npf', 'fdr'];
                              foreach ($autoFields as $field) {
                                   if (empty($highlight->$field) || $highlight->$field == 0) {
                                        if (!empty($prevHighlight->$field) && $prevHighlight->$field > 0) {
                                             $highlight->$field = $prevHighlight->$field;
                                        }
                                   }
                              }
                         }

                         $highlight->save();

                         $this->cacheService->invalidateDashboardRenderCache();

                         Log::info("Created new FinancialHighlight for period {$periodMonth}/{$periodYear}");
                    }
               } catch (\Exception $e) {
                    Log::error("Error recalculating FinancialHighlight for period {$periodMonth}/{$periodYear}: " . $e->getMessage());
               }
          }
     }
}
