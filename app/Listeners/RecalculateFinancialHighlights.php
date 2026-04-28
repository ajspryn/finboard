<?php

namespace App\Listeners;

use App\Events\FinancialDataUpdated;
use App\Models\FinancialHighlight;
use Illuminate\Support\Facades\Log;

class RecalculateFinancialHighlights
{
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
                         // but only overwrite existing non-zero values if the new calculation also returns non-zero.
                         // This prevents a CSV import for a different data type from resetting good values to 0.
                         $newDpk        = FinancialHighlight::calculateDpk($periodYear, $periodMonth);
                         $newPembiayaan = FinancialHighlight::calculatePembiayaan($periodYear, $periodMonth);
                         $newNpf        = FinancialHighlight::calculateNpf($periodYear, $periodMonth);
                         $newFdr        = FinancialHighlight::calculateFdr($periodYear, $periodMonth);

                         if ($newDpk > 0 || !$highlight->dpk)               $highlight->dpk        = $newDpk;
                         if ($newPembiayaan > 0 || !$highlight->pembiayaan)  $highlight->pembiayaan = $newPembiayaan;
                         if ($newNpf > 0 || !$highlight->npf)                $highlight->npf        = $newNpf;
                         if ($newFdr > 0 || !$highlight->fdr)                $highlight->fdr        = $newFdr;

                         $highlight->save();

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
                         $highlight->save();

                         Log::info("Created new FinancialHighlight for period {$periodMonth}/{$periodYear}");
                    }
               } catch (\Exception $e) {
                    Log::error("Error recalculating FinancialHighlight for period {$periodMonth}/{$periodYear}: " . $e->getMessage());
               }
          }
     }
}
