<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NpfMultivariateFeatureBuilder
{
     /**
      * Build monthly rows for NPF ratio forecasting using the PDF sliding-window approach.
      *
      * Each row corresponds to one month and contains:
      * - npf_ratio (%), outstanding (pokok), tunggakan_pokok (pokok NPF), rate_eff (%)
      */
     public function buildSlidingWindowRowsForNpfRatio(int $endYear, int $endMonth, int $maxMonths): array
     {
          $periods = $this->collectPembiayaanAggregatesUpTo($endYear, $endMonth);
          if (count($periods) > $maxMonths) {
               $periods = array_slice($periods, -$maxMonths);
          }

          $out = [];
          foreach ($periods as $p) {
               $out[] = [
                    'year' => (int)($p['year'] ?? 0),
                    'month' => (int)($p['month'] ?? 0),
                    'npf_ratio' => (float)($p['npf_ratio'] ?? 0.0),
                    'outstanding' => (float)($p['total_os'] ?? 0.0),
                    'tunggakan_pokok' => (float)($p['tunggakan_pokok'] ?? 0.0),
                    'rate_eff' => (float)($p['rate_eff'] ?? 0.0),
               ];
          }

          return $out;
     }

     public function buildTrainingRowsForTotalOs(int $endYear, int $endMonth, int $maxMonths): array
     {
          $periods = $this->collectPembiayaanAggregatesUpTo($endYear, $endMonth);
          if (count($periods) > $maxMonths) {
               $periods = array_slice($periods, -$maxMonths);
          }
          if (count($periods) < 2) {
               return [];
          }

          $macroByPeriod = $this->loadMacroIndicatorsForPeriods($periods);

          $rows = [];
          for ($i = 1; $i < count($periods); $i++) {
               $prev = $periods[$i - 1];
               $curr = $periods[$i];

               $currKey = ((int)$curr['year']) * 100 + (int)$curr['month'];
               $macro = $macroByPeriod[$currKey] ?? ['bi_rate' => 0.0, 'inflation_yoy' => 0.0];

               $rows[] = [
                    'year' => $curr['year'],
                    'month' => $curr['month'],
                    'y' => (float)$curr['total_os'],
                    'features' => [
                         'lag_total_os' => (float)$prev['total_os'],
                         'contracts_count' => (float)$curr['contracts_count'],
                         'customers_count' => (float)$curr['customers_count'],
                         'plafon_total' => (float)$curr['plafon_total'],
                         'avg_jw' => (float)$curr['avg_jw'],
                         'lag_npf_ratio' => (float)$prev['npf_ratio'],
                         'macro_bi_rate' => (float)($macro['bi_rate'] ?? 0.0),
                         'macro_inflation_yoy' => (float)($macro['inflation_yoy'] ?? 0.0),
                    ],
               ];
          }

          return $rows;
     }

     public function buildTrainingRowsForNpfOs(int $endYear, int $endMonth, int $maxMonths): array
     {
          $periods = $this->collectPembiayaanAggregatesUpTo($endYear, $endMonth);
          if (count($periods) > $maxMonths) {
               $periods = array_slice($periods, -$maxMonths);
          }
          if (count($periods) < 2) {
               return [];
          }

          $macroByPeriod = $this->loadMacroIndicatorsForPeriods($periods);

          $rows = [];
          for ($i = 1; $i < count($periods); $i++) {
               $prev = $periods[$i - 1];
               $curr = $periods[$i];

               $currKey = ((int)$curr['year']) * 100 + (int)$curr['month'];
               $macro = $macroByPeriod[$currKey] ?? ['bi_rate' => 0.0, 'inflation_yoy' => 0.0];

               $rows[] = [
                    'year' => $curr['year'],
                    'month' => $curr['month'],
                    'y' => (float)$curr['npf_os'],
                    'features' => [
                         'lag_npf_os' => (float)$prev['npf_os'],
                         'lag_total_os' => (float)$prev['total_os'],
                         'npf_contracts_count' => (float)$curr['npf_contracts_count'],
                         'contracts_count' => (float)$curr['contracts_count'],
                         'customers_count' => (float)$curr['customers_count'],
                         'avg_haritgkmdl' => (float)$curr['avg_haritgkmdl'],
                         'avg_jw' => (float)$curr['avg_jw'],
                         'lag_npf_ratio' => (float)$prev['npf_ratio'],
                         'macro_bi_rate' => (float)($macro['bi_rate'] ?? 0.0),
                         'macro_inflation_yoy' => (float)($macro['inflation_yoy'] ?? 0.0),
                    ],
               ];
          }

          return $rows;
     }

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
               if (str_starts_with($k, 'lag_') || $k === 'avg_jw' || $k === 'avg_haritgkmdl') {
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

               if (str_starts_with($k, 'lag_') || $k === 'avg_jw' || $k === 'avg_haritgkmdl') {
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
                    DB::raw('SUM(osmdlc) as total_os'),
                    DB::raw("SUM(CASE WHEN colbaru IN ('3','4','5') THEN osmdlc ELSE 0 END) as npf_os"),
                    DB::raw("SUM(CASE WHEN colbaru IN ('3','4','5') THEN COALESCE(tgkpok, 0) ELSE 0 END) as tunggakan_pokok"),
                    DB::raw('AVG(COALESCE(mgnawal, 0) * 1.1) as rate_eff'),
                    DB::raw('COUNT(DISTINCT nokontrak) as contracts_count'),
                    DB::raw('COUNT(DISTINCT nocif) as customers_count'),
                    DB::raw("COUNT(CASE WHEN colbaru IN ('3','4','5') THEN 1 END) as npf_contracts_count"),
                    DB::raw('SUM(COALESCE(plafon, 0)) as plafon_total'),
                    DB::raw('AVG(COALESCE(jw, 0)) as avg_jw'),
                    DB::raw('AVG(COALESCE(haritgkmdl, 0)) as avg_haritgkmdl')
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
               $total = (float)$r->total_os;
               $npf = (float)$r->npf_os;
               $ratio = $total > 1e-9 ? ($npf / $total) * 100.0 : 0.0;

               $customers = (int)$r->customers_count;
               if ($customers <= 0) {
                    $customers = (int)$r->contracts_count;
               }

               $out[] = [
                    'year' => $year,
                    'month' => $month,
                    'total_os' => $total,
                    'npf_os' => $npf,
                    'npf_ratio' => $ratio,
                    'tunggakan_pokok' => $r->tunggakan_pokok !== null ? (float)$r->tunggakan_pokok : 0.0,
                    'rate_eff' => $r->rate_eff !== null ? (float)$r->rate_eff : 0.0,
                    'contracts_count' => (int)$r->contracts_count,
                    'customers_count' => $customers,
                    'npf_contracts_count' => (int)$r->npf_contracts_count,
                    'plafon_total' => (float)$r->plafon_total,
                    'avg_jw' => (float)$r->avg_jw,
                    'avg_haritgkmdl' => (float)$r->avg_haritgkmdl,
               ];
          }

          return $out;
     }
}
