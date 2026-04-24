<?php

namespace App\Console\Commands;

use App\Services\MacroIndicatorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncMacroIndicators extends Command
{
     /**
      * Examples:
      *   php artisan macro:sync --months-back=36
      *   php artisan macro:sync --start=2024-01 --end=2026-03
      */
     protected $signature = 'macro:sync {--months-back=36} {--start=} {--end=}';

     protected $description = 'Sync BI rate & inflation into macro_indicators (Inflation: BPS if configured; fallback: FRED)';

     public function handle(MacroIndicatorService $service): int
     {
          $startOpt = $this->option('start');
          $endOpt = $this->option('end');

          try {
               if ($startOpt || $endOpt) {
                    if (!$startOpt || !$endOpt) {
                         $this->error('Jika pakai --start, harus juga isi --end (format YYYY-MM).');
                         return self::FAILURE;
                    }

                    $start = Carbon::createFromFormat('Y-m', (string) $startOpt)->startOfMonth();
                    $end = Carbon::createFromFormat('Y-m', (string) $endOpt)->startOfMonth();
               } else {
                    $monthsBack = (int) $this->option('months-back');
                    if ($monthsBack < 1) {
                         $monthsBack = 1;
                    }

                    $end = now()->startOfMonth();
                    $start = now()->startOfMonth()->subMonths($monthsBack - 1);
               }
          } catch (\Throwable $e) {
               $this->error('Format tanggal tidak valid. Gunakan YYYY-MM.');
               return self::FAILURE;
          }

          $this->info(sprintf('Sync macro indicators: %s .. %s', $start->format('Y-m'), $end->format('Y-m')));

          try {
               $result = $service->syncFromInternet($start, $end);
          } catch (\Throwable $e) {
               $this->error('Gagal sync dari internet: ' . $e->getMessage());
               return self::FAILURE;
          }

          $suffix = '';
          if (!empty($result['inflation_provider'])) {
               $suffix .= ' | Inflation provider=' . (string) $result['inflation_provider'];
          }

          $this->info(sprintf(
               'OK. Updated=%d, Skipped(no data)=%d | Source=%s | BI series=%s | Inflation series=%s%s',
               (int) ($result['updated'] ?? 0),
               (int) ($result['skipped'] ?? 0),
               (string) ($result['source'] ?? '-'),
               (string) ($result['bi_rate_series_id'] ?? '-'),
               (string) ($result['inflation_yoy_series_id'] ?? '-'),
               $suffix
          ));

          return self::SUCCESS;
     }
}
