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
                         // Update existing record with recalculated values
                         $highlight->calculateDerivedValues();
                         $highlight->save();

                         Log::info("Updated FinancialHighlight for period {$periodMonth}/{$periodYear}");
                    } else {
                         // Create new record if it doesn't exist
                         $highlight = new FinancialHighlight();
                         $highlight->period_month = $periodMonth;
                         $highlight->period_year = $periodYear;
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
