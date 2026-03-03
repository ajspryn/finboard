<?php

/**
 * Smoke test dashboard aggregates vs DB aggregates.
 *
 * Usage:
 *   php src/test/smoke-dashboard.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\DashboardController;
use App\Models\Deposito;
use App\Models\Pembiayaan;
use App\Models\Tabungan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function normalizeRange(string $range): string
{
     $range = strtolower(trim($range));
     $allowed = ['1d', '1w', '1m', '3m', '1y', 'ytd', 'all'];
     return in_array($range, $allowed, true) ? $range : 'all';
}

function calcWindow(array $q): array
{
     $range = normalizeRange((string)($q['range'] ?? 'all'));
     $startDay = $q['start_day'] ?? null;
     $endDay = $q['end_day'] ?? null;
     $month = (string)($q['month'] ?? '');
     $year = (string)($q['year'] ?? '');

     $startDay = ($startDay !== null && $startDay !== '') ? (string)$startDay : null;
     $endDay = ($endDay !== null && $endDay !== '') ? (string)$endDay : null;

     if ($startDay || $endDay) {
          if (!ctype_digit($year) || !ctype_digit($month)) {
               return [null, null];
          }
          $yearInt = (int)$year;
          $monthInt = (int)$month;
          if ($yearInt <= 0 || $monthInt < 1 || $monthInt > 12) {
               return [null, null];
          }

          $startDate = null;
          $endDate = null;
          if ($startDay && ctype_digit($startDay)) {
               $startDate = sprintf('%04d-%02d-%02d', $yearInt, $monthInt, (int)$startDay);
          }
          if ($endDay && ctype_digit($endDay)) {
               $endDate = sprintf('%04d-%02d-%02d', $yearInt, $monthInt, (int)$endDay);
          }

          return [$startDate, $endDate];
     }

     if ($range === 'all') {
          return [null, null];
     }

     if (!ctype_digit($year) || !ctype_digit($month)) {
          return [null, null];
     }
     $yearInt = (int)$year;
     $monthInt = (int)$month;
     if ($yearInt <= 0 || $monthInt < 1 || $monthInt > 12) {
          return [null, null];
     }

     $endOfPeriod = Carbon::create($yearInt, $monthInt, 1)->endOfMonth();
     $endMonthStart = Carbon::create($yearInt, $monthInt, 1)->startOfMonth();

     if ($range === 'ytd') {
          $start = Carbon::create($yearInt, 1, 1)->startOfDay();
     } else {
          $map = ['1d' => 1, '1w' => 1, '1m' => 1, '3m' => 3, '1y' => 12];
          $months = $map[$range] ?? null;
          if (!$months || $months < 1) {
               return [null, null];
          }
          $start = (clone $endMonthStart)->subMonths($months - 1);
     }

     return [$start->toDateString(), $endOfPeriod->toDateString()];
}

function fmt($v): string
{
     if ($v === null) {
          return 'null';
     }
     if (is_float($v)) {
          return rtrim(rtrim(sprintf('%.2f', $v), '0'), '.');
     }
     return (string)$v;
}

function asFloat($v): float
{
     return is_numeric($v) ? (float)$v : 0.0;
}

$user = User::first();
if (!$user) {
     fwrite(STDERR, "NO_USER\n");
     exit(1);
}
Auth::login($user);

$controller = app(DashboardController::class);

$latest = Pembiayaan::query()
     ->select('period_year', 'period_month')
     ->whereNotNull('period_year')
     ->whereNotNull('period_month')
     ->orderByRaw('period_year DESC, LPAD(period_month, 2, "0") DESC')
     ->first();

if (!$latest) {
     fwrite(STDERR, "NO_LATEST_PERIOD\n");
     exit(1);
}

$latestYear = (string)$latest->period_year;
$latestMonth = str_pad((string)(int)$latest->period_month, 2, '0', STR_PAD_LEFT);

echo "latest={$latestYear}-{$latestMonth}\n";

$scenarios = [
     ['month' => $latestMonth, 'year' => $latestYear, 'range' => 'all'],
     ['month' => $latestMonth, 'year' => $latestYear, 'range' => '1m'],
     ['month' => $latestMonth, 'year' => $latestYear, 'range' => '3m'],
     ['month' => $latestMonth, 'year' => $latestYear, 'range' => '1y'],
     ['month' => $latestMonth, 'year' => $latestYear, 'range' => 'ytd'],
     ['month' => $latestMonth, 'year' => $latestYear, 'range' => 'all', 'start_day' => '01', 'end_day' => '10'],
];

$sampleAo = Pembiayaan::query()->whereNotNull('kdaoh')->where('kdaoh', '!=', '')->value('kdaoh');
if (!$sampleAo) {
     $sampleAo = Pembiayaan::query()->whereNotNull('nmao')->where('nmao', '!=', '')->value('nmao');
}

foreach ($scenarios as $q) {
     $req = Request::create('/dashboard', 'GET', $q);
     app()->instance('request', $req);

     try {
          $resp = $controller->index($req);
          if ($resp instanceof Illuminate\Http\RedirectResponse) {
               echo "index " . json_encode($q) . " => REDIRECT\n";
               continue;
          }

          $data = method_exists($resp, 'getData') ? $resp->getData() : null;
          if (!is_array($data)) {
               echo "index " . json_encode($q) . " => UNEXPECTED_RESPONSE\n";
               continue;
          }

          $fm = (string)($data['filterMonth'] ?? $q['month']);
          $fy = (string)($data['filterYear'] ?? $q['year']);
          $rangeOut = (string)($data['range'] ?? $q['range']);

          [$absStart, $absEnd] = calcWindow(['month' => $fm, 'year' => $fy] + $q);

          $basePemb = Pembiayaan::query()->where('period_month', $fm)->where('period_year', $fy);
          if ($absStart) {
               $basePemb->whereDate('tgleff', '>=', $absStart);
          }
          if ($absEnd) {
               $basePemb->whereDate('tgleff', '<=', $absEnd);
          }

          $dbLendingTotal = (clone $basePemb)->sum('osmdlc');
          $dbLendingMargin = (clone $basePemb)->sum('osmgnc');
          $dbNasabah = (clone $basePemb)->count();

          $npfPemb = (clone $basePemb)->whereIn('colbaru', ['3', '4', '5']);
          $dbNpfTotal = (clone $npfPemb)->sum('osmdlc');
          $dbTunggakan = (clone $npfPemb)->sum('tgkpok');
          $dbNpfRatio = $dbLendingTotal > 0 ? round(($dbNpfTotal / $dbLendingTotal) * 100, 2) : 0;

          $baseTab = Tabungan::query()->where('period_month', $fm)->where('period_year', $fy);
          if ($absStart) {
               $baseTab->whereDate('tgltrnakh', '>=', $absStart);
          }
          if ($absEnd) {
               $baseTab->whereDate('tgltrnakh', '<=', $absEnd);
          }

          $baseDep = Deposito::query()->where('period_month', $fm)->where('period_year', $fy);
          if ($absStart) {
               $baseDep->whereDate('tglbuka', '>=', $absStart);
          }
          if ($absEnd) {
               $baseDep->whereDate('tglbuka', '<=', $absEnd);
          }

          $dbTabTotal = (clone $baseTab)->sum('sahirrp');
          $dbDepTotal = (clone $baseDep)->sum('nomrp');
          $dbFundTotal = $dbTabTotal + $dbDepTotal;

          $lending = $data['lending'] ?? [];
          $npf = $data['npf'] ?? [];
          $funding = $data['funding'] ?? [];

          $checks = [
               ['lending.total', asFloat($lending['total'] ?? null), (float)$dbLendingTotal],
               ['lending.margin', asFloat($lending['margin'] ?? null), (float)$dbLendingMargin],
               ['lending.nasabah', asFloat($lending['nasabah'] ?? null), (float)$dbNasabah],
               ['npf.total', asFloat($npf['total'] ?? null), (float)$dbNpfTotal],
               ['npf.tunggakan_pokok', asFloat($npf['tunggakan_pokok'] ?? null), (float)$dbTunggakan],
               ['npf.ratio', asFloat($npf['ratio'] ?? null), (float)$dbNpfRatio],
               ['funding.total', asFloat($funding['total'] ?? null), (float)$dbFundTotal],
               ['funding.nominal.Tabungan', asFloat($funding['nominal']['Tabungan'] ?? null), (float)$dbTabTotal],
               ['funding.nominal.Deposito', asFloat($funding['nominal']['Deposito'] ?? null), (float)$dbDepTotal],
          ];

          $ok = true;
          foreach ($checks as [$label, $c, $d]) {
               if (abs($c - $d) > 0.0001) {
                    $ok = false;
                    break;
               }
          }

          echo "index " . json_encode($q) . " => month={$fm} year={$fy} range={$rangeOut} window=[" . fmt($absStart) . ',' . fmt($absEnd) . '] ' . ($ok ? 'OK' : 'DIFF') . "\n";

          if (!$ok) {
               foreach ($checks as [$label, $c, $d]) {
                    $diff = $c - $d;
                    if (abs($diff) > 0.0001) {
                         echo "  - {$label}: controller=" . fmt($c) . ' db=' . fmt($d) . ' diff=' . fmt($diff) . "\n";
                    }
               }
          }

          // Extra: validate aoFundingData sum equals deposito sum for kdprd 31/41 with stsrec A + kodeaoh present
          if (isset($data['aoFundingData']) && $data['aoFundingData'] instanceof Illuminate\Support\Collection) {
               $sumAoFunding = (float)$data['aoFundingData']->sum('total_funding');
               $depAo = DB::table('depositos')
                    ->where('period_month', $fm)
                    ->where('period_year', $fy)
                    ->when($absStart, fn($qq) => $qq->whereDate('tglbuka', '>=', $absStart))
                    ->when($absEnd, fn($qq) => $qq->whereDate('tglbuka', '<=', $absEnd))
                    ->where('stsrec', 'A')
                    ->whereNotNull('kodeaoh')
                    ->where('kodeaoh', '!=', '')
                    ->whereIn('kdprd', ['31', '41'])
                    ->sum('nomrp');

               $d = abs($sumAoFunding - (float)$depAo);
               echo '  aoFundingData.sum total_funding=' . fmt($sumAoFunding) . ' db_deposito(kdprd31/41,stsrecA,kodeaoh)=' . fmt($depAo) . ' ' . ($d < 0.0001 ? 'OK' : 'DIFF') . "\n";
          }

          // Optional: AO endpoints sanity for one AO key
          if ($sampleAo) {
               $ao = (string)$sampleAo;

               // AODetail expected totals
               $aoBase = Pembiayaan::query()->where('period_month', $fm)->where('period_year', $fy)
                    ->where(function ($sub) use ($ao) {
                         $sub->where('kdaoh', $ao)->orWhere('nmao', $ao);
                    });
               if ($absStart) {
                    $aoBase->whereDate('tgleff', '>=', $absStart);
               }
               if ($absEnd) {
                    $aoBase->whereDate('tgleff', '<=', $absEnd);
               }

               $aoOutstanding = (float)(clone $aoBase)->sum('osmdlc');
               $aoDisburse = (float)(clone $aoBase)->sum('mdlawal');
               $aoCount = (int)(clone $aoBase)->count();

               $aoResp = $controller->getAODetail($ao);
               $aoJson = json_decode($aoResp->getContent(), true);
               $aoSummary = $aoJson['summary'] ?? [];

               $okAo = abs(asFloat($aoSummary['total_outstanding'] ?? null) - $aoOutstanding) < 0.0001
                    && abs(asFloat($aoSummary['total_disburse'] ?? null) - $aoDisburse) < 0.0001
                    && (int)($aoSummary['total_kontrak'] ?? -1) === $aoCount;

               echo '  getAODetail(ao=' . $ao . ') ' . ($okAo ? 'OK' : 'DIFF') . "\n";
          }
     } catch (Throwable $e) {
          echo "index " . json_encode($q) . ' => ERROR ' . get_class($e) . ' ' . $e->getMessage() . "\n";
     }
}
