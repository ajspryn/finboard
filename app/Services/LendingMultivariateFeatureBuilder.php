<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LendingMultivariateFeatureBuilder
{
     /**
      * Build multivariate training rows for Lending (Outstanding).
      *
      * Each row corresponds to a month t and uses:
      * - lag_outstanding = outstanding(t-1)
      * - dpk_total(t)
      * - npf_ratio(t)
      * - inflation_yoy(t)
      * - bi_rate(t)
      * Target y = outstanding(t)
      */
     public function buildTrainingRows(int $endYear, int $endMonth, int $maxMonths): array
     {
          $periods = $this->collectPembiayaanAggregatesUpTo($endYear, $endMonth);
          if (count($periods) > $maxMonths) {
               $periods = array_slice($periods, -$maxMonths);
          }

          if (count($periods) < 2) {
               return [];
          }

          $macroByPeriod = $this->loadMacroIndicatorsForPeriods($periods);
          $dpkByPeriod = $this->loadDpkTotalsForPeriods($periods);

          $rows = [];
          for ($i = 1; $i < count($periods); $i++) {
               $prev = $periods[$i - 1];
               $curr = $periods[$i];

               $currKey = ((int)$curr['year']) * 100 + (int)$curr['month'];
               $macro = $macroByPeriod[$currKey] ?? ['bi_rate' => 0.0, 'inflation_yoy' => 0.0];
               $dpk = $dpkByPeriod[$currKey] ?? 0.0;

               $rows[] = [
                    'year' => $curr['year'],
                    'month' => $curr['month'],
                    'y' => (float)$curr['outstanding'],
                    'features' => [
                         'lag_outstanding' => (float)$prev['outstanding'],
                         'dpk_total' => (float)$dpk,
                         'npf_ratio' => (float)$curr['npf_ratio'],
                         'macro_bi_rate' => (float)($macro['bi_rate'] ?? 0.0),
                         'macro_inflation_yoy' => (float)($macro['inflation_yoy'] ?? 0.0),
                    ],
               ];
          }

          return $rows;
     }

     /**
      * Project next-period features.
      *
      * - lag_* features are taken from the last observed period (cutoff).
      * - volume/count features are projected using average growth across history.
      * - avg_jw is kept stable.
      */
     public function projectNextFeatures(array $trainingRows): array
     {
          if (!$trainingRows) {
               return [];
          }

          $last = $trainingRows[count($trainingRows) - 1];
          $featureKeys = array_keys($last['features'] ?? []);
          if (!$featureKeys) {
               return [];
          }

          $avgGrowth = [];
          foreach ($featureKeys as $k) {
               if (str_starts_with($k, 'lag_') || $k === 'avg_jw') {
                    $avgGrowth[$k] = 0.0;
                    continue;
               }

               if (str_starts_with($k, 'macro_')) {
                    $avgGrowth[$k] = 0.0;
                    continue;
               }

               $growths = [];
               for ($i = 1; $i < count($trainingRows); $i++) {
                    $prevVal = (float)($trainingRows[$i - 1]['features'][$k] ?? 0);
                    $curVal = (float)($trainingRows[$i]['features'][$k] ?? 0);
                    if (abs($prevVal) < 1e-9) {
                         $growths[] = 0.0;
                         continue;
                    }
                    $growths[] = ($curVal - $prevVal) / $prevVal;
               }
               $avgGrowth[$k] = $growths ? (array_sum($growths) / count($growths)) : 0.0;
          }

          $next = [];
          foreach ($featureKeys as $k) {
               $base = (float)($last['features'][$k] ?? 0);

               if (str_starts_with($k, 'lag_')) {
                    $next[$k] = $base;
                    continue;
               }

               if ($k === 'avg_jw') {
                    $next[$k] = $base;
                    continue;
               }

               if (str_starts_with($k, 'macro_')) {
                    $next[$k] = $base;
                    continue;
               }

               $g = (float)($avgGrowth[$k] ?? 0);
               $next[$k] = $base * (1.0 + $g);
          }

          return $next;
     }

     private function loadMacroIndicatorsForPeriods(array $periods): array
     {
          if (!$periods) {
               return [];
          }

          $min = $periods[0];
          $max = $periods[count($periods) - 1];
          $minKey = ((int)$min['year']) * 100 + (int)$min['month'];
          $maxKey = ((int)$max['year']) * 100 + (int)$max['month'];

          $rows = DB::table('macro_indicators')
               ->select('period_year', 'period_month', 'bi_rate', 'inflation_yoy')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$maxKey])
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $raw = [];
          foreach ($rows as $r) {
               $key = ((int)$r->period_year) * 100 + (int)$r->period_month;
               $raw[$key] = [
                    'bi_rate' => $r->bi_rate !== null ? (float)$r->bi_rate : null,
                    'inflation_yoy' => $r->inflation_yoy !== null ? (float)$r->inflation_yoy : null,
               ];
          }

          $cursorYear = (int)$min['year'];
          $cursorMonth = (int)$min['month'];
          $endYear = (int)$max['year'];
          $endMonth = (int)$max['month'];

          $lastBi = null;
          $lastInfl = null;

          if ($raw) {
               $seedKeys = array_keys($raw);
               sort($seedKeys);
               foreach ($seedKeys as $k) {
                    if ((int)$k > (int)$minKey) {
                         break;
                    }
                    $row = $raw[$k] ?? null;
                    if ($row && $row['bi_rate'] !== null) {
                         $lastBi = (float)$row['bi_rate'];
                    }
                    if ($row && $row['inflation_yoy'] !== null) {
                         $lastInfl = (float)$row['inflation_yoy'];
                    }
               }
          }
          $out = [];

          while ($cursorYear * 100 + $cursorMonth <= $endYear * 100 + $endMonth) {
               $key = $cursorYear * 100 + $cursorMonth;
               $row = $raw[$key] ?? null;

               if ($row && $row['bi_rate'] !== null) {
                    $lastBi = (float)$row['bi_rate'];
               }
               if ($row && $row['inflation_yoy'] !== null) {
                    $lastInfl = (float)$row['inflation_yoy'];
               }

               $out[$key] = [
                    'bi_rate' => $lastBi !== null ? (float)$lastBi : 0.0,
                    'inflation_yoy' => $lastInfl !== null ? (float)$lastInfl : 0.0,
               ];

               $cursorMonth++;
               if ($cursorMonth > 12) {
                    $cursorMonth = 1;
                    $cursorYear++;
               }
          }

          return $out;
     }

     public function toMatrices(array $trainingRows, array $featureOrder): array
     {
          $X = [];
          $y = [];
          foreach ($trainingRows as $row) {
               $xRow = [];
               foreach ($featureOrder as $k) {
                    $xRow[] = (float)($row['features'][$k] ?? 0);
               }
               $X[] = $xRow;
               $y[] = (float)$row['y'];
          }

          return [$X, $y];
     }

     private function collectPembiayaanAggregatesUpTo(int $endYear, int $endMonth): array
     {
          $endPeriod = $endYear * 100 + $endMonth;

          $rows = DB::table('pembiayaans')
               ->select(
                    'period_year',
                    'period_month',
                    DB::raw('SUM(osmdlc) as outstanding'),
                    DB::raw("SUM(CASE WHEN colbaru IN ('3','4','5') THEN osmdlc ELSE 0 END) as npf_os")
               )
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $out = [];
          foreach ($rows as $r) {
               $year = (int)$r->period_year;
               $month = (int)$r->period_month;
               $total = (float)$r->outstanding;
               $npf = (float)$r->npf_os;
               $ratio = $total > 1e-9 ? ($npf / $total) * 100.0 : 0.0;
               $out[] = [
                    'year' => $year,
                    'month' => $month,
                    'outstanding' => $total,
                    'npf_ratio' => (float)$ratio,
               ];
          }

          return $out;
     }

     /**
      * @return array<int,float> Map periodKey (YYYYMM int) => DPK total (tabungan + deposito)
      */
     private function loadDpkTotalsForPeriods(array $periods): array
     {
          if (!$periods) {
               return [];
          }

          $min = $periods[0];
          $max = $periods[count($periods) - 1];
          $minKey = ((int)$min['year']) * 100 + (int)$min['month'];
          $maxKey = ((int)$max['year']) * 100 + (int)$max['month'];

          $tabAgg = DB::table('tabungans')
               ->select('period_year', 'period_month', DB::raw('SUM(sahirrp) as total'))
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) >= ?', [$minKey])
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$maxKey])
               ->groupBy('period_year', 'period_month')
               ->get();

          $depAgg = DB::table('depositos')
               ->select('period_year', 'period_month', DB::raw('SUM(nomrp) as total'))
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) >= ?', [$minKey])
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$maxKey])
               ->groupBy('period_year', 'period_month')
               ->get();

          $out = [];
          foreach ($tabAgg as $r) {
               $key = ((int)$r->period_year) * 100 + (int)$r->period_month;
               $out[$key] = ($out[$key] ?? 0.0) + (float)($r->total ?? 0.0);
          }
          foreach ($depAgg as $r) {
               $key = ((int)$r->period_year) * 100 + (int)$r->period_month;
               $out[$key] = ($out[$key] ?? 0.0) + (float)($r->total ?? 0.0);
          }

          // Forward-fill missing months to keep alignment stable.
          $cursorYear = (int)$min['year'];
          $cursorMonth = (int)$min['month'];
          $endYear = (int)$max['year'];
          $endMonth = (int)$max['month'];

          $last = 0.0;
          $filled = [];
          while ($cursorYear * 100 + $cursorMonth <= $endYear * 100 + $endMonth) {
               $key = $cursorYear * 100 + $cursorMonth;
               if (array_key_exists($key, $out)) {
                    $last = (float)$out[$key];
               }
               $filled[$key] = (float)$last;

               $cursorMonth++;
               if ($cursorMonth > 12) {
                    $cursorMonth = 1;
                    $cursorYear++;
               }
          }

          return $filled;
     }
}
