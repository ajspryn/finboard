<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use App\Services\FundingMultivariateFeatureBuilder;
use App\Services\LendingMultivariateFeatureBuilder;
use App\Services\NpfMultivariateFeatureBuilder;
use App\Services\MacroIndicatorService;

class BacktestSvrPredictions extends Command
{
     protected $signature = 'predictions:backtest-svr
        {--months=4 : Number of target months to backtest (default 4)}
        {--max-months= : Max months of history to use (default from config)}';

     protected $description = 'Backtest SVR predictions on the last N months and report accuracy (MAPE/Accuracy% and R²)';

     public function handle(MacroIndicatorService $macroService): int
     {
          $months = (int)$this->option('months');
          if ($months < 1) {
               $this->error('--months harus >= 1');
               return Command::FAILURE;
          }

          $minHistoryMonths = (int)config('predictions.svr.min_history_months', 6);
          if ($minHistoryMonths < 6) {
               $minHistoryMonths = 6;
          }

          $maxMonths = (int)($this->option('max-months') ?: config('predictions.svr.max_months', 60));
          if ($maxMonths < $minHistoryMonths) {
               $maxMonths = $minHistoryMonths;
          }

          $trainWindowMonths = (int)config('predictions.svr.train_window_months', 6);
          if ($trainWindowMonths <= 0) {
               $trainWindowMonths = $maxMonths;
          }
          if ($trainWindowMonths < $minHistoryMonths) {
               $trainWindowMonths = $minHistoryMonths;
          }
          $trainWindowMonths = min($trainWindowMonths, $maxMonths);

          $python = (string)config('predictions.python_bin');
          $script = base_path('ml/svr_forecast.py');
          $mvScript = base_path('ml/svr_forecast_multivariate.py');
          $npfWindowScript = base_path('ml/svr_npf_sliding_window.py');

          if (!is_file($python)) {
               $this->error("Python binary tidak ditemukan: {$python}");
               $this->warn('Pastikan virtualenv sudah dibuat (ml/.venv) atau set PREDICTIONS_PYTHON_BIN di .env');
               return Command::FAILURE;
          }

          if (!is_file($script)) {
               $this->error("Script tidak ditemukan: {$script}");
               return Command::FAILURE;
          }

          if (!is_file($mvScript)) {
               $this->error("Script tidak ditemukan: {$mvScript}");
               return Command::FAILURE;
          }

          if (!is_file($npfWindowScript)) {
               $this->error("Script tidak ditemukan: {$npfWindowScript}");
               return Command::FAILURE;
          }

          [$latestYear, $latestMonth] = $this->resolveLatestPeriod();
          if (!$latestYear || !$latestMonth) {
               $this->error('Tidak menemukan data period_year/period_month di pembiayaans/tabungans/depositos.');
               return Command::FAILURE;
          }

          $latest = Carbon::create($latestYear, $latestMonth, 1);

          $lags = (array)config('predictions.svr.lags', [1, 2, 3, 6, 12]);
          $testFraction = (float)config('predictions.svr.test_fraction', 0.2);
          $targetR2 = (float)config('predictions.svr.target_r2', 0.9);
          $lagSearch = (bool)config('predictions.svr.lag_search', true);
          $lagSearchMaxSets = (int)config('predictions.svr.lag_search_max_sets', 6);
          $npfMaxMonths = (int)config('predictions.svr.npf_max_months', 6);
          if ($npfMaxMonths < $minHistoryMonths) {
               $npfMaxMonths = $minHistoryMonths;
          }

          $metrics = [
               'funding_total' => [
                    'label' => 'Funding (DPK) Total',
                    'formatter' => fn(float $v) => number_format($v, 2, '.', ','),
               ],
               'lending_outstanding' => [
                    'label' => 'Lending Outstanding',
                    'formatter' => fn(float $v) => number_format($v, 2, '.', ','),
               ],
               'npf_ratio' => [
                    'label' => 'NPF Ratio',
                    'formatter' => fn(float $v) => number_format($v, 4) . '%',
               ],
          ];

          $this->info(sprintf('Backtest %d bulan terakhir (latest=%04d-%02d)', $months, $latestYear, $latestMonth));
          $this->line('Definisi: Accuracy% = max(0, 100 - MAPE%), dan R² dihitung dari 4 titik target.');

          // Best-effort macro sync for the range involved in the backtest.
          if (Schema::hasTable('macro_indicators')) {
               try {
                    $macroStart = (clone $latest)->startOfMonth()->subMonths($trainWindowMonths + $months + 3);
                    $macroEnd = (clone $latest)->startOfMonth();
                    $macroService->syncFromInternet($macroStart, $macroEnd);
               } catch (\Throwable $e) {
                    $this->warn('Macro sync gagal (akan lanjut dengan nilai 0 jika belum ada data): ' . $e->getMessage());
               }
          }

          $any = false;

          foreach ($metrics as $metricKey => $meta) {
               $targets = [];
               for ($i = 0; $i < $months; $i++) {
                    $targets[] = (clone $latest)->subMonths($i);
               }
               $targets = array_reverse($targets); // oldest -> newest

               $rows = [];
               $yTrue = [];
               $yPred = [];
               $apeList = [];

               foreach ($targets as $target) {
                    $targetYear = (int)$target->year;
                    $targetMonth = (int)$target->month;

                    $cutoff = (clone $target)->subMonth();
                    $cutoffYear = (int)$cutoff->year;
                    $cutoffMonth = (int)$cutoff->month;

                    $actual = $this->getActualValue($metricKey, $targetYear, $targetMonth);

                    if ($actual === null) {
                         $rows[] = [
                              'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                              'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                              'actual' => null,
                              'pred' => null,
                              'ape' => null,
                              'note' => 'aktual tidak tersedia',
                         ];
                         continue;
                    }

                    if ($metricKey === 'npf_ratio') {
                         // PDF method: sliding window (3 months) over [bulan_num, npf, outstanding, tunggakan_pokok, rate_eff].
                         $historyMonths = max($minHistoryMonths, min($maxMonths, $trainWindowMonths, $npfMaxMonths));
                         $windowSize = 3;

                         $builder = new NpfMultivariateFeatureBuilder();
                         $monthlyRows = $builder->buildSlidingWindowRowsForNpfRatio($cutoffYear, $cutoffMonth, $historyMonths);
                         if (count($monthlyRows) < ($windowSize + 2)) {
                              $rows[] = [
                                   'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                   'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                   'actual' => $actual,
                                   'pred' => null,
                                   'ape' => null,
                                   'note' => 'histori sliding-window tidak cukup (' . count($monthlyRows) . '; butuh >=' . ($windowSize + 2) . ')',
                              ];
                              continue;
                         }

                         $payload = [
                              'metric' => 'npf_ratio_sliding_window',
                              'rows' => $monthlyRows,
                              'window_size' => $windowSize,
                              'test_fraction' => $testFraction,
                              'non_negative' => true,
                              'svr_params' => [
                                   'kernel' => 'rbf',
                                   'C' => 100,
                                   'gamma' => 0.1,
                                   'epsilon' => 0.01,
                              ],
                         ];

                         $result = $this->runPythonForecast($python, $npfWindowScript, $payload);
                         if (!$result['ok']) {
                              $rows[] = [
                                   'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                   'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                   'actual' => $actual,
                                   'pred' => null,
                                   'ape' => null,
                                   'note' => 'prediksi gagal (sliding-window): ' . ($result['error'] ?? 'unknown error'),
                              ];
                              continue;
                         }

                         $pred = (float)$result['prediction'];
                         $pred = max(0.0, min(100.0, $pred));
                    } else {
                         $historyMonths = max($minHistoryMonths, min($maxMonths, $trainWindowMonths));

                         if ($metricKey === 'funding_total') {
                              $series = $this->buildFundingSeries($cutoffYear, $cutoffMonth, $historyMonths);

                              if (count($series) < $minHistoryMonths) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => "histori < {$minHistoryMonths} bulan (" . count($series) . ')',
                                   ];
                                   continue;
                              }

                              $builder = new FundingMultivariateFeatureBuilder();
                              $trainingRows = $builder->buildTrainingRows($cutoffYear, $cutoffMonth, $historyMonths);
                              if (count($trainingRows) < 3) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'training rows multivariat tidak cukup (' . count($trainingRows) . ')',
                                   ];
                                   continue;
                              }

                              $featureOrder = [
                                   'lag_funding_total',
                                   'tabungan_count',
                                   'deposito_count',
                                   'tabungan_new_total',
                                   'tabungan_withdrawn_total',
                                   'deposito_net_inflow_total',
                                   'deposito_rate_avg',
                                   'deposito_rate_max',
                                   'deposito_rate_min',
                                   'macro_bi_rate',
                                   'macro_inflation_yoy',
                              ];

                              [$X, $y] = $builder->toMatrices($trainingRows, $featureOrder);
                              $XNextAssoc = $builder->projectNextFeatures($trainingRows);
                              if (!$XNextAssoc) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'gagal membuat fitur proyeksi (X_next)',
                                   ];
                                   continue;
                              }

                              $macroTarget = $this->resolveMacroForPeriod($targetYear, $targetMonth);
                              $XNextAssoc['macro_bi_rate'] = (float)($macroTarget['bi_rate'] ?? 0.0);
                              $XNextAssoc['macro_inflation_yoy'] = (float)($macroTarget['inflation_yoy'] ?? 0.0);

                              $XNext = [];
                              foreach ($featureOrder as $k) {
                                   $XNext[] = (float)($XNextAssoc[$k] ?? 0);
                              }

                              $payload = [
                                   'metric' => 'funding_total_multivariate',
                                   'X' => $X,
                                   'y' => $y,
                                   'X_next' => $XNext,
                                   'test_fraction' => $testFraction,
                                   'non_negative' => true,
                                   'svr_params' => [
                                        'kernel' => 'rbf',
                                        'C' => 5,
                                        'gamma' => 'scale',
                                        'epsilon' => 0.25,
                                   ],
                              ];

                              $result = $this->runPythonForecast($python, $mvScript, $payload);
                              if (!$result['ok']) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'prediksi gagal (multivariat): ' . ($result['error'] ?? 'unknown error'),
                                   ];
                                   continue;
                              }

                              $pred = (float)$result['prediction'];
                         } elseif ($metricKey === 'lending_outstanding') {
                              $series = $this->buildLendingSeries($cutoffYear, $cutoffMonth, $historyMonths);

                              if (count($series) < $minHistoryMonths) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => "histori < {$minHistoryMonths} bulan (" . count($series) . ')',
                                   ];
                                   continue;
                              }

                              $builder = new LendingMultivariateFeatureBuilder();
                              $trainingRows = $builder->buildTrainingRows($cutoffYear, $cutoffMonth, $historyMonths);
                              if (count($trainingRows) < 3) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'training rows multivariat tidak cukup (' . count($trainingRows) . ')',
                                   ];
                                   continue;
                              }

                              $featureOrder = [
                                   'lag_outstanding',
                                   'dpk_total',
                                   'npf_ratio',
                                   'macro_bi_rate',
                                   'macro_inflation_yoy',
                              ];

                              [$X, $y] = $builder->toMatrices($trainingRows, $featureOrder);

                              $fundingRes = $this->forecastFundingForTarget($python, $mvScript, $cutoffYear, $cutoffMonth, $targetYear, $targetMonth, $historyMonths, $testFraction);
                              if (!$fundingRes['ok']) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'gagal prediksi DPK (dependency): ' . ($fundingRes['error'] ?? 'unknown error'),
                                   ];
                                   continue;
                              }

                              $npfHistoryMonths = min($historyMonths, $npfMaxMonths);
                              $npfRes = $this->forecastNpfForTarget($python, $npfWindowScript, $cutoffYear, $cutoffMonth, $npfHistoryMonths, $testFraction);
                              if (!$npfRes['ok']) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'gagal prediksi NPF (dependency): ' . ($npfRes['error'] ?? 'unknown error'),
                                   ];
                                   continue;
                              }

                              $macroTarget = $this->resolveMacroForPeriod($targetYear, $targetMonth);
                              $lastRow = $trainingRows[count($trainingRows) - 1] ?? null;
                              $lagOutstanding = $lastRow ? (float)($lastRow['y'] ?? 0.0) : 0.0;

                              $XNext = [
                                   $lagOutstanding,
                                   (float)$fundingRes['prediction'],
                                   (float)$npfRes['prediction'],
                                   (float)($macroTarget['bi_rate'] ?? 0.0),
                                   (float)($macroTarget['inflation_yoy'] ?? 0.0),
                              ];

                              $payload = [
                                   'metric' => 'lending_outstanding_multivariate',
                                   'X' => $X,
                                   'y' => $y,
                                   'X_next' => $XNext,
                                   'test_fraction' => $testFraction,
                                   'non_negative' => true,
                                   'svr_params' => [
                                        'kernel' => 'rbf',
                                        'C' => 100,
                                        'gamma' => 'scale',
                                        'epsilon' => 0.01,
                                   ],
                              ];

                              $result = $this->runPythonForecast($python, $mvScript, $payload);
                              if (!$result['ok']) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'prediksi gagal (multivariat): ' . ($result['error'] ?? 'unknown error'),
                                   ];
                                   continue;
                              }

                              $pred = (float)$result['prediction'];
                         } else {
                              $series = $this->buildSeries($metricKey, $cutoffYear, $cutoffMonth, $historyMonths);

                              if (count($series) < $minHistoryMonths) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => "histori < {$minHistoryMonths} bulan (" . count($series) . ')',
                                   ];
                                   continue;
                              }

                              $effectiveLags = $this->resolveEffectiveLags($lags, count($series));
                              if (!$effectiveLags) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'histori tidak cukup untuk SVR (sesuaikan lags / tambah bulan data)',
                                   ];
                                   continue;
                              }

                              $payload = [
                                   'metric' => $metricKey,
                                   'series' => $series,
                                   'lags' => array_values(array_map('intval', $effectiveLags)),
                                   'test_fraction' => $testFraction,
                                   'lag_search' => $lagSearch,
                                   'lag_search_max_sets' => $lagSearchMaxSets,
                                   'non_negative' => true,
                              ];

                              $result = $this->runPythonForecast($python, $script, $payload);
                              if (!$result['ok']) {
                                   $rows[] = [
                                        'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                        'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                                        'actual' => $actual,
                                        'pred' => null,
                                        'ape' => null,
                                        'note' => 'prediksi gagal: ' . ($result['error'] ?? 'unknown error'),
                                   ];
                                   continue;
                              }

                              $pred = (float)$result['prediction'];
                         }
                    }

                    $ape = $this->ape($actual, $pred);

                    $rows[] = [
                         'period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                         'cutoff' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                         'actual' => $actual,
                         'pred' => $pred,
                         'ape' => $ape,
                         'note' => '',
                    ];

                    $yTrue[] = $actual;
                    $yPred[] = $pred;
                    if ($ape !== null) {
                         $apeList[] = $ape;
                    }
               }

               if (count($yTrue) < 2) {
                    $this->warn("{$metricKey}: data backtest tidak cukup (butuh >=2 titik valid)");
                    continue;
               }

               $any = true;

               $mape = count($apeList) ? (array_sum($apeList) / count($apeList)) : null;
               $accuracy = $mape !== null ? max(0.0, 100.0 - $mape) : null;
               $r2 = $this->r2($yTrue, $yPred);

               $this->newLine();
               $this->info($meta['label'] . " ({$metricKey})");
               $this->line(str_repeat('-', 72));
               foreach ($rows as $r) {
                    $actualText = $r['actual'] === null ? '-' : ($meta['formatter'])((float)$r['actual']);
                    $predText = $r['pred'] === null ? '-' : ($meta['formatter'])((float)$r['pred']);
                    $apeText = $r['ape'] === null ? '-' : number_format((float)$r['ape'], 2) . '%';
                    $note = $r['note'] ? (' | ' . $r['note']) : '';

                    $this->line(sprintf(
                         '%s (cutoff %s) | actual=%s | pred=%s | APE=%s%s',
                         $r['period'],
                         $r['cutoff'],
                         $actualText,
                         $predText,
                         $apeText,
                         $note
                    ));
               }

               $this->line(str_repeat('-', 72));
               $this->line(sprintf(
                    'MAPE=%s | Accuracy%%=%s | R²=%s | Target R²>=%.2f',
                    $mape === null ? '-' : number_format($mape, 2) . '%',
                    $accuracy === null ? '-' : number_format($accuracy, 2) . '%',
                    $r2 === null ? '-' : number_format($r2, 4),
                    $targetR2
               ));

               if ($accuracy !== null) {
                    $this->line('Status Accuracy% (>90): ' . ($accuracy >= 90.0 ? 'PASS' : 'FAIL'));
               }
               if ($r2 !== null) {
                    $this->line('Status R² (>=0.90): ' . ($r2 >= 0.90 ? 'PASS' : 'FAIL'));
               }
          }

          if (!$any) {
               $this->error('Backtest tidak menghasilkan metrik yang bisa dihitung (cek ketersediaan data).');
               return Command::FAILURE;
          }

          return Command::SUCCESS;
     }

     private function resolveLatestPeriod(): array
     {
          $tables = ['pembiayaans', 'tabungans', 'depositos'];
          foreach ($tables as $table) {
               $latest = DB::table($table)
                    ->select('period_year', 'period_month')
                    ->whereNotNull('period_year')
                    ->whereNotNull('period_month')
                    ->orderByRaw('(period_year * 100 + period_month) DESC')
                    ->first();

               if ($latest) {
                    return [(int)$latest->period_year, (int)$latest->period_month];
               }
          }

          return [null, null];
     }

     private function buildSeries(string $metricKey, int $endYear, int $endMonth, int $maxMonths): array
     {
          return match ($metricKey) {
               'funding_total' => $this->buildFundingSeries($endYear, $endMonth, $maxMonths),
               'lending_outstanding' => $this->buildLendingSeries($endYear, $endMonth, $maxMonths),
               'npf_ratio' => $this->buildNpfSeries($endYear, $endMonth, $maxMonths),
               default => [],
          };
     }

     private function getActualValue(string $metricKey, int $year, int $month): ?float
     {
          return match ($metricKey) {
               'funding_total' => $this->getActualFunding($year, $month),
               'lending_outstanding' => $this->getActualLending($year, $month),
               'npf_ratio' => $this->getActualNpf($year, $month),
               default => null,
          };
     }

     private function getActualFunding(int $year, int $month): ?float
     {
          $tab = DB::table('tabungans')
               ->where('period_year', $year)
               ->where('period_month', $month)
               ->sum('sahirrp');
          $dep = DB::table('depositos')
               ->where('period_year', $year)
               ->where('period_month', $month)
               ->sum('nomrp');

          if ($tab === 0.0 && $dep === 0.0) {
               $exists = DB::table('tabungans')
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->exists()
                    || DB::table('depositos')
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->exists();

               if (!$exists) {
                    return null;
               }
          }

          return (float)$tab + (float)$dep;
     }

     private function getActualLending(int $year, int $month): ?float
     {
          $sum = DB::table('pembiayaans')
               ->where('period_year', $year)
               ->where('period_month', $month)
               ->sum('osmdlc');

          if ((float)$sum === 0.0) {
               $exists = DB::table('pembiayaans')
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->exists();

               if (!$exists) {
                    return null;
               }
          }

          return (float)$sum;
     }

     private function getActualNpf(int $year, int $month): ?float
     {
          $total = DB::table('pembiayaans')
               ->where('period_year', $year)
               ->where('period_month', $month)
               ->sum('osmdlc');

          if ((float)$total === 0.0) {
               $exists = DB::table('pembiayaans')
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->exists();

               if (!$exists) {
                    return null;
               }
          }

          $npf = DB::table('pembiayaans')
               ->where('period_year', $year)
               ->where('period_month', $month)
               ->whereIn('colbaru', ['3', '4', '5'])
               ->sum('osmdlc');

          $ratio = $total > 0 ? ((float)$npf / (float)$total) * 100.0 : 0.0;
          return (float)$ratio;
     }

     private function buildFundingSeries(int $endYear, int $endMonth, int $maxMonths): array
     {
          $endPeriod = $endYear * 100 + $endMonth;

          $tabungan = DB::table('tabungans')
               ->select('period_year', 'period_month', DB::raw('SUM(sahirrp) as total'))
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $deposito = DB::table('depositos')
               ->select('period_year', 'period_month', DB::raw('SUM(nomrp) as total'))
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $byPeriod = [];
          foreach ($tabungan as $row) {
               $key = ((int)$row->period_year) * 100 + (int)$row->period_month;
               $byPeriod[$key] = $byPeriod[$key] ?? ['year' => (int)$row->period_year, 'month' => (int)$row->period_month, 'tab' => 0.0, 'dep' => 0.0];
               $byPeriod[$key]['tab'] = (float)$row->total;
          }
          foreach ($deposito as $row) {
               $key = ((int)$row->period_year) * 100 + (int)$row->period_month;
               $byPeriod[$key] = $byPeriod[$key] ?? ['year' => (int)$row->period_year, 'month' => (int)$row->period_month, 'tab' => 0.0, 'dep' => 0.0];
               $byPeriod[$key]['dep'] = (float)$row->total;
          }

          ksort($byPeriod);
          $series = [];
          foreach ($byPeriod as $row) {
               $series[] = [
                    'year' => $row['year'],
                    'month' => $row['month'],
                    'value' => (float)$row['tab'] + (float)$row['dep'],
               ];
          }

          if (count($series) > $maxMonths) {
               $series = array_slice($series, -$maxMonths);
          }

          return $series;
     }

     private function buildLendingSeries(int $endYear, int $endMonth, int $maxMonths): array
     {
          $endPeriod = $endYear * 100 + $endMonth;

          $rows = DB::table('pembiayaans')
               ->select('period_year', 'period_month', DB::raw('SUM(osmdlc) as total'))
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $series = [];
          foreach ($rows as $row) {
               $series[] = [
                    'year' => (int)$row->period_year,
                    'month' => (int)$row->period_month,
                    'value' => (float)$row->total,
               ];
          }

          if (count($series) > $maxMonths) {
               $series = array_slice($series, -$maxMonths);
          }

          return $series;
     }

     private function buildNpfSeries(int $endYear, int $endMonth, int $maxMonths): array
     {
          $endPeriod = $endYear * 100 + $endMonth;

          $rows = DB::table('pembiayaans')
               ->select(
                    'period_year',
                    'period_month',
                    DB::raw('SUM(osmdlc) as total_os'),
                    DB::raw("SUM(CASE WHEN colbaru IN ('3','4','5') THEN osmdlc ELSE 0 END) as npf_os")
               )
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $series = [];
          foreach ($rows as $row) {
               $total = (float)$row->total_os;
               $npf = (float)$row->npf_os;
               $ratio = $total > 0 ? ($npf / $total) * 100.0 : 0.0;

               $series[] = [
                    'year' => (int)$row->period_year,
                    'month' => (int)$row->period_month,
                    'value' => (float)$ratio,
               ];
          }

          if (count($series) > $maxMonths) {
               $series = array_slice($series, -$maxMonths);
          }

          return $series;
     }

     private function buildTotalOutstandingSeries(int $endYear, int $endMonth, int $maxMonths): array
     {
          $endPeriod = $endYear * 100 + $endMonth;

          $rows = DB::table('pembiayaans')
               ->select('period_year', 'period_month', DB::raw('SUM(osmdlc) as total_os'))
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $series = [];
          foreach ($rows as $row) {
               $series[] = [
                    'year' => (int)$row->period_year,
                    'month' => (int)$row->period_month,
                    'value' => (float)$row->total_os,
               ];
          }

          if (count($series) > $maxMonths) {
               $series = array_slice($series, -$maxMonths);
          }

          return $series;
     }

     private function buildNpfOutstandingSeries(int $endYear, int $endMonth, int $maxMonths): array
     {
          $endPeriod = $endYear * 100 + $endMonth;

          $rows = DB::table('pembiayaans')
               ->select(
                    'period_year',
                    'period_month',
                    DB::raw("SUM(CASE WHEN colbaru IN ('3','4','5') THEN osmdlc ELSE 0 END) as npf_os")
               )
               ->whereNotNull('period_year')
               ->whereNotNull('period_month')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$endPeriod])
               ->groupBy('period_year', 'period_month')
               ->orderByRaw('(period_year * 100 + period_month) ASC')
               ->get();

          $series = [];
          foreach ($rows as $row) {
               $series[] = [
                    'year' => (int)$row->period_year,
                    'month' => (int)$row->period_month,
                    'value' => (float)$row->npf_os,
               ];
          }

          if (count($series) > $maxMonths) {
               $series = array_slice($series, -$maxMonths);
          }

          return $series;
     }

     private function resolveMacroForPeriod(int $year, int $month): array
     {
          if (!Schema::hasTable('macro_indicators')) {
               return ['bi_rate' => 0.0, 'inflation_yoy' => 0.0];
          }

          $key = $year * 100 + $month;
          $row = DB::table('macro_indicators')
               ->select('period_year', 'period_month', 'bi_rate', 'inflation_yoy')
               ->whereRaw('(period_year * 100 + period_month) <= ?', [$key])
               ->orderByRaw('(period_year * 100 + period_month) DESC')
               ->first();

          if (!$row) {
               return ['bi_rate' => 0.0, 'inflation_yoy' => 0.0];
          }

          return [
               'bi_rate' => $row->bi_rate !== null ? (float)$row->bi_rate : 0.0,
               'inflation_yoy' => $row->inflation_yoy !== null ? (float)$row->inflation_yoy : 0.0,
          ];
     }

     private function forecastFundingForTarget(
          string $python,
          string $mvScript,
          int $cutoffYear,
          int $cutoffMonth,
          int $targetYear,
          int $targetMonth,
          int $historyMonths,
          float $testFraction
     ): array {
          $builder = new FundingMultivariateFeatureBuilder();
          $trainingRows = $builder->buildTrainingRows($cutoffYear, $cutoffMonth, $historyMonths);
          if (count($trainingRows) < 3) {
               return ['ok' => false, 'error' => 'training rows multivariat tidak cukup (' . count($trainingRows) . ')'];
          }

          $featureOrder = [
               'lag_funding_total',
               'tabungan_count',
               'deposito_count',
               'tabungan_new_total',
               'tabungan_withdrawn_total',
               'deposito_net_inflow_total',
               'deposito_rate_avg',
               'deposito_rate_max',
               'deposito_rate_min',
               'macro_bi_rate',
               'macro_inflation_yoy',
          ];

          [$X, $y] = $builder->toMatrices($trainingRows, $featureOrder);
          $XNextAssoc = $builder->projectNextFeatures($trainingRows);
          if (!$XNextAssoc) {
               return ['ok' => false, 'error' => 'gagal membuat fitur proyeksi (X_next)'];
          }

          $macroTarget = $this->resolveMacroForPeriod($targetYear, $targetMonth);
          $XNextAssoc['macro_bi_rate'] = (float)($macroTarget['bi_rate'] ?? 0.0);
          $XNextAssoc['macro_inflation_yoy'] = (float)($macroTarget['inflation_yoy'] ?? 0.0);

          $XNext = [];
          foreach ($featureOrder as $k) {
               $XNext[] = (float)($XNextAssoc[$k] ?? 0.0);
          }

          $payload = [
               'metric' => 'funding_total_multivariate',
               'X' => $X,
               'y' => $y,
               'X_next' => $XNext,
               'test_fraction' => $testFraction,
               'non_negative' => true,
               'svr_params' => [
                    'kernel' => 'rbf',
                    'C' => 5,
                    'gamma' => 'scale',
                    'epsilon' => 0.25,
               ],
          ];

          return $this->runPythonForecast($python, $mvScript, $payload);
     }

     private function forecastNpfForTarget(
          string $python,
          string $npfWindowScript,
          int $cutoffYear,
          int $cutoffMonth,
          int $historyMonths,
          float $testFraction
     ): array {
          $builder = new NpfMultivariateFeatureBuilder();
          $monthlyRows = $builder->buildSlidingWindowRowsForNpfRatio($cutoffYear, $cutoffMonth, $historyMonths);
          $windowSize = 3;

          if (count($monthlyRows) < ($windowSize + 2)) {
               return ['ok' => false, 'error' => 'histori sliding-window tidak cukup (' . count($monthlyRows) . ')'];
          }

          $payload = [
               'metric' => 'npf_ratio_sliding_window',
               'rows' => $monthlyRows,
               'window_size' => $windowSize,
               'test_fraction' => $testFraction,
               'non_negative' => true,
               'svr_params' => [
                    'kernel' => 'rbf',
                    'C' => 100,
                    'gamma' => 0.1,
                    'epsilon' => 0.01,
               ],
          ];

          $result = $this->runPythonForecast($python, $npfWindowScript, $payload);
          if ($result['ok'] ?? false) {
               $result['prediction'] = max(0.0, min(100.0, (float)($result['prediction'] ?? 0.0)));
          }
          return $result;
     }

     private function runPythonForecast(string $pythonBin, string $scriptPath, array $payload): array
     {
          $process = new Process([$pythonBin, $scriptPath], base_path());
          $process->setInput(json_encode($payload));
          $process->setTimeout(120);

          $process->run();

          $output = trim($process->getOutput() ?: $process->getErrorOutput());
          $decoded = json_decode($output, true);

          if (!is_array($decoded)) {
               return [
                    'ok' => false,
                    'error' => 'Output Python tidak valid: ' . $output,
               ];
          }

          return $decoded;
     }

     private function ape(float $actual, float $pred): ?float
     {
          $denom = abs($actual);
          if ($denom < 1e-9) {
               return abs($pred) < 1e-9 ? 0.0 : null;
          }

          return (abs($actual - $pred) / $denom) * 100.0;
     }

     private function r2(array $yTrue, array $yPred): ?float
     {
          $n = count($yTrue);
          if ($n !== count($yPred) || $n < 2) {
               return null;
          }

          $mean = array_sum($yTrue) / $n;
          $ssTot = 0.0;
          $ssRes = 0.0;

          for ($i = 0; $i < $n; $i++) {
               $ssTot += ($yTrue[$i] - $mean) ** 2;
               $ssRes += ($yTrue[$i] - $yPred[$i]) ** 2;
          }

          if ($ssTot < 1e-12) {
               return null;
          }

          return 1.0 - ($ssRes / $ssTot);
     }

     private function resolveEffectiveLags(array $configuredLags, int $seriesLength): array
     {
          $configuredLags = array_values(array_filter(array_map('intval', $configuredLags), fn($v) => $v > 0));
          sort($configuredLags);

          // Python side requires len(values) >= max_lag + 3.
          $maxAllowedLag = $seriesLength - 3;
          if ($maxAllowedLag < 1) {
               return [];
          }

          $effective = array_values(array_filter($configuredLags, fn($lag) => $lag <= $maxAllowedLag));
          if (!$effective && $maxAllowedLag >= 1) {
               // Fallback to lag=1 if possible.
               return [1];
          }

          return $effective;
     }
}
