<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MacroIndicatorService
{
     /**
      * Sync monthly BI rate and inflation (YoY) from the internet.
      *
      * Official-first strategy:
      * - Inflation YoY: BPS WebAPI (SDDS CPI) if BPS API key is configured.
      * - BI rate: FRED CSV (fallback) until an official BI provider is wired.
      *
      * If BPS API key is not configured, falls back to FRED for both.
      */
     public function syncFromInternet(Carbon $startMonth, Carbon $endMonth): array
     {
          $start = $startMonth->copy()->startOfMonth();
          $end = $endMonth->copy()->startOfMonth();
          if ($start->gt($end)) {
               [$start, $end] = [$end, $start];
          }

          $bpsApiKey = (string) config('macro.bps.api_key');
          $useBps = trim($bpsApiKey) !== '';

          $biSeries = (string) config('macro.fred.bi_rate_series_id');
          $inflSeries = (string) config('macro.fred.inflation_yoy_series_id');

          $biRates = $this->fetchFredMonthlySeries($biSeries);

          // Inflation: try BPS official source when configured, else FRED.
          $inflationYoyByPeriod = [];
          $inflationProvider = null;
          $inflationProviderDetails = [];

          if ($useBps) {
               try {
                    $inflationYoyByPeriod = $this->computeInflationYoyFromBpsCpi($start, $end);
                    if (!$inflationYoyByPeriod) {
                         throw new \RuntimeException('BPS CPI fetched but produced no inflation_yoy values for requested range.');
                    }
                    $inflationProvider = 'BPS';
                    $inflationProviderDetails = [
                         'bps' => [
                              'cpi_var_id' => (int) config('macro.bps.cpi_var_id'),
                              'vervar_preferred_label' => (string) config('macro.bps.vervar_preferred_label', 'INDONESIA'),
                         ],
                    ];
               } catch (\Throwable $e) {
                    // Fallback to FRED inflation if BPS is misconfigured/unavailable.
                    $inflationYoyByPeriod = $this->fetchFredMonthlySeries($inflSeries);
                    $inflationProvider = 'FRED';
                    $inflationProviderDetails = [
                         'fred' => [
                              'inflation_yoy_series_id' => $inflSeries,
                              'fallback_reason' => 'bps_failed',
                         ],
                         'bps_error' => $e->getMessage(),
                    ];
               }
          } else {
               $inflationYoyByPeriod = $this->fetchFredMonthlySeries($inflSeries);
               $inflationProvider = 'FRED';
               $inflationProviderDetails = [
                    'fred' => [
                         'inflation_yoy_series_id' => $inflSeries,
                    ],
               ];
          }

          $existing = $this->loadExistingMacroRowsByPeriod($start, $end);

          $updates = 0;
          $skipped = 0;

          $cursor = $start->copy();
          while ($cursor->lte($end)) {
               $year = (int) $cursor->year;
               $month = (int) $cursor->month;
               $key = $year * 100 + $month;

               $existingRow = $existing[$key] ?? null;

               $biNew = array_key_exists($key, $biRates) ? $biRates[$key] : null;
               $inflNew = array_key_exists($key, $inflationYoyByPeriod) ? $inflationYoyByPeriod[$key] : null;

               $biFinal = $biNew;
               $inflFinal = $inflNew;
               if ($existingRow) {
                    if ($biFinal === null && $existingRow['bi_rate'] !== null) {
                         $biFinal = (float) $existingRow['bi_rate'];
                    }
                    if ($inflFinal === null && $existingRow['inflation_yoy'] !== null) {
                         $inflFinal = (float) $existingRow['inflation_yoy'];
                    }
               }

               if ($biFinal === null && $inflFinal === null) {
                    $skipped++;
                    $cursor->addMonth();
                    continue;
               }

               $providers = [];
               if ($biFinal !== null) {
                    $providers['bi_rate'] = $biNew !== null ? 'FRED' : ($existingRow['providers']['bi_rate'] ?? ($existingRow['source'] ?? 'UNKNOWN'));
               }
               if ($inflFinal !== null) {
                    $providers['inflation_yoy'] = $inflNew !== null ? ($inflationProvider ?: 'UNKNOWN') : ($existingRow['providers']['inflation_yoy'] ?? ($existingRow['source'] ?? 'UNKNOWN'));
               }

               $uniqueProviders = array_values(array_unique(array_values($providers)));
               $source = $uniqueProviders ? implode('+', $uniqueProviders) : ($existingRow['source'] ?? 'UNKNOWN');

               $sourceDetails = $existingRow['source_details'] ?? [];
               $sourceDetails = $this->mergeSourceDetails($sourceDetails, [
                    'providers' => $providers,
                    'fred' => [
                         'bi_rate_series_id' => $biSeries,
                    ],
               ]);
               $sourceDetails = $this->mergeSourceDetails($sourceDetails, $inflationProviderDetails);

               $this->upsertMacroRow(
                    $year,
                    $month,
                    $biFinal,
                    $inflFinal,
                    $source,
                    $sourceDetails,
                    $existingRow ? (int) $existingRow['id'] : null
               );

               $updates++;
               $cursor->addMonth();
          }

          return [
               'ok' => true,
               'updated' => $updates,
               'skipped' => $skipped,
               'source' => $useBps ? 'BPS+FRED' : 'FRED',
               'bi_rate_series_id' => $biSeries,
               'inflation_yoy_series_id' => $inflSeries,
               'inflation_provider' => $inflationProvider,
          ];
     }

     /**
      * @return array<int,array{id:int,bi_rate:float|null,inflation_yoy:float|null,source:string,providers:array<string,string>,source_details:array<string,mixed>}> Map periodKey (YYYYMM int) => row data
      */
     private function loadExistingMacroRowsByPeriod(Carbon $start, Carbon $end): array
     {
          $minKey = ((int) $start->year) * 100 + (int) $start->month;
          $maxKey = ((int) $end->year) * 100 + (int) $end->month;

          $rows = DB::table('macro_indicators')
               ->select('id', 'period_year', 'period_month', 'bi_rate', 'inflation_yoy', 'source', 'source_details')
               ->whereRaw('(period_year * 100 + period_month) >= ?', [$minKey])
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$maxKey])
               ->get();

          $out = [];
          foreach ($rows as $r) {
               $key = ((int) $r->period_year) * 100 + (int) $r->period_month;
               $details = [];
               if ($r->source_details !== null) {
                    try {
                         $details = json_decode((string) $r->source_details, true, 512, JSON_THROW_ON_ERROR);
                    } catch (\Throwable $e) {
                         $details = [];
                    }
               }

               $out[$key] = [
                    'id' => (int) $r->id,
                    'bi_rate' => $r->bi_rate !== null ? (float) $r->bi_rate : null,
                    'inflation_yoy' => $r->inflation_yoy !== null ? (float) $r->inflation_yoy : null,
                    'source' => (string) $r->source,
                    'providers' => is_array($details['providers'] ?? null) ? $details['providers'] : [],
                    'source_details' => $details,
               ];
          }

          return $out;
     }

     private function upsertMacroRow(
          int $year,
          int $month,
          ?float $biRate,
          ?float $inflationYoy,
          string $source,
          array $sourceDetails,
          ?int $existingId
     ): void {
          $now = now();

          $payload = [
               'period_year' => $year,
               'period_month' => $month,
               'bi_rate' => $biRate,
               'inflation_yoy' => $inflationYoy,
               'source' => $source,
               'source_details' => json_encode($sourceDetails),
               'fetched_at' => $now,
               'updated_at' => $now,
          ];

          if ($existingId !== null) {
               DB::table('macro_indicators')->where('id', $existingId)->update($payload);
               return;
          }

          $payload['created_at'] = $now;
          DB::table('macro_indicators')->insert($payload);
     }

     private function mergeSourceDetails(array $base, array $add): array
     {
          foreach ($add as $k => $v) {
               if (is_array($v) && is_array($base[$k] ?? null)) {
                    $base[$k] = $this->mergeSourceDetails($base[$k], $v);
               } else {
                    $base[$k] = $v;
               }
          }
          return $base;
     }

     /**
      * Compute inflation YoY (%) using CPI from BPS WebAPI (SDDS CPI variable).
      *
      * @return array<int,float> Map periodKey (YYYYMM int) => inflation_yoy
      */
     private function computeInflationYoyFromBpsCpi(Carbon $start, Carbon $end): array
     {
          $cpiVarId = (int) config('macro.bps.cpi_var_id');
          $vervarPreferredLabel = (string) config('macro.bps.vervar_preferred_label', 'INDONESIA');

          // Need 12 months before start to compute YoY for start..end.
          $needStart = $start->copy()->subMonths(12)->startOfMonth();

          $cpiByPeriod = $this->fetchBpsMonthlySeries($cpiVarId, $needStart, $end, $vervarPreferredLabel);

          $out = [];
          $cursor = $start->copy();
          while ($cursor->lte($end)) {
               $key = ((int) $cursor->year) * 100 + (int) $cursor->month;
               $prev = $cursor->copy()->subMonths(12);
               $prevKey = ((int) $prev->year) * 100 + (int) $prev->month;

               $c = $cpiByPeriod[$key] ?? null;
               $cPrev = $cpiByPeriod[$prevKey] ?? null;

               if ($c === null || $cPrev === null) {
                    $cursor->addMonth();
                    continue;
               }
               if (abs((float) $cPrev) < 1e-9) {
                    $cursor->addMonth();
                    continue;
               }

               $out[$key] = (((float) $c) / ((float) $cPrev) - 1.0) * 100.0;
               $cursor->addMonth();
          }

          return $out;
     }

     /**
      * Fetch a monthly series from BPS WebAPI Dynamic Data (model=data) using var_id.
      *
      * @return array<int,float> Map periodKey (YYYYMM int) => value
      */
     private function fetchBpsMonthlySeries(int $varId, Carbon $start, Carbon $end, string $preferredVervarLabel): array
     {
          $baseUrl = rtrim((string) config('macro.bps.base_url'), '/');
          $apiKey = (string) config('macro.bps.api_key');
          $timeout = (int) config('macro.bps.timeout_seconds', 20);

          if (trim($apiKey) === '') {
               throw new \RuntimeException('BPS API key is not configured. Set BPS_API_KEY.');
          }

          $url = $baseUrl . '/list/model/data/domain/0000/var/' . $varId . '/key/' . urlencode($apiKey);

          $response = Http::timeout($timeout)
               ->acceptJson()
               ->retry(2, 250)
               ->get($url);

          if (!$response->ok()) {
               throw new \RuntimeException("Failed to fetch BPS var {$varId} (HTTP {$response->status()})");
          }

          $payload = $response->json();
          if (!is_array($payload)) {
               throw new \RuntimeException('Unexpected BPS response (not JSON object).');
          }

          $datacontent = $payload['datacontent'] ?? null;
          if (!is_array($datacontent)) {
               throw new \RuntimeException('BPS response missing datacontent.');
          }

          $varVal = $this->pickValFromList($payload['var'] ?? null, $varId);
          $turvarVal = $this->pickTurvarVal($payload['turvar'] ?? null);
          $vervarVal = $this->pickVervarVal($payload['vervar'] ?? null, $preferredVervarLabel);

          $yearIdByYear = $this->yearIdByYear($payload['tahun'] ?? null);
          if (!$yearIdByYear) {
               throw new \RuntimeException('BPS response missing tahun/year mapping.');
          }

          $availableTurthVals = $this->turthVals($payload['turtahun'] ?? null);
          $start = $start->copy()->startOfMonth();
          $end = $end->copy()->startOfMonth();
          if ($start->gt($end)) {
               [$start, $end] = [$end, $start];
          }

          $out = [];
          $cursor = $start->copy();
          while ($cursor->lte($end)) {
               $year = (int) $cursor->year;
               $month = (int) $cursor->month;
               $periodKey = $year * 100 + $month;

               $thId = $yearIdByYear[$year] ?? null;
               if ($thId === null) {
                    $cursor->addMonth();
                    continue;
               }

               $turthId = $this->pickTurthIdForMonth($month, $availableTurthVals);
               $dataKey = sprintf('%d%d%d%d%d', $vervarVal, $varVal, $turvarVal, (int) $thId, (int) $turthId);

               if (array_key_exists($dataKey, $datacontent) && is_numeric($datacontent[$dataKey])) {
                    $out[$periodKey] = (float) $datacontent[$dataKey];
               }

               $cursor->addMonth();
          }

          return $out;
     }

     /**
      * @param mixed $list
      */
     private function pickValFromList($list, int $fallback): int
     {
          if (is_array($list) && isset($list[0]['val']) && is_numeric($list[0]['val'])) {
               return (int) $list[0]['val'];
          }
          return $fallback;
     }

     /**
      * Prefer a "General/Umum"-like turvar when present; otherwise first; otherwise 0.
      *
      * @param mixed $turvarList
      */
     private function pickTurvarVal($turvarList): int
     {
          if (!is_array($turvarList) || !$turvarList) {
               return 0;
          }

          $preferContains = strtoupper((string) config('macro.bps.turvar_prefer_contains', 'UMUM'));
          foreach ($turvarList as $item) {
               $label = strtoupper((string) ($item['label'] ?? ''));
               if ($preferContains !== '' && $label !== '' && str_contains($label, $preferContains) && is_numeric($item['val'] ?? null)) {
                    return (int) $item['val'];
               }
          }

          foreach ($turvarList as $item) {
               if (is_numeric($item['val'] ?? null)) {
                    return (int) $item['val'];
               }
          }

          return 0;
     }

     /**
      * @param mixed $vervarList
      */
     private function pickVervarVal($vervarList, string $preferredLabel): int
     {
          if (!is_array($vervarList) || !$vervarList) {
               return 0;
          }

          $preferredLabel = strtoupper(trim($preferredLabel));
          foreach ($vervarList as $item) {
               $label = strtoupper((string) ($item['label'] ?? ''));
               if ($preferredLabel !== '' && $label === $preferredLabel && is_numeric($item['val'] ?? null)) {
                    return (int) $item['val'];
               }
          }

          // Common aggregate.
          foreach ($vervarList as $item) {
               if ((string) ($item['label'] ?? '') !== '' && strtoupper((string) $item['label']) === 'INDONESIA' && is_numeric($item['val'] ?? null)) {
                    return (int) $item['val'];
               }
          }

          foreach ($vervarList as $item) {
               if (is_numeric($item['val'] ?? null)) {
                    return (int) $item['val'];
               }
          }

          return 0;
     }

     /**
      * @param mixed $tahunList
      * @return array<int,int> map year => th_id
      */
     private function yearIdByYear($tahunList): array
     {
          if (!is_array($tahunList)) {
               return [];
          }
          $out = [];
          foreach ($tahunList as $item) {
               $label = trim((string) ($item['label'] ?? ''));
               if (!is_numeric($label) || !is_numeric($item['val'] ?? null)) {
                    continue;
               }
               $out[(int) $label] = (int) $item['val'];
          }
          return $out;
     }

     /**
      * @param mixed $turthList
      * @return int[]
      */
     private function turthVals($turthList): array
     {
          if (!is_array($turthList)) {
               return [];
          }
          $vals = [];
          foreach ($turthList as $item) {
               if (is_numeric($item['val'] ?? null)) {
                    $vals[] = (int) $item['val'];
               }
          }
          return array_values(array_unique($vals));
     }

     /**
      * Prefer month id present in API response, otherwise assume month number.
      */
     private function pickTurthIdForMonth(int $month, array $availableTurthVals): int
     {
          if (in_array($month, $availableTurthVals, true)) {
               return $month;
          }
          // Some tables might use 1..12 regardless of the returned list.
          return $month;
     }

     /**
      * @return array<int,float> Map periodKey (YYYYMM int) => value
      */
     private function fetchFredMonthlySeries(string $seriesId): array
     {
          $baseUrl = (string) config('macro.fred.base_url');
          $timeout = (int) config('macro.fred.timeout_seconds', 20);

          $response = Http::timeout($timeout)
               ->accept('text/csv')
               ->get($baseUrl, ['id' => $seriesId]);

          if (!$response->ok()) {
               throw new \RuntimeException("Failed to fetch FRED series {$seriesId} (HTTP {$response->status()})");
          }

          $csv = (string) $response->body();
          $lines = preg_split("/\r\n|\n|\r/", trim($csv));
          if (!$lines || count($lines) < 2) {
               return [];
          }

          $header = str_getcsv(array_shift($lines));
          $idxDate = array_search('DATE', $header, true);
          $idxValue = array_search($seriesId, $header, true);

          if ($idxDate === false) {
               $idxDate = 0;
          }
          if ($idxValue === false) {
               // FRED sometimes uses "VALUE" as second header in some exports.
               $idxValue = array_key_exists(1, $header) ? 1 : 0;
          }

          $out = [];
          foreach ($lines as $line) {
               $line = trim((string) $line);
               if ($line === '') {
                    continue;
               }

               $cols = str_getcsv($line);
               if (!isset($cols[$idxDate]) || !isset($cols[$idxValue])) {
                    continue;
               }

               $dateStr = trim((string) $cols[$idxDate]);
               $valStr = trim((string) $cols[$idxValue]);

               if ($valStr === '.' || $valStr === '') {
                    continue;
               }

               if (!is_numeric($valStr)) {
                    continue;
               }

               try {
                    $dt = Carbon::parse($dateStr)->startOfMonth();
               } catch (\Throwable $e) {
                    continue;
               }

               $key = ((int) $dt->year) * 100 + (int) $dt->month;
               $out[$key] = (float) $valStr;
          }

          return $out;
     }
}
