<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FundingMultivariateFeatureBuilder
{
     /**
      * Build multivariate training rows for Funding (DPK).
      *
      * Each row corresponds to a month t and uses:
      * - lag_funding_total = funding_total(t-1)
      * - tabungan_count(t), deposito_count(t)
      * - tabungan_new_total(t), tabungan_withdrawn_total(t) computed from t-1 -> t
      * - deposito_net_inflow_total(t) = buka_total(t) - cair_total(t) computed from t-1 -> t
      * - deposito_rate_{avg,max,min}(t)
      * Target y = funding_total(t)
      */
     public function buildTrainingRows(int $endYear, int $endMonth, int $maxMonths): array
     {
          $periods = $this->collectFundingPeriodsUpTo($endYear, $endMonth);
          if (count($periods) > $maxMonths) {
               $periods = array_slice($periods, -$maxMonths);
          }

          // Need at least 2 months to compute lag + flows.
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

               $tabFlows = $this->computeTabunganFlows($prev['year'], $prev['month'], $curr['year'], $curr['month']);
               $depFlows = $this->computeDepositoFlows($prev['year'], $prev['month'], $curr['year'], $curr['month']);

               $y = (float)$curr['funding_total'];
               $features = [
                    'lag_funding_total' => (float)$prev['funding_total'],
                    'tabungan_count' => (float)$curr['tabungan_count'],
                    'deposito_count' => (float)$curr['deposito_count'],
                    'tabungan_new_total' => (float)$tabFlows['new_total'],
                    'tabungan_withdrawn_total' => (float)$tabFlows['withdrawn_total'],
                    'deposito_net_inflow_total' => (float)$depFlows['net_inflow_total'],
                    'deposito_rate_avg' => (float)$curr['deposito_rate_avg'],
                    'deposito_rate_max' => (float)$curr['deposito_rate_max'],
                    'deposito_rate_min' => (float)$curr['deposito_rate_min'],
                    'macro_bi_rate' => (float)($macro['bi_rate'] ?? 0.0),
                    'macro_inflation_yoy' => (float)($macro['inflation_yoy'] ?? 0.0),
               ];

               $rows[] = [
                    'year' => $curr['year'],
                    'month' => $curr['month'],
                    'y' => $y,
                    'features' => $features,
               ];
          }

          return $rows;
     }

     /**
      * Project next-period features using average growth over history (PDF-style).
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
               // Growth is computed across consecutive months in training rows.
               // If prev is ~0, treat growth as 0 to avoid explosions.
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

               // PDF: lag(t+1) should equal the latest observed target (y at cutoff).
               if ($k === 'lag_funding_total') {
                    $next[$k] = (float)($last['y'] ?? $base);
                    continue;
               }

               // Keep rate features stable by default.
               if (str_starts_with($k, 'deposito_rate_') || str_starts_with($k, 'macro_')) {
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

          $cursor = Carbon::create((int)$min['year'], (int)$min['month'], 1)->startOfMonth();
          $end = Carbon::create((int)$max['year'], (int)$max['month'], 1)->startOfMonth();

          $lastBi = null;
          $lastInfl = null;

          // Seed with last known values <= minKey, so later months don't become 0 when series is stale.
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

          while ($cursor->lte($end)) {
               $key = ((int)$cursor->year) * 100 + (int)$cursor->month;
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

               $cursor->addMonth();
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

     private function collectFundingPeriodsUpTo(int $endYear, int $endMonth): array
     {
          $endPeriod = $endYear * 100 + $endMonth;

          $tabAgg = DB::table('tabungans')
               ->select('period_year', 'period_month', DB::raw('SUM(sahirrp) as total'), DB::raw('COUNT(*) as cnt'))
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $depAgg = DB::table('depositos')
               ->select(
                    'period_year',
                    'period_month',
                    DB::raw('SUM(nomrp) as total'),
                    DB::raw('COUNT(*) as cnt'),
                    DB::raw('AVG(equivrate) as rate_avg'),
                    DB::raw('MAX(equivrate) as rate_max'),
                    DB::raw('MIN(equivrate) as rate_min'),
               )
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $byPeriod = [];
          foreach ($tabAgg as $row) {
               $key = ((int)$row->period_year) * 100 + (int)$row->period_month;
               $byPeriod[$key] = $byPeriod[$key] ?? [
                    'year' => (int)$row->period_year,
                    'month' => (int)$row->period_month,
                    'tabungan_total' => 0.0,
                    'tabungan_count' => 0.0,
                    'deposito_total' => 0.0,
                    'deposito_count' => 0.0,
                    'deposito_rate_avg' => 0.0,
                    'deposito_rate_max' => 0.0,
                    'deposito_rate_min' => 0.0,
               ];
               $byPeriod[$key]['tabungan_total'] = (float)$row->total;
               $byPeriod[$key]['tabungan_count'] = (float)$row->cnt;
          }
          foreach ($depAgg as $row) {
               $key = ((int)$row->period_year) * 100 + (int)$row->period_month;
               $byPeriod[$key] = $byPeriod[$key] ?? [
                    'year' => (int)$row->period_year,
                    'month' => (int)$row->period_month,
                    'tabungan_total' => 0.0,
                    'tabungan_count' => 0.0,
                    'deposito_total' => 0.0,
                    'deposito_count' => 0.0,
                    'deposito_rate_avg' => 0.0,
                    'deposito_rate_max' => 0.0,
                    'deposito_rate_min' => 0.0,
               ];
               $byPeriod[$key]['deposito_total'] = (float)$row->total;
               $byPeriod[$key]['deposito_count'] = (float)$row->cnt;
               $byPeriod[$key]['deposito_rate_avg'] = $row->rate_avg !== null ? (float)$row->rate_avg : 0.0;
               $byPeriod[$key]['deposito_rate_max'] = $row->rate_max !== null ? (float)$row->rate_max : 0.0;
               $byPeriod[$key]['deposito_rate_min'] = $row->rate_min !== null ? (float)$row->rate_min : 0.0;
          }

          ksort($byPeriod);

          $out = [];
          foreach ($byPeriod as $key => $row) {
               $row['funding_total'] = (float)$row['tabungan_total'] + (float)$row['deposito_total'];
               $out[] = $row;
          }

          return $out;
     }

     private function computeTabunganFlows(int $prevYear, int $prevMonth, int $currYear, int $currMonth): array
     {
          $prevMonthStr = str_pad((string)$prevMonth, 2, '0', STR_PAD_LEFT);
          $currMonthStr = str_pad((string)$currMonth, 2, '0', STR_PAD_LEFT);

          $pairsSql = "SELECT SUM(GREATEST(prev_bal - curr_bal, 0)) AS withdrawn_total,
                           SUM(GREATEST(curr_bal - prev_bal, 0)) AS new_total
                    FROM (
                        SELECT n.notab,
                               COALESCE(prev.sahirrp, 0) AS prev_bal,
                               COALESCE(curr.sahirrp, 0) AS curr_bal
                        FROM (
                            SELECT notab FROM tabungans WHERE period_month = ? AND period_year = ?
                            UNION
                            SELECT notab FROM tabungans WHERE period_month = ? AND period_year = ?
                        ) n
                        LEFT JOIN tabungans prev ON prev.notab = n.notab AND prev.period_month = ? AND prev.period_year = ?
                        LEFT JOIN tabungans curr ON curr.notab = n.notab AND curr.period_month = ? AND curr.period_year = ?
                    ) pairs";

          $bindings = [
               $prevMonthStr,
               (string)$prevYear,
               $currMonthStr,
               (string)$currYear,
               $prevMonthStr,
               (string)$prevYear,
               $currMonthStr,
               (string)$currYear,
          ];

          try {
               $r = DB::selectOne($pairsSql, $bindings);
               return [
                    'withdrawn_total' => isset($r->withdrawn_total) ? (float)$r->withdrawn_total : 0.0,
                    'new_total' => isset($r->new_total) ? (float)$r->new_total : 0.0,
               ];
          } catch (\Throwable $e) {
               return [
                    'withdrawn_total' => 0.0,
                    'new_total' => 0.0,
               ];
          }
     }

     private function computeDepositoFlows(int $prevYear, int $prevMonth, int $currYear, int $currMonth): array
     {
          $prevMonthStr = str_pad((string)$prevMonth, 2, '0', STR_PAD_LEFT);
          $currMonthStr = str_pad((string)$currMonth, 2, '0', STR_PAD_LEFT);

          // cair: existed in prev but not in curr
          $cair = DB::table('depositos as prev')
               ->leftJoin('depositos as curr', function ($join) use ($currMonthStr, $currYear) {
                    $join->on('prev.nobilyet', '=', 'curr.nobilyet')
                         ->where('curr.period_month', $currMonthStr)
                         ->where('curr.period_year', (string)$currYear);
               })
               ->where('prev.period_month', $prevMonthStr)
               ->where('prev.period_year', (string)$prevYear)
               ->whereNull('curr.nobilyet')
               ->select(DB::raw('SUM(prev.nomrp) as total'))
               ->first();

          // buka: existed in curr but not in prev
          $buka = DB::table('depositos as curr')
               ->leftJoin('depositos as prev', function ($join) use ($prevMonthStr, $prevYear) {
                    $join->on('curr.nobilyet', '=', 'prev.nobilyet')
                         ->where('prev.period_month', $prevMonthStr)
                         ->where('prev.period_year', (string)$prevYear);
               })
               ->where('curr.period_month', $currMonthStr)
               ->where('curr.period_year', (string)$currYear)
               ->whereNull('prev.nobilyet')
               ->select(DB::raw('SUM(curr.nomrp) as total'))
               ->first();

          $cairTotal = $cair && $cair->total !== null ? (float)$cair->total : 0.0;
          $bukaTotal = $buka && $buka->total !== null ? (float)$buka->total : 0.0;

          return [
               'net_inflow_total' => $bukaTotal - $cairTotal,
          ];
     }
}
