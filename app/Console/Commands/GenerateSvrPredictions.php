<?php

namespace App\Console\Commands;

use App\Models\FinancialMetricPrediction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use App\Services\FundingMultivariateFeatureBuilder;
use App\Services\LendingMultivariateFeatureBuilder;
use App\Services\NpfMultivariateFeatureBuilder;
use App\Services\MacroIndicatorService;

class GenerateSvrPredictions extends Command
{
     protected $signature = 'predictions:generate-svr
     {--year= : Target year to predict (bulan ini), e.g. 2025}
     {--month= : Target month to predict (bulan ini), 1-12}
        {--max-months= : Max months of history to use (default from config)}';

     protected $description = 'Generate SVR predictions for Funding, Lending, and NPF (Python scikit-learn)';

     public function handle(MacroIndicatorService $macroService): int
     {
          if (!Schema::hasTable('financial_metric_predictions')) {
               $this->error("Tabel financial_metric_predictions belum ada. Jalankan: php artisan migrate");
               return Command::FAILURE;
          }

          [$targetYear, $targetMonth] = $this->resolveEndPeriod();
          if (!$targetYear || !$targetMonth) {
               $this->error('Tidak bisa menentukan periode akhir (year/month).');
               return Command::FAILURE;
          }

          $target = Carbon::create($targetYear, $targetMonth, 1);
          $cutoff = (clone $target)->subMonth();
          $cutoffYear = (int)$cutoff->year;
          $cutoffMonth = (int)$cutoff->month;

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

          $this->info(sprintf('Target prediksi: %04d-%02d', $targetYear, $targetMonth));
          $this->info(sprintf('Periode training cutoff: %04d-%02d', $cutoffYear, $cutoffMonth));

          $historyMonths = max($minHistoryMonths, min($maxMonths, $trainWindowMonths));

          // Sync BI rate & inflation from internet into local DB.
          if (Schema::hasTable('macro_indicators')) {
               try {
                    $macroStart = (clone $cutoff)->startOfMonth()->subMonths($historyMonths + 2);
                    // PDF requires BI rate & inflation for the predicted month.
                    $macroEnd = (clone $target)->startOfMonth();
                    $macroService->syncFromInternet($macroStart, $macroEnd);
               } catch (\Throwable $e) {
                    $this->warn('Macro sync gagal (akan lanjut dengan nilai 0 jika belum ada data): ' . $e->getMessage());
               }
          } else {
               $this->warn('Tabel macro_indicators belum ada; jalankan: php artisan migrate');
          }

          // IMPORTANT: order matters (Lending uses predicted DPK + predicted NPF per PDF).
          $metrics = [
               'funding_total' => $this->buildFundingSeries($cutoffYear, $cutoffMonth, $historyMonths),
               'npf_ratio' => $this->buildNpfSeries($cutoffYear, $cutoffMonth, $historyMonths),
               'lending_outstanding' => $this->buildLendingSeries($cutoffYear, $cutoffMonth, $historyMonths),
          ];

          $lags = (array)config('predictions.svr.lags', [1, 2, 3, 6, 12]);
          $testFraction = (float)config('predictions.svr.test_fraction', 0.2);
          $lagSearch = (bool)config('predictions.svr.lag_search', true);
          $lagSearchMaxSets = (int)config('predictions.svr.lag_search_max_sets', 6);
          $npfMaxMonths = (int)config('predictions.svr.npf_max_months', 6);
          if ($npfMaxMonths < $minHistoryMonths) {
               $npfMaxMonths = $minHistoryMonths;
          }

          $npfWindowScript = base_path('ml/svr_npf_sliding_window.py');
          if (!is_file($npfWindowScript)) {
               $this->error("Script tidak ditemukan: {$npfWindowScript}");
               return Command::FAILURE;
          }

          $anySuccess = false;
          $predictedFunding = null;
          $predictedNpf = null;

          foreach ($metrics as $metricKey => $series) {
               if (count($series) < $minHistoryMonths) {
                    $this->warn("{$metricKey}: histori hanya " . count($series) . " bulan; butuh minimal {$minHistoryMonths} bulan untuk prediksi");
                    continue;
               }

               if ($metricKey === 'funding_total') {
                    $builder = new FundingMultivariateFeatureBuilder();
                    $trainingRows = $builder->buildTrainingRows($cutoffYear, $cutoffMonth, $historyMonths);
                    if (count($trainingRows) < 3) {
                         $this->warn("{$metricKey}: training rows multivariat tidak cukup (" . count($trainingRows) . ")");
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
                         $this->warn("{$metricKey}: gagal membuat fitur proyeksi (X_next)");
                         continue;
                    }

                    // Ensure macro features are for the predicted month (target period), not the cutoff month.
                    $macroTarget = $this->resolveMacroForPeriod($targetYear, $targetMonth);
                    $XNextAssoc['macro_bi_rate'] = (float)($macroTarget['bi_rate'] ?? 0.0);
                    $XNextAssoc['macro_inflation_yoy'] = (float)($macroTarget['inflation_yoy'] ?? 0.0);
                    $XNext = [];
                    foreach ($featureOrder as $k) {
                         $XNext[] = (float)($XNextAssoc[$k] ?? 0);
                    }

                    $mvPayload = [
                         'metric' => 'funding_total_multivariate',
                         'X' => $X,
                         'y' => $y,
                         'X_next' => $XNext,
                         'test_fraction' => $testFraction,
                         'non_negative' => true,
                         // PDF (Tabungan/Deposito): kernel=rbf, C=5, epsilon=0.25, gamma=scale.
                         'svr_params' => [
                              'kernel' => 'rbf',
                              'C' => 5,
                              'epsilon' => 0.25,
                              'gamma' => 'scale',
                         ],
                    ];

                    $result = $this->runPythonForecast($python, $mvScript, $mvPayload);
                    if (!$result['ok']) {
                         $this->error("{$metricKey}: gagal (multivariat) - " . ($result['error'] ?? 'unknown error'));
                         continue;
                    }

                    $prediction = (float)$result['prediction'];
                    $predictedFunding = $prediction;
                    $anySuccess = true;

                    FinancialMetricPrediction::updateOrCreate(
                         [
                              'model_name' => 'svr',
                              'metric_key' => $metricKey,
                              'period_year' => $targetYear,
                              'period_month' => $targetMonth,
                         ],
                         [
                              'predicted_value' => $prediction,
                              'r2' => $result['r2'] ?? null,
                              'mape' => $result['mape'] ?? null,
                              'train_size' => $result['train_size'] ?? null,
                              'test_size' => $result['test_size'] ?? null,
                              'train_end_year' => $cutoffYear,
                              'train_end_month' => $cutoffMonth,
                              'details' => [
                                   'mode' => 'multivariate',
                                   'feature_order' => $featureOrder,
                                   'X_next' => $XNextAssoc,
                                   'svr_params' => $mvPayload['svr_params'],
                                   'best_params' => $result['best_params'] ?? null,
                                   'warnings' => $result['warnings'] ?? [],
                                   'test_fraction' => $testFraction,
                                   'max_months' => $maxMonths,
                                   'train_window_months' => $historyMonths,
                                   'min_history_months' => $minHistoryMonths,
                                   'target_period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                   'cutoff_period' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                              ],
                         ]
                    );

                    $this->info(sprintf(
                         '%s -> prediksi %04d-%02d = %s | R2=%s | MAPE=%s%% (multivariat)',
                         $metricKey,
                         $targetYear,
                         $targetMonth,
                         $this->formatNumber($metricKey, $prediction),
                         isset($result['r2']) && $result['r2'] !== null ? number_format((float)$result['r2'], 4) : '-',
                         isset($result['mape']) && $result['mape'] !== null ? number_format((float)$result['mape'], 2) : '-'
                    ));

                    continue;
               }

               if ($metricKey === 'npf_ratio') {
                    $npfHistoryMonths = max($minHistoryMonths, min($maxMonths, $trainWindowMonths, $npfMaxMonths));

                    $builder = new NpfMultivariateFeatureBuilder();
                    $monthlyRows = $builder->buildSlidingWindowRowsForNpfRatio($cutoffYear, $cutoffMonth, $npfHistoryMonths);

                    // Need at least window_size + 2 months to build a reasonable dataset.
                    $windowSize = 3;
                    if (count($monthlyRows) < ($windowSize + 2)) {
                         $this->warn("{$metricKey}: histori tidak cukup untuk sliding-window (" . count($monthlyRows) . " bulan; butuh >=" . ($windowSize + 2) . ")");
                         continue;
                    }

                    $payload = [
                         'metric' => 'npf_ratio_sliding_window',
                         'rows' => $monthlyRows,
                         'window_size' => $windowSize,
                         'test_fraction' => $testFraction,
                         'non_negative' => true,
                         // PDF (NPF): kernel=rbf, C=100, gamma=0.1, epsilon=0.01
                         'svr_params' => [
                              'kernel' => 'rbf',
                              'C' => 100,
                              'gamma' => 0.1,
                              'epsilon' => 0.01,
                         ],
                    ];

                    $result = $this->runPythonForecast($python, $npfWindowScript, $payload);
                    if (!$result['ok']) {
                         $this->error("{$metricKey}: gagal (sliding-window) - " . ($result['error'] ?? 'unknown error'));
                         continue;
                    }

                    $prediction = (float)$result['prediction'];
                    $predictedNpf = $prediction;
                    $anySuccess = true;

                    FinancialMetricPrediction::updateOrCreate(
                         [
                              'model_name' => 'svr',
                              'metric_key' => $metricKey,
                              'period_year' => $targetYear,
                              'period_month' => $targetMonth,
                         ],
                         [
                              'predicted_value' => $prediction,
                              'r2' => $result['r2'] ?? null,
                              'mape' => $result['mape'] ?? null,
                              'train_size' => $result['train_size'] ?? null,
                              'test_size' => $result['test_size'] ?? null,
                              'train_end_year' => $cutoffYear,
                              'train_end_month' => $cutoffMonth,
                              'details' => [
                                   'mode' => 'sliding_window',
                                   'window_size' => $windowSize,
                                   'input_columns' => ['bulan_num', 'npf_ratio', 'outstanding', 'tunggakan_pokok', 'rate_eff'],
                                   'svr_params' => $payload['svr_params'],
                                   'best_params' => $result['best_params'] ?? null,
                                   'warnings' => $result['warnings'] ?? [],
                                   'test_fraction' => $testFraction,
                                   'max_months' => $maxMonths,
                                   'train_window_months' => $npfHistoryMonths,
                                   'min_history_months' => $minHistoryMonths,
                                   'target_period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                   'cutoff_period' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                              ],
                         ]
                    );

                    $this->info(sprintf(
                         '%s -> prediksi %04d-%02d = %s | R2=%s | MAPE=%s%% (sliding-window=%d)',
                         $metricKey,
                         $targetYear,
                         $targetMonth,
                         $this->formatNumber($metricKey, $prediction),
                         isset($result['r2']) && $result['r2'] !== null ? number_format((float)$result['r2'], 4) : '-',
                         isset($result['mape']) && $result['mape'] !== null ? number_format((float)$result['mape'], 2) : '-',
                         $windowSize,
                    ));

                    continue;
               }

               if ($metricKey === 'lending_outstanding') {
                    $builder = new LendingMultivariateFeatureBuilder();
                    $trainingRows = $builder->buildTrainingRows($cutoffYear, $cutoffMonth, $historyMonths);
                    if (count($trainingRows) < 3) {
                         $this->warn("{$metricKey}: training rows multivariat tidak cukup (" . count($trainingRows) . ")");
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
                    $last = $trainingRows[count($trainingRows) - 1];
                    $macroTarget = $this->resolveMacroForPeriod($targetYear, $targetMonth);

                    // PDF: Lag1 for the predicted month uses the latest observed outstanding (cutoff month).
                    $XNextAssoc = [
                         'lag_outstanding' => (float)($last['y'] ?? 0.0),
                         // PDF requires DPK and NPF for the predicted month; we use our own predictions when available.
                         'dpk_total' => $predictedFunding !== null ? (float)$predictedFunding : (float)($last['features']['dpk_total'] ?? 0.0),
                         'npf_ratio' => $predictedNpf !== null ? (float)$predictedNpf : (float)($last['features']['npf_ratio'] ?? 0.0),
                         'macro_bi_rate' => (float)($macroTarget['bi_rate'] ?? 0.0),
                         'macro_inflation_yoy' => (float)($macroTarget['inflation_yoy'] ?? 0.0),
                    ];
                    $XNext = [];
                    foreach ($featureOrder as $k) {
                         $XNext[] = (float)($XNextAssoc[$k] ?? 0);
                    }

                    $mvPayload = [
                         'metric' => 'lending_outstanding_multivariate',
                         'X' => $X,
                         'y' => $y,
                         'X_next' => $XNext,
                         'test_fraction' => $testFraction,
                         'non_negative' => true,
                         // PDF (Pembiayaan): kernel=rbf, C=100, epsilon=0.01, gamma=scale.
                         'svr_params' => [
                              'kernel' => 'rbf',
                              'C' => 100,
                              'epsilon' => 0.01,
                              'gamma' => 'scale',
                         ],
                    ];

                    $result = $this->runPythonForecast($python, $mvScript, $mvPayload);
                    if (!$result['ok']) {
                         $this->error("{$metricKey}: gagal (multivariat) - " . ($result['error'] ?? 'unknown error'));
                         continue;
                    }

                    $prediction = (float)$result['prediction'];
                    $anySuccess = true;

                    FinancialMetricPrediction::updateOrCreate(
                         [
                              'model_name' => 'svr',
                              'metric_key' => $metricKey,
                              'period_year' => $targetYear,
                              'period_month' => $targetMonth,
                         ],
                         [
                              'predicted_value' => $prediction,
                              'r2' => $result['r2'] ?? null,
                              'mape' => $result['mape'] ?? null,
                              'train_size' => $result['train_size'] ?? null,
                              'test_size' => $result['test_size'] ?? null,
                              'train_end_year' => $cutoffYear,
                              'train_end_month' => $cutoffMonth,
                              'details' => [
                                   'mode' => 'multivariate',
                                   'feature_order' => $featureOrder,
                                   'X_next' => $XNextAssoc,
                                   'svr_params' => $mvPayload['svr_params'],
                                   'best_params' => $result['best_params'] ?? null,
                                   'warnings' => $result['warnings'] ?? [],
                                   'test_fraction' => $testFraction,
                                   'max_months' => $maxMonths,
                                   'train_window_months' => $historyMonths,
                                   'min_history_months' => $minHistoryMonths,
                                   'target_period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                   'cutoff_period' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                              ],
                         ]
                    );

                    $this->info(sprintf(
                         '%s -> prediksi %04d-%02d = %s | R2=%s | MAPE=%s%% (multivariat)',
                         $metricKey,
                         $targetYear,
                         $targetMonth,
                         $this->formatNumber($metricKey, $prediction),
                         isset($result['r2']) && $result['r2'] !== null ? number_format((float)$result['r2'], 4) : '-',
                         isset($result['mape']) && $result['mape'] !== null ? number_format((float)$result['mape'], 2) : '-'
                    ));

                    continue;
               }

               if ($metricKey === 'npf_ratio') {
                    $npfHistoryMonths = max($minHistoryMonths, min($maxMonths, $trainWindowMonths, $npfMaxMonths));

                    $builder = new NpfMultivariateFeatureBuilder();
                    $totalRows = $builder->buildTrainingRowsForTotalOs($cutoffYear, $cutoffMonth, $npfHistoryMonths);
                    $npfRows = $builder->buildTrainingRowsForNpfOs($cutoffYear, $cutoffMonth, $npfHistoryMonths);

                    $minRows = max(3, $minHistoryMonths - 1);
                    if (count($totalRows) < $minRows || count($npfRows) < $minRows) {
                         $this->warn("{$metricKey}: training rows multivariat tidak cukup (total=" . count($totalRows) . ", npf=" . count($npfRows) . "; minimal {$minRows})");
                         continue;
                    }

                    $totalFeatureOrder = [
                         'lag_total_os',
                         'contracts_count',
                         'customers_count',
                         'plafon_total',
                         'avg_jw',
                         'lag_npf_ratio',
                         'macro_bi_rate',
                         'macro_inflation_yoy',
                    ];
                    $npfFeatureOrder = [
                         'lag_npf_os',
                         'lag_total_os',
                         'npf_contracts_count',
                         'contracts_count',
                         'customers_count',
                         'avg_haritgkmdl',
                         'avg_jw',
                         'lag_npf_ratio',
                         'macro_bi_rate',
                         'macro_inflation_yoy',
                    ];

                    [$totalX, $totalY] = $builder->toMatrices($totalRows, $totalFeatureOrder);
                    $totalXNextAssoc = $builder->projectNextFeatures($totalRows);
                    if (!$totalXNextAssoc) {
                         $this->warn("{$metricKey}: gagal membuat fitur proyeksi total_os (X_next)");
                         continue;
                    }
                    $totalXNext = [];
                    foreach ($totalFeatureOrder as $k) {
                         $totalXNext[] = (float)($totalXNextAssoc[$k] ?? 0);
                    }

                    [$npfX, $npfY] = $builder->toMatrices($npfRows, $npfFeatureOrder);
                    $npfXNextAssoc = $builder->projectNextFeatures($npfRows);
                    if (!$npfXNextAssoc) {
                         $this->warn("{$metricKey}: gagal membuat fitur proyeksi npf_os (X_next)");
                         continue;
                    }
                    $npfXNext = [];
                    foreach ($npfFeatureOrder as $k) {
                         $npfXNext[] = (float)($npfXNextAssoc[$k] ?? 0);
                    }

                    $totalPayload = [
                         'metric' => 'npf_total_os_multivariate',
                         'X' => $totalX,
                         'y' => $totalY,
                         'X_next' => $totalXNext,
                         'test_fraction' => $testFraction,
                         'non_negative' => true,
                    ];
                    $npfPayload = [
                         'metric' => 'npf_os_multivariate',
                         'X' => $npfX,
                         'y' => $npfY,
                         'X_next' => $npfXNext,
                         'test_fraction' => $testFraction,
                         'non_negative' => true,
                    ];

                    $totalResult = $this->runPythonForecast($python, $mvScript, $totalPayload);
                    if (!$totalResult['ok']) {
                         $this->error("{$metricKey}: gagal (total_os multivariat) - " . ($totalResult['error'] ?? 'unknown error'));
                         continue;
                    }
                    $npfResult = $this->runPythonForecast($python, $mvScript, $npfPayload);
                    if (!$npfResult['ok']) {
                         $this->error("{$metricKey}: gagal (npf_os multivariat) - " . ($npfResult['error'] ?? 'unknown error'));
                         continue;
                    }

                    $predTotal = (float)$totalResult['prediction'];
                    $predNpf = (float)$npfResult['prediction'];
                    $prediction = $predTotal > 1e-9 ? ($predNpf / $predTotal) * 100.0 : 0.0;
                    $prediction = max(0.0, min(100.0, $prediction));

                    FinancialMetricPrediction::updateOrCreate(
                         [
                              'metric_key' => $metricKey,
                              'period_year' => $targetYear,
                              'period_month' => $targetMonth,
                              'model_name' => 'svr',
                         ],
                         [
                              'predicted_value' => $prediction,
                              'r2' => null,
                              'mape' => null,
                              'train_size' => null,
                              'test_size' => null,
                              'train_end_year' => $cutoffYear,
                              'train_end_month' => $cutoffMonth,
                              'details' => [
                                   'method' => 'ratio_from_components',
                                   'mode' => 'multivariate_components',
                                   'pred_total_os' => $predTotal,
                                   'pred_npf_os' => $predNpf,
                                   'components' => [
                                        'total_os' => $totalResult,
                                        'npf_os' => $npfResult,
                                   ],
                                   'warnings' => array_values(array_filter(array_merge(
                                        $totalResult['warnings'] ?? [],
                                        $npfResult['warnings'] ?? [],
                                   ))),
                                   'feature_order' => [
                                        'total_os' => $totalFeatureOrder,
                                        'npf_os' => $npfFeatureOrder,
                                   ],
                                   'X_next' => [
                                        'total_os' => $totalXNextAssoc,
                                        'npf_os' => $npfXNextAssoc,
                                   ],
                                   'test_fraction' => $testFraction,
                                   'max_months' => $maxMonths,
                                   'train_window_months' => $npfHistoryMonths,
                                   'min_history_months' => $minHistoryMonths,
                                   'target_period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                                   'cutoff_period' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                              ],
                         ]
                    );

                    $anySuccess = true;
                    $this->info(sprintf(
                         '%s -> prediksi %04d-%02d = %s | (component-based multivariat; R2/MAPE ratio n/a)',
                         $metricKey,
                         $targetYear,
                         $targetMonth,
                         $this->formatNumber($metricKey, $prediction),
                    ));

                    continue;
               }

               $effectiveLags = $this->resolveEffectiveLags($lags, count($series));
               if (!$effectiveLags) {
                    $this->warn("{$metricKey}: histori tidak cukup untuk SVR dengan konfigurasi lag saat ini");
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
                    $this->error("{$metricKey}: gagal - " . ($result['error'] ?? 'unknown error'));
                    continue;
               }

               $prediction = (float)$result['prediction'];

               FinancialMetricPrediction::updateOrCreate(
                    [
                         'metric_key' => $metricKey,
                         'period_year' => $targetYear,
                         'period_month' => $targetMonth,
                         'model_name' => 'svr',
                    ],
                    [
                         'predicted_value' => $prediction,
                         'r2' => $result['r2'],
                         'mape' => $result['mape'],
                         'train_size' => $result['train_size'],
                         'test_size' => $result['test_size'],
                         'train_end_year' => $cutoffYear,
                         'train_end_month' => $cutoffMonth,
                         'details' => [
                              'best_params' => $result['best_params'] ?? null,
                              'warnings' => $result['warnings'] ?? [],
                              'lags' => $payload['lags'],
                              'test_fraction' => $testFraction,
                              'max_months' => $maxMonths,
                              'train_window_months' => $historyMonths,
                              'min_history_months' => $minHistoryMonths,
                              'target_period' => sprintf('%04d-%02d', $targetYear, $targetMonth),
                              'cutoff_period' => sprintf('%04d-%02d', $cutoffYear, $cutoffMonth),
                         ],
                    ]
               );

               $anySuccess = true;

               $r2 = $result['r2'];
               $mape = $result['mape'];
               $this->info(sprintf(
                    '%s -> prediksi %04d-%02d = %s | R2=%s | MAPE=%s%%',
                    $metricKey,
                    $targetYear,
                    $targetMonth,
                    $this->formatNumber($metricKey, $prediction),
                    $r2 === null ? '-' : number_format((float)$r2, 4),
                    $mape === null ? '-' : number_format((float)$mape, 2)
               ));
          }

          if (!$anySuccess) {
               $this->error('Tidak ada prediksi yang berhasil dibuat.');
               return Command::FAILURE;
          }

          return Command::SUCCESS;
     }

     private function resolveEndPeriod(): array
     {
          $yearOpt = $this->option('year');
          $monthOpt = $this->option('month');

          if ($yearOpt !== null || $monthOpt !== null) {
               $year = (int)$yearOpt;
               $month = (int)$monthOpt;
               if ($year > 0 && $month >= 1 && $month <= 12) {
                    return [$year, $month];
               }

               return [null, null];
          }

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

     /**
      * Resolve macro values for a given period (YYYY-MM) using last-known <= period.
      * This matches the monthly nature required by the PDF and avoids zeros when the newest month is not yet available.
      */
     private function resolveMacroForPeriod(int $year, int $month): array
     {
          if (!Schema::hasTable('macro_indicators')) {
               return ['bi_rate' => 0.0, 'inflation_yoy' => 0.0];
          }

          $key = $year * 100 + $month;
          $row = DB::table('macro_indicators')
               ->select('bi_rate', 'inflation_yoy')
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
          foreach ($byPeriod as $key => $row) {
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

          $series = $rows->map(function ($row) {
               return [
                    'year' => (int)$row->period_year,
                    'month' => (int)$row->period_month,
                    'value' => (float)$row->total,
               ];
          })->all();

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
                    'value' => $ratio,
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

     private function formatNumber(string $metricKey, float $value): string
     {
          if ($metricKey === 'npf_ratio') {
               return number_format($value, 4) . '%';
          }

          return number_format($value, 2, '.', ',');
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
               return [1];
          }

          return $effective;
     }
}
