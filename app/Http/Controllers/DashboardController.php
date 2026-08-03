<?php

namespace App\Http\Controllers;

use App\Models\Pembiayaan;
use App\Models\Tabungan;
use App\Models\Deposito;
use App\Models\FinancialMetricPrediction;
use App\Models\Linkage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private function normalizeSegmentTableGroupBy(?string $groupBy): string
    {
        $groupBy = strtolower(trim((string) $groupBy));
        $allowed = ['segmentasi', 'sektor_ekonomi'];

        return in_array($groupBy, $allowed, true) ? $groupBy : 'segmentasi';
    }

    private function normalizeDashboardRange(?string $range): string
    {
        $range = strtolower(trim((string)$range));
        $allowedRanges = ['1d', '1w', '1m', '3m', '1y', 'ytd', 'all'];
        if (!in_array($range, $allowedRanges, true)) {
            return 'all';
        }

        return $range;
    }

    private function resolveDashboardDateWindow(string $range, $startDay, $endDay, string $filterMonth, string $filterYear): array
    {
        $startDay = $startDay !== null && $startDay !== '' ? (string)$startDay : null;
        $endDay = $endDay !== null && $endDay !== '' ? (string)$endDay : null;

        // Explicit day-range takes precedence over range.
        if ($startDay || $endDay) {
            if (!ctype_digit((string)$filterYear) || !ctype_digit((string)$filterMonth)) {
                return [null, null];
            }

            $yearInt = (int)$filterYear;
            $monthInt = (int)$filterMonth;
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

        $range = $this->normalizeDashboardRange($range);
        if ($range === 'all') {
            return [null, null];
        }

        if (!ctype_digit((string)$filterYear) || !ctype_digit((string)$filterMonth)) {
            return [null, null];
        }

        $yearInt = (int)$filterYear;
        $monthInt = (int)$filterMonth;
        if ($yearInt <= 0 || $monthInt < 1 || $monthInt > 12) {
            return [null, null];
        }

        $rangeMonthsMap = [
            '1d' => 1,
            '1w' => 1,
            '1m' => 1,
            '3m' => 3,
            '1y' => 12,
            'ytd' => null,
            'all' => null,
        ];

        $endOfPeriod = Carbon::create($yearInt, $monthInt, 1)->endOfMonth();
        $endMonthStart = Carbon::create($yearInt, $monthInt, 1)->startOfMonth();

        if ($range === 'ytd') {
            $start = Carbon::create($yearInt, 1, 1)->startOfDay();
        } else {
            $months = $rangeMonthsMap[$range] ?? null;
            if (!$months || $months < 1) {
                return [null, null];
            }
            $start = (clone $endMonthStart)->subMonths($months - 1);
        }

        return [$start->toDateString(), $endOfPeriod->toDateString()];
    }

    private function applyOptionalDateFilter($query, string $column, ?string $startDate, ?string $endDate)
    {
        if ($startDate) {
            $query->whereDate($column, '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate($column, '<=', $endDate);
        }

        return $query;
    }

    private function getAoMapping(): array
    {
        return [
            '017' => 'AGUS SETIAWAN',
            '018' => 'ADITYA FATAHILLAH MUHARAM',
            '020' => 'TAUFAN NUGRAHA',
            '021' => 'SURYA SEPTIANNANDA',
            '022' => 'FACHRI EKA PUTRA',
            '023' => 'RIZKI NIRMALA',
            '024' => 'GUNANTO',
            '025' => 'SANDI M ILHAM',
            '026' => 'FEISHAL JUAENI',
            '027' => 'ZAINAL ARIFIN',
            '028' => 'RIVI NUGRAHA',
            '029' => 'YOHAN EKA PUTRA',
            '030' => 'YUSRON WIJAYA',
            '031' => 'SABIQ KHUSNAIDI',
            '032' => 'YUNITA HERDIANA',
            '033' => 'YUSI IRMAYANTI',
            '034' => 'LARIZA AFRIANTI',
            '035' => 'DEVI NURLIANTO',
            '036' => 'FAUZIA NURUL AFINAH',
            '037' => 'ENDANG SITI MULYANI',
            '038' => 'RADEN MUHAMMAD ROBIANTARA PUTR',
            '039' => 'BALQIS CITRA SULISTYANA',
            '11' => 'DERRY NUR MUHAMMAD',
            '12' => 'FATTAH YASIN',
            'GR01' => 'AO GRAMINDO 01',
            'GR02' => 'AO GRAMINDO 02',
            'GR03' => 'AO GRAMINDO 03',
            'GR04' => 'AO GRAMINDO 04',
            'GR05' => 'AO GRAMINDO 05',
            'GR06' => 'AO BTB-GRAMIN 06',
            'GR07' => 'AO BTB-GRAMIN 07',
            'GR08' => 'AO BTB-GRAMIN 08',
            'GR09' => 'AO BTB-GRAMIN 09',
            'GR10' => 'AO BTB-GRAMIN 10',
            'GR11' => 'AO BTB-GRAMIN 11',
            'GR12' => 'AO BTB-GRAMIN 12',
            'GR13' => 'AO BTB-GRAMIN 13',
            'GR14' => 'AO BTB-GRAMIN 14',
            'GR15' => 'AO BTB-GRAMIN 15',
            'GR16' => 'AO BTB-GRAMIN 16',
            'GR17' => 'AO BTB-GRAMIN 17',
            'SDI' => 'SDI',
        ];
    }

    private function getAoDisplayName(?string $aoCodeOrName): string
    {
        $raw = (string)($aoCodeOrName ?? '');
        $raw = trim($raw);
        if ($raw === '') {
            return '-';
        }

        $aoMapping = $this->getAoMapping();
        if (isset($aoMapping[$raw])) {
            return $aoMapping[$raw];
        }

        $withoutLeadingZeros = ltrim($raw, '0');
        if ($withoutLeadingZeros !== '' && isset($aoMapping[$withoutLeadingZeros])) {
            return $aoMapping[$withoutLeadingZeros];
        }

        return 'AO ' . $raw;
    }

    /**
     * Show the dashboard with banking data
     */
    /**
     * Shell – respons instan dengan skeleton screen, tanpa query berat.
     * Data dashboard dimuat oleh browser via AJAX ke /dashboard/render.
     */
    public function index(Request $request)
    {
        // Jika ini request AJAX render, delegasikan ke renderContent()
        if ($request->boolean('_render')) {
            return $this->renderContent($request);
        }

        // Resolusi periode dengan 1 query ringan
        $filterMonth = $request->input('month');
        $filterYear  = $request->input('year');
        $range       = $this->normalizeDashboardRange($request->input('range', 'all'));
        $startDay    = $request->input('start_day', '');
        $endDay      = $request->input('end_day',   '');

        if (! $filterMonth || ! $filterYear) {
            $latest = Pembiayaan::query()
                ->select('period_year', 'period_month')
                ->whereNotNull('period_year')
                ->whereNotNull('period_month')
                ->orderByRaw('(period_year * 100 + period_month) DESC')
                ->first();

            $filterMonth = $filterMonth ?: str_pad((string)(int)($latest?->period_month ?: date('m')), 2, '0', STR_PAD_LEFT);
            $filterYear  = $filterYear  ?: (string)($latest?->period_year ?: date('Y'));

            // Redirect agar URL mempunyai parameter yang sudah di-resolve
            return redirect()->route('dashboard', array_merge(
                $request->query(),
                ['month' => $filterMonth, 'year' => $filterYear]
            ));
        }

        return view('dashboard-shell', compact('filterMonth', 'filterYear', 'range', 'startDay', 'endDay'));
    }

    /**
     * Render penuh – melakukan semua query berat, mengembalikan JSON
     * { html, styles, scripts } untuk di-inject oleh skeleton JS.
     */
    public function renderContent(Request $request)
    {
        // Get current user
        $user = Auth::user();

        // Get filter parameters
        $requestedMonth = $request->input('month');
        $requestedYear  = $request->input('year');

        $startDay = $request->input('start_day');
        $endDay   = $request->input('end_day');

        // Resolve latest available period first — used as fallback when no params are given
        $latestPeriod = Pembiayaan::query()
            ->select('period_year', 'period_month')
            ->whereNotNull('period_year')
            ->whereNotNull('period_month')
            ->orderByRaw('(period_year * 100 + period_month) DESC')
            ->first();

        $latestYear  = $latestPeriod?->period_year;
        $latestMonth = $latestPeriod?->period_month;

        // Default to latest available period (never fallback to current wall-clock date)
        $filterMonth = $request->input('month') ?: str_pad((string)(int)($latestMonth ?: date('m')), 2, '0', STR_PAD_LEFT);
        $filterYear  = $request->input('year')  ?: (string)($latestYear  ?: date('Y'));

        // Quick range filter
        $range = $this->normalizeDashboardRange($request->input('range', 'all'));

        $rangeMonthsMap = [
            '1d' => 1,
            '1w' => 1,
            '1m' => 1,
            '3m' => 3,
            '1y' => 12,
            'ytd' => null,
            'all' => null,
        ];

        $rangeMonths = $rangeMonthsMap[$range];

        // Normalize 'all' period selection to a concrete snapshot period.
        // The dashboard calculations rely on a single (month, year) snapshot in many places.
        $normalizedFromAll = ($filterYear === 'all' || $filterMonth === 'all');
        if ($normalizedFromAll) {
            if ($filterYear === 'all') {
                $filterYear = (string)($latestYear ?: date('Y'));
            }

            if ($filterMonth === 'all') {
                $maxMonthInYear = Pembiayaan::query()
                    ->when($filterYear !== 'all', function ($query) use ($filterYear) {
                        return $query->where('period_year', $filterYear);
                    })
                    ->max('period_month');

                $resolvedMonth = $maxMonthInYear ?: ($latestMonth ?: date('m'));
                $filterMonth = str_pad((string)(int)$resolvedMonth, 2, '0', STR_PAD_LEFT);
            } else {
                $filterMonth = str_pad((string)(int)$filterMonth, 2, '0', STR_PAD_LEFT);
            }

            // Day-range filters only make sense with a specific month/year.
            $startDay = null;
            $endDay = null;
        }

        // If the user asked for month/year=all, redirect to the resolved snapshot period.
        // This keeps query params consistent so all dashboard AJAX/modals use the same period.
        $requestedAll = ($requestedMonth === 'all' || $requestedYear === 'all');
        if ($requestedAll) {
            $queryParams = $request->query();
            $queryParams['month'] = $filterMonth;
            $queryParams['year'] = $filterYear;
            unset($queryParams['start_day'], $queryParams['end_day'], $queryParams['_render']);

            $qs = http_build_query($queryParams);
            // Always redirect to the dashboard shell page (not the render endpoint)
            $redirectUrl = route('dashboard') . ($qs ? ('?' . $qs) : '');

            if ($request->boolean('_render') || $request->ajax() || $request->expectsJson()) {
                return response()->json(['redirect' => $redirectUrl]);
            }
            return redirect()->to($redirectUrl);
        }

        // Build base query with combined filters
        $query = Pembiayaan::query();

        // Step 1: Filter by period_month dan period_year - apply only when user didn't select 'all'
        if ($filterMonth !== 'all') {
            $query->where('period_month', $filterMonth);
        }
        if ($filterYear !== 'all') {
            $query->where('period_year', $filterYear);
        }

        // Step 2: Filter by tanggal range (tgleff) - from explicit start/end day OR derived from range window
        [$dashboardStartDate, $dashboardEndDate] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$filterMonth, (string)$filterYear);
        $this->applyOptionalDateFilter($query, 'tgleff', $dashboardStartDate, $dashboardEndDate);

        // Segmentasi default (dipakai chart existing)
        $segmentasiData = $this->getSegmentasiData(
            $startDay,
            $endDay,
            $filterMonth,
            $filterYear,
            $dashboardStartDate,
            $dashboardEndDate,
            'segmentasi'
        );

        $segmentTableGroupBy = $this->normalizeSegmentTableGroupBy($request->input('group_by', 'segmentasi'));
        $segmentTableData = $segmentTableGroupBy === 'segmentasi'
            ? $segmentasiData
            : $this->getSegmentasiData(
                $startDay,
                $endDay,
                $filterMonth,
                $filterYear,
                $dashboardStartDate,
                $dashboardEndDate,
                $segmentTableGroupBy
            );

        // Semua agregasi utama lending diringkas ke 1 query untuk menekan waktu load awal.
        $lendingSummary = (clone $query)
            ->selectRaw('COALESCE(SUM(osmdlc), 0) as total_lending_modal')
            ->selectRaw('COALESCE(SUM(osmgnc), 0) as total_lending_margin')
            ->selectRaw('COALESCE(SUM(mdlawal), 0) as total_modal_awal')
            ->selectRaw('COALESCE(SUM(mgnawal), 0) as total_margin_awal')
            ->selectRaw('COUNT(*) as total_nasabah')
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru IN ('3', '4', '5') THEN osmdlc ELSE 0 END), 0) as total_npf")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru IN ('3', '4', '5') THEN tgkpok ELSE 0 END), 0) as total_tunggakan_pokok")
            ->selectRaw("SUM(CASE WHEN colbaru = '1' THEN 1 ELSE 0 END) as col1_count")
            ->selectRaw("SUM(CASE WHEN colbaru = '2' THEN 1 ELSE 0 END) as col2_count")
            ->selectRaw("SUM(CASE WHEN colbaru = '3' THEN 1 ELSE 0 END) as col3_count")
            ->selectRaw("SUM(CASE WHEN colbaru = '4' THEN 1 ELSE 0 END) as col4_count")
            ->selectRaw("SUM(CASE WHEN colbaru = '5' THEN 1 ELSE 0 END) as col5_count")
            ->first();

        // Total Outstanding Lending (Pokok Pembiayaan saja)
        $totalLendingModal = (float) ($lendingSummary->total_lending_modal ?? 0); // Outstanding Modal/Pokok
        $totalLendingMargin = (float) ($lendingSummary->total_lending_margin ?? 0); // Outstanding Margin

        // Total Modal Awal (Plafon awal pembiayaan)
        $totalModalAwal = (float) ($lendingSummary->total_modal_awal ?? 0);
        $totalMarginAwal = (float) ($lendingSummary->total_margin_awal ?? 0);

        // Count total nasabah/kontrak
        $totalNasabah = (int) ($lendingSummary->total_nasabah ?? 0);

        // Calculate NPF (kolektibilitas >= 3) - hanya pokok
        $totalNPF = (float) ($lendingSummary->total_npf ?? 0); // NPF hanya dari pokok
        $totalTunggakanPokok = (float) ($lendingSummary->total_tunggakan_pokok ?? 0); // Tunggakan pokok NPF
        $npfRatio = $totalLendingModal > 0 ? ($totalNPF / $totalLendingModal) * 100 : 0;

        // Funding data (Real dari tabel tabungans dan depositos)
        // Build query dengan filter yang sama seperti lending
        $tabunganQuery = Tabungan::query();
        if ($filterMonth !== 'all') {
            $tabunganQuery->where('period_month', $filterMonth);
        }
        if ($filterYear !== 'all') {
            $tabunganQuery->where('period_year', $filterYear);
        }

        $depositoQuery = Deposito::query();
        if ($filterMonth !== 'all') {
            $depositoQuery->where('period_month', $filterMonth);
        }
        if ($filterYear !== 'all') {
            $depositoQuery->where('period_year', $filterYear);
        }

        // Apply date window filter (tgltrnakh for tabungan, tglbuka for deposito)
        $this->applyOptionalDateFilter($tabunganQuery, 'tgltrnakh', $dashboardStartDate, $dashboardEndDate);
        $this->applyOptionalDateFilter($depositoQuery, 'tglbuka', $dashboardStartDate, $dashboardEndDate);

        $totalTabungan = (clone $tabunganQuery)->sum('sahirrp');
        $totalDeposito = (clone $depositoQuery)->sum('nomrp');
        $countTabungan = (clone $tabunganQuery)->count();
        $countDeposito = (clone $depositoQuery)->count();

        $totalFunding = $totalTabungan + $totalDeposito;

        // Funding growth & movement metrics only make sense for a specific period.
        $prevMonth = null;
        $prevYear = null;
        $prevTotalTabungan = 0;
        $prevTotalDeposito = 0;
        $prevTotalFunding = 0;
        $fundingGrowth = 0;

        $jumlahPencairan = 0;
        $totalPencairan = 0;
        $jumlahBaruDeposito = 0;
        $totalBaruDeposito = 0;

        $jumlahPencairanTabungan = 0;
        $totalPencairanTabungan = 0;
        $jumlahBaruTabungan = 0;
        $totalBaruTabungan = 0;

        $pencairanGrowth = 0;

        $hasSpecificFundingPeriod = (
            $filterMonth !== 'all'
            && $filterYear !== 'all'
            && ctype_digit((string)$filterMonth)
            && ctype_digit((string)$filterYear)
        );

        if ($hasSpecificFundingPeriod) {
            $currentMonthInt = (int)$filterMonth;
            $currentYearInt = (int)$filterYear;

            if ($currentMonthInt >= 1 && $currentMonthInt <= 12 && $currentYearInt > 0) {
                $prevMonthInt = $currentMonthInt === 1 ? 12 : ($currentMonthInt - 1);
                $prevYearInt = $currentMonthInt === 1 ? ($currentYearInt - 1) : $currentYearInt;

                $prevMonth = str_pad((string)$prevMonthInt, 2, '0', STR_PAD_LEFT);
                $prevYear = (string)$prevYearInt;

                $prevTotalTabungan = Tabungan::where('period_month', $prevMonth)
                    ->where('period_year', $prevYear)
                    ->sum('sahirrp');

                $prevTotalDeposito = Deposito::where('period_month', $prevMonth)
                    ->where('period_year', $prevYear)
                    ->sum('nomrp');

                $prevTotalFunding = $prevTotalTabungan + $prevTotalDeposito;
                $fundingGrowth = $prevTotalFunding > 0 ? (($totalFunding - $prevTotalFunding) / $prevTotalFunding) * 100 : 0;

                // Hitung Pencairan Deposito
                // Deposito yang ada di bulan lalu tapi tidak ada di bulan sekarang (sudah dicairkan)
                $depositoCairkan = DB::table('depositos as prev')
                    ->leftJoin('depositos as curr', function ($join) use ($filterMonth, $filterYear) {
                        $join->on('prev.nobilyet', '=', 'curr.nobilyet')
                            ->where('curr.period_month', $filterMonth)
                            ->where('curr.period_year', $filterYear);
                    })
                    ->where('prev.period_month', $prevMonth)
                    ->where('prev.period_year', $prevYear)
                    ->whereNull('curr.nobilyet') // Tidak ada di bulan sekarang
                    ->select(
                        DB::raw('COUNT(*) as jumlah_pencairan'),
                        DB::raw('SUM(prev.nomrp) as total_pencairan')
                    )
                    ->first();

                $jumlahPencairan = $depositoCairkan->jumlah_pencairan ?? 0;
                $totalPencairan = $depositoCairkan->total_pencairan ?? 0;

                // Deposito: hitung berapa yang baru buka deposito (ada di bulan ini tapi tidak ada di bulan lalu)
                $depositoBaru = DB::table('depositos as curr')
                    ->leftJoin('depositos as prev', function ($join) use ($prevMonth, $prevYear) {
                        $join->on('curr.nobilyet', '=', 'prev.nobilyet')
                            ->where('prev.period_month', $prevMonth)
                            ->where('prev.period_year', $prevYear);
                    })
                    ->where('curr.period_month', $filterMonth)
                    ->where('curr.period_year', $filterYear)
                    ->whereNull('prev.nobilyet')
                    ->select(
                        DB::raw('COUNT(*) as jumlah_baru_deposito'),
                        DB::raw('SUM(curr.nomrp) as total_baru_deposito')
                    )
                    ->first();

                $jumlahBaruDeposito = $depositoBaru->jumlah_baru_deposito ?? 0;
                $totalBaruDeposito = $depositoBaru->total_baru_deposito ?? 0;

                // Tabungan: compute per-account deltas between previous and current period to get
                // withdrawn (penarikan) and new deposit (nabung) counts and totals.
                try {
                    $pairsSql = "SELECT SUM(GREATEST(prev_bal - curr_bal, 0)) AS withdrawn_total,
                                         SUM(GREATEST(curr_bal - prev_bal, 0)) AS new_total,
                                         SUM(CASE WHEN prev_bal > curr_bal THEN 1 ELSE 0 END) AS withdrawn_count,
                                         SUM(CASE WHEN curr_bal > prev_bal THEN 1 ELSE 0 END) AS new_count
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
                        $prevMonth,
                        $prevYear,
                        $filterMonth,
                        $filterYear,
                        $prevMonth,
                        $prevYear,
                        $filterMonth,
                        $filterYear
                    ];

                    $pairResult = DB::selectOne($pairsSql, $bindings);

                    $jumlahPencairanTabungan = isset($pairResult->withdrawn_count) ? (int)$pairResult->withdrawn_count : 0;
                    $totalPencairanTabungan = isset($pairResult->withdrawn_total) ? (float)$pairResult->withdrawn_total : 0;

                    $jumlahBaruTabungan = isset($pairResult->new_count) ? (int)$pairResult->new_count : 0;
                    $totalBaruTabungan = isset($pairResult->new_total) ? (float)$pairResult->new_total : 0;
                } catch (\Exception $e) {
                    // Fallback to previous simple calculations if complex query fails
                    $tabunganCair = DB::table('tabungans as prev')
                        ->leftJoin('tabungans as curr', function ($join) use ($filterMonth, $filterYear) {
                            $join->on('prev.notab', '=', 'curr.notab')
                                ->where('curr.period_month', $filterMonth)
                                ->where('curr.period_year', $filterYear);
                        })
                        ->where('prev.period_month', $prevMonth)
                        ->where('prev.period_year', $prevYear)
                        ->whereNull('curr.notab')
                        ->select(
                            DB::raw('COUNT(*) as jumlah_pencairan_tabungan'),
                            DB::raw('SUM(prev.sahirrp) as total_pencairan_tabungan')
                        )
                        ->first();

                    $jumlahPencairanTabungan = $tabunganCair->jumlah_pencairan_tabungan ?? 0;
                    $totalPencairanTabungan = $tabunganCair->total_pencairan_tabungan ?? 0;

                    $tabunganBaru = DB::table('tabungans as curr')
                        ->leftJoin('tabungans as prev', function ($join) use ($prevMonth, $prevYear) {
                            $join->on('curr.notab', '=', 'prev.notab')
                                ->where('prev.period_month', $prevMonth)
                                ->where('prev.period_year', $prevYear);
                        })
                        ->where('curr.period_month', $filterMonth)
                        ->where('curr.period_year', $filterYear)
                        ->whereNull('prev.notab')
                        ->select(
                            DB::raw('COUNT(*) as jumlah_baru_tabungan'),
                            DB::raw('SUM(curr.sahirrp) as total_baru_tabungan')
                        )
                        ->first();

                    $jumlahBaruTabungan = $tabunganBaru->jumlah_baru_tabungan ?? 0;
                    $totalBaruTabungan = $tabunganBaru->total_baru_tabungan ?? 0;
                }

                // Calculate pencairan growth from previous month
                $prevPrevMonthInt = $prevMonthInt === 1 ? 12 : ($prevMonthInt - 1);
                $prevPrevYearInt = $prevMonthInt === 1 ? ($prevYearInt - 1) : $prevYearInt;
                $prevPrevMonth = str_pad((string)$prevPrevMonthInt, 2, '0', STR_PAD_LEFT);
                $prevPrevYear = (string)$prevPrevYearInt;

                $prevPencairan = DB::table('depositos as prev_prev')
                    ->leftJoin('depositos as prev_curr', function ($join) use ($prevMonth, $prevYear) {
                        $join->on('prev_prev.nobilyet', '=', 'prev_curr.nobilyet')
                            ->where('prev_curr.period_month', $prevMonth)
                            ->where('prev_curr.period_year', $prevYear);
                    })
                    ->where('prev_prev.period_month', $prevPrevMonth)
                    ->where('prev_prev.period_year', $prevPrevYear)
                    ->whereNull('prev_curr.nobilyet')
                    ->sum('prev_prev.nomrp');

                $pencairanGrowth = $prevPencairan > 0 ? (($totalPencairan - $prevPencairan) / $prevPencairan) * 100 : 0;
            }
        }

        // Calculate percentage composition
        $tabunganPct = $totalFunding > 0 ? round(($totalTabungan / $totalFunding) * 100, 1) : 0;
        $depositoPct = $totalFunding > 0 ? round(($totalDeposito / $totalFunding) * 100, 1) : 0;

        $funding = [
            'total' => $totalFunding,
            'growth' => round($fundingGrowth, 2),
            'composition' => [
                'Tabungan' => $tabunganPct,
                'Deposito' => $depositoPct
            ],
            'nominal' => [
                'Tabungan' => $totalTabungan,
                'Deposito' => $totalDeposito
            ],
            'count' => [
                'Tabungan' => $countTabungan,
                'Deposito' => $countDeposito,
                'Total' => $countTabungan + $countDeposito
            ],
            'pencairan' => [
                'jumlah' => $jumlahPencairan,
                'total' => $totalPencairan,
                'growth' => round($pencairanGrowth, 2)
            ]
        ];

        // Tabungan-specific growth (compare to previous month)
        try {
            $tabunganDelta = $totalTabungan - $prevTotalTabungan;
            $tabunganGrowthPercent = $prevTotalTabungan > 0 ? (($tabunganDelta) / $prevTotalTabungan) * 100 : 0;
            $funding['tabungan_growth_percent'] = round($tabunganGrowthPercent, 2);
            $funding['tabungan_growth_amount'] = $tabunganDelta;
        } catch (\Exception $e) {
            $funding['tabungan_growth_percent'] = 0;
            $funding['tabungan_growth_amount'] = 0;
        }

        // Attach tabungan-specific counts/totals for UI
        $funding['pencairan_tabungan'] = [
            'jumlah' => $jumlahPencairanTabungan,
            'total' => $totalPencairanTabungan
        ];

        $funding['nabung'] = [
            'jumlah' => $jumlahBaruTabungan,
            'total' => $totalBaruTabungan
        ];

        // Attach deposito-specific counts/totals for UI
        $funding['pencairan_deposito'] = [
            'jumlah' => $jumlahPencairan,
            'total' => $totalPencairan
        ];

        $funding['buka_deposito'] = [
            'jumlah' => $jumlahBaruDeposito,
            'total' => $totalBaruDeposito
        ];

        // Deposito-specific growth (compare to previous month)
        try {
            $depositoDelta = $totalDeposito - $prevTotalDeposito;
            $depositoGrowthPercent = $prevTotalDeposito > 0 ? (($depositoDelta) / $prevTotalDeposito) * 100 : 0;
            $funding['deposito_growth_percent'] = round($depositoGrowthPercent, 2);
            $funding['deposito_growth_amount'] = $depositoDelta;
        } catch (\Exception $e) {
            $funding['deposito_growth_percent'] = 0;
            $funding['deposito_growth_amount'] = 0;
        }

        // Funding Detail Table - Current Period (dengan filter)
        $fundingDetails = [
            'tabungan' => (clone $tabunganQuery)
                ->orderBy('sahirrp', 'desc')
                ->limit(10)
                ->get(),
            'deposito' => (clone $depositoQuery)
                ->orderBy('nomrp', 'desc')
                ->limit(10)
                ->get()
        ];

        // Get last updated dates for each data type
        $lastUpdated = [
            'pembiayaan' => Pembiayaan::where('period_month', $filterMonth)
                ->where('period_year', $filterYear)
                ->max('updated_at'),
            'tabungan' => Tabungan::where('period_month', $filterMonth)
                ->where('period_year', $filterYear)
                ->max('updated_at'),
            'deposito' => Deposito::where('period_month', $filterMonth)
                ->where('period_year', $filterYear)
                ->max('updated_at'),
            'linkage' => Linkage::where('period_month', $filterMonth)
                ->where('period_year', $filterYear)
                ->max('updated_at'),
            'financial_highlight' => \DB::table('financial_highlights')
                ->where('period_month', $filterMonth)
                ->where('period_year', $filterYear)
                ->max('updated_at')
        ];

        // Nasabah dengan Total Saldo Funding Terbesar (Tabungan + Deposito)
        // Gabungkan semua nasabah dari tabungan dan deposito (pakai query builder agar aman untuk month/year = all)
        $tabunganFundingQuery = DB::table('tabungans')
            ->selectRaw('nocif, fnama as nama, sahirrp as saldo, "Tabungan" as jenis, tgltrnakh as tanggal')
            ->when($filterMonth !== 'all', function ($query) use ($filterMonth) {
                return $query->where('period_month', $filterMonth);
            })
            ->when($filterYear !== 'all', function ($query) use ($filterYear) {
                return $query->where('period_year', $filterYear);
            });

        $depositoFundingQuery = DB::table('depositos')
            ->selectRaw('nocif, nama, nomrp as saldo, "Deposito" as jenis, tglbuka as tanggal')
            ->when($filterMonth !== 'all', function ($query) use ($filterMonth) {
                return $query->where('period_month', $filterMonth);
            })
            ->when($filterYear !== 'all', function ($query) use ($filterYear) {
                return $query->where('period_year', $filterYear);
            });

        $hasSpecificFundingPeriodForDates = (
            $filterMonth !== 'all'
            && $filterYear !== 'all'
            && ctype_digit((string)$filterMonth)
            && ctype_digit((string)$filterYear)
        );

        if ($hasSpecificFundingPeriodForDates && ($dashboardStartDate || $dashboardEndDate)) {
            $this->applyOptionalDateFilter($tabunganFundingQuery, 'tgltrnakh', $dashboardStartDate, $dashboardEndDate);
            $this->applyOptionalDateFilter($depositoFundingQuery, 'tglbuka', $dashboardStartDate, $dashboardEndDate);
        }

        $combinedFundingQuery = $tabunganFundingQuery->unionAll($depositoFundingQuery);

        $allNasabahFunding = DB::query()
            ->fromSub($combinedFundingQuery, 'combined')
            ->select(
                'nocif',
                DB::raw('MAX(nama) as nama'),
                DB::raw('SUM(CASE WHEN jenis = "Tabungan" THEN saldo ELSE 0 END) as total_tabungan'),
                DB::raw('SUM(CASE WHEN jenis = "Deposito" THEN saldo ELSE 0 END) as total_deposito'),
                DB::raw('COUNT(CASE WHEN jenis = "Tabungan" THEN 1 END) as jumlah_tabungan'),
                DB::raw('COUNT(CASE WHEN jenis = "Deposito" THEN 1 END) as jumlah_deposito'),
                DB::raw('SUM(saldo) as total_funding')
            )
            ->groupBy('nocif')
            ->orderByDesc('total_funding')
            ->limit(50)
            ->get();

        $nasabahBothFunding = $allNasabahFunding;

        // Top 50 nasabah dengan pinjaman terbesar
        $nasabahLending = Pembiayaan::where('period_month', $filterMonth)
            ->where('period_year', $filterYear)
            ->when($dashboardStartDate, function ($query) use ($dashboardStartDate) {
                return $query->whereDate('tgleff', '>=', $dashboardStartDate);
            })
            ->when($dashboardEndDate, function ($query) use ($dashboardEndDate) {
                return $query->whereDate('tgleff', '<=', $dashboardEndDate);
            })
            ->select(
                'nocif',
                DB::raw('MAX(nama) as nama'),
                DB::raw('COUNT(*) as jumlah_pinjaman'),
                DB::raw('SUM(mdlawal) as total_pinjaman'),
                DB::raw('SUM(mgnawal) as total_bunga'),
                DB::raw('SUM(osmdlc + osmgnc) as total_angsuran'),
                DB::raw('SUM(osmdlc) as total_outstanding'),
                DB::raw('MAX(colbaru) as kolektibilitas')
            )
            ->groupBy('nocif')
            ->orderByRaw('SUM(osmdlc) desc')
            ->limit(50)
            ->get();

        // Lending data (Real dari CSV)
        $lending = [
            'total' => $totalLendingModal, // Outstanding POKOK saja
            'modal' => $totalLendingModal, // Outstanding Modal
            'margin' => $totalLendingMargin, // Outstanding Margin
            'plafon_awal' => $totalModalAwal, // Plafon awal
            'rate_flat' => 11.5, // Dummy - bisa dihitung dari data jika ada
            'rate_eff' => 19.9, // Dummy
            'nasabah' => $totalNasabah
        ];

        // NPF (Non-Performing Financing)
        $npf = [
            'total' => $totalNPF,
            'tunggakan_pokok' => $totalTunggakanPokok,
            'ratio' => round($npfRatio, 2)
        ];

        // Monthly trends should show period-based data, not tgleff
        $monthlyDataQuery = Pembiayaan::select(
            'period_year',
            'period_month',
            DB::raw('SUM(mdlawal) as plafon'),
            DB::raw('SUM(osmdlc) as outstanding'),
            DB::raw('COUNT(DISTINCT nokontrak) as funding_count'),
            DB::raw('COUNT(DISTINCT CASE WHEN osmdlc > 0 THEN nokontrak END) as lending_count')
        )
            ->whereNotNull('period_year')
            ->whereNotNull('period_month')
            ->groupBy('period_year', 'period_month');

        $trendRangeLabel = null;

        if ($range === 'ytd' || $rangeMonths !== null) {
            // Determine end period for the range
            $endYear = null;
            $endMonth = null;

            if ($range === 'ytd') {
                // YTD follows selected year even if month=all
                if ($filterYear !== 'all') {
                    $endYear = (int)$filterYear;
                } elseif ($latestYear) {
                    $endYear = (int)$latestYear;
                }

                if ($filterMonth !== 'all') {
                    $endMonth = (int)$filterMonth;
                } elseif ($endYear) {
                    $maxMonthInYear = Pembiayaan::query()
                        ->where('period_year', $endYear)
                        ->whereNotNull('period_month')
                        ->orderBy('period_month', 'desc')
                        ->value('period_month');

                    $endMonth = $maxMonthInYear ? (int)$maxMonthInYear : 12;
                }
            } else {
                if ($filterYear !== 'all' && $filterMonth !== 'all') {
                    $endYear = (int)$filterYear;
                    $endMonth = (int)$filterMonth;
                } elseif ($latestYear && $latestMonth) {
                    $endYear = (int)$latestYear;
                    $endMonth = (int)$latestMonth;
                }
            }

            if ($endYear && $endMonth) {
                $endDate = Carbon::create($endYear, $endMonth, 1);
                if ($range === 'ytd') {
                    $startDate = Carbon::create($endYear, 1, 1);
                } else {
                    $startDate = (clone $endDate)->subMonths($rangeMonths - 1);
                }

                $startKey = (int)$startDate->format('Ym');
                $endKey = (int)$endDate->format('Ym');

                $monthlyDataQuery->whereRaw(
                    '(period_year * 100 + period_month) BETWEEN ? AND ?',
                    [$startKey, $endKey]
                );

                $monthNames = [
                    1 => 'Jan',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'Mei',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Agt',
                    9 => 'Sep',
                    10 => 'Okt',
                    11 => 'Nov',
                    12 => 'Des',
                ];

                $trendRangeLabel = ($monthNames[(int)$startDate->format('n')] ?? $startDate->format('m')) . ' ' . $startDate->format('Y')
                    . ' - ' .
                    ($monthNames[(int)$endDate->format('n')] ?? $endDate->format('m')) . ' ' . $endDate->format('Y');
            }
        } else {
            $trendRangeLabel = 'Semua periode';
        }

        $monthlyData = $monthlyDataQuery
            ->orderByRaw('(period_year * 100 + period_month) ASC')
            ->get();

        $monthlyTrends = [
            'labels' => $monthlyData->map(function ($item) {
                $monthNames = [
                    '01' => 'Jan',
                    '02' => 'Feb',
                    '03' => 'Mar',
                    '04' => 'Apr',
                    '05' => 'Mei',
                    '06' => 'Jun',
                    '07' => 'Jul',
                    '08' => 'Agt',
                    '09' => 'Sep',
                    '10' => 'Okt',
                    '11' => 'Nov',
                    '12' => 'Des'
                ];
                $monthKey = str_pad((string)(int)$item->period_month, 2, '0', STR_PAD_LEFT);
                return ($monthNames[$monthKey] ?? $monthKey) . ' ' . $item->period_year;
            })->values()->toArray(),
            'funding' => $monthlyData->map(function ($item) {
                return round($item->plafon / 1000000000, 2); // Konversi ke miliar
            })->values()->toArray(),
            'lending' => $monthlyData->map(function ($item) {
                return round($item->outstanding / 1000000000, 2); // Konversi ke miliar
            })->values()->toArray(),
            'funding_count' => $monthlyData->map(function ($item) {
                return (int) ($item->funding_count ?? 0);
            })->values()->toArray(),
            'lending_count' => $monthlyData->map(function ($item) {
                return (int) ($item->lending_count ?? 0);
            })->values()->toArray()
        ];

        // Compute pelunasan cepat per month for monthly trend chart
        $pelunasanCepatByMonth = [];
        $pelunasanCepatCountByMonth = [];
        foreach ($monthlyData as $trendItem) {
            $tMonth = (int)$trendItem->period_month;
            $tYear  = (int)$trendItem->period_year;
            $tMonthStr = str_pad($tMonth, 2, '0', STR_PAD_LEFT);
            $tPrevMonth = $tMonth - 1;
            $tPrevYear  = $tYear;
            if ($tPrevMonth < 1) {
                $tPrevMonth = 12;
                $tPrevYear = $tYear - 1;
            }
            $tPrevMonthStr = str_pad($tPrevMonth, 2, '0', STR_PAD_LEFT);

            $tKontrakLalu = Pembiayaan::where('period_month', $tPrevMonthStr)
                ->where('period_year', $tPrevYear)
                ->selectRaw('DISTINCT nokontrak')
                ->pluck('nokontrak')
                ->toArray();
            $tKontrakIni = Pembiayaan::where('period_month', $tMonthStr)
                ->where('period_year', $tYear)
                ->selectRaw('DISTINCT nokontrak')
                ->pluck('nokontrak')
                ->toArray();
            $tHilang = array_diff($tKontrakLalu, $tKontrakIni);

            if (!empty($tHilang)) {
                $pelunasanCepatBaseQuery = Pembiayaan::where('period_month', $tPrevMonthStr)
                    ->where('period_year', $tPrevYear)
                    ->whereIn('nokontrak', $tHilang)
                    ->whereRaw('angs_ke < jw')
                    ->where('jw', '>', 0)
                    ->select('nokontrak', 'mdlawal');

                $pelunasanCepatContracts = $pelunasanCepatBaseQuery
                    ->get()
                    ->unique('nokontrak')
                    ->values();

                $tNominal = $pelunasanCepatContracts->sum('mdlawal');
                $pelunasanCepatByMonth[] = round(($tNominal ?? 0) / 1000000000, 2);
                $pelunasanCepatCountByMonth[] = $pelunasanCepatContracts->count();
            } else {
                $pelunasanCepatByMonth[] = 0;
                $pelunasanCepatCountByMonth[] = 0;
            }
        }
        $monthlyTrends['pelunasan_cepat'] = $pelunasanCepatByMonth;
        $monthlyTrends['pelunasan_cepat_count'] = $pelunasanCepatCountByMonth;

        // If no data, use default
        if (empty($monthlyTrends['labels'])) {
            $monthlyTrends = [
                'labels'          => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                'funding'         => [0, 0, 0, 0, 0, 0],
                'lending'         => [0, 0, 0, 0, 0, 0],
                'pelunasan_cepat' => [0, 0, 0, 0, 0, 0],
                'funding_count' => [0, 0, 0, 0, 0, 0],
                'lending_count' => [0, 0, 0, 0, 0, 0],
                'pelunasan_cepat_count' => [0, 0, 0, 0, 0, 0],
            ];
        }

        // NPF distribution by segmentasi (Kol 3, 4, 5)
        $npfDistribution = [
            'labels' => [],
            'values' => []
        ];

        // Ambil data NPF per segmentasi dari tabel segmentasi
        foreach ($segmentasiData as $segment) {
            if (!$segment['is_total'] && $segment['type']) {
                // Hitung total NPF (Kol 3 + Kol 4 + Kol 5) untuk segmen ini
                $npfAmount = ($segment['col3_sum'] ?? 0) + ($segment['col4_sum'] ?? 0) + ($segment['col5_sum'] ?? 0);

                // Hanya masukkan jika ada NPF
                if ($npfAmount > 0) {
                    $npfDistribution['labels'][] = $segment['type'];
                    $npfDistribution['values'][] = round($npfAmount / 1000000000, 2); // Konversi ke miliar
                }
            }
        }

        // Top 5 nasabah penyumbang NPF terbesar
        $topNpfQuery = (clone $query)->whereIn('colbaru', ['3', '4', '5'])
            ->select('nama', 'nokontrak', 'osmdlc', 'colbaru')
            ->orderBy('osmdlc', 'desc')
            ->limit(5);

        $topNpfContributors = $topNpfQuery->get()
            ->map(function ($item) {
                return [
                    'nama' => $item->nama,
                    'nokontrak' => $item->nokontrak,
                    'osmdlc' => $item->osmdlc,
                    'colbaru' => $item->colbaru,
                    'colbaru_label' => $item->colbaru == '3' ? 'Kurang Lancar' : ($item->colbaru == '4' ? 'Diragukan' : 'Macet')
                ];
            });

        // Kolektibilitas Distribution (All) pakai hasil agregasi awal.
        $col1 = (int) ($lendingSummary->col1_count ?? 0);
        $col2 = (int) ($lendingSummary->col2_count ?? 0);
        $col3Count = (int) ($lendingSummary->col3_count ?? 0);
        $col4Count = (int) ($lendingSummary->col4_count ?? 0);
        $col5Count = (int) ($lendingSummary->col5_count ?? 0);

        $collectibilityStats = [
            ['label' => 'Lancar (Kol 1)', 'count' => $col1, 'percentage' => $totalNasabah > 0 ? round(($col1 / $totalNasabah) * 100, 1) : 0, 'color' => 'success'],
            ['label' => 'DPK (Kol 2)', 'count' => $col2, 'percentage' => $totalNasabah > 0 ? round(($col2 / $totalNasabah) * 100, 1) : 0, 'color' => 'info'],
            ['label' => 'Kurang Lancar (Kol 3)', 'count' => $col3Count, 'percentage' => $totalNasabah > 0 ? round(($col3Count / $totalNasabah) * 100, 1) : 0, 'color' => 'warning'],
            ['label' => 'Diragukan (Kol 4)', 'count' => $col4Count, 'percentage' => $totalNasabah > 0 ? round(($col4Count / $totalNasabah) * 100, 1) : 0, 'color' => 'danger'],
            ['label' => 'Macet (Kol 5)', 'count' => $col5Count, 'percentage' => $totalNasabah > 0 ? round(($col5Count / $totalNasabah) * 100, 1) : 0, 'color' => 'dark'],
        ];

        // Top 5 Produk Pembiayaan
        $productMapping = [
            '55' => 'Musyarakah',
            '50' => 'Murabahah',
            '56' => 'MMQ',
            '88' => 'Isthisna',
            '86' => 'Multijasa Piutang',
        ];

        $topProductsQuery = (clone $query)->select('kdprd', DB::raw('COUNT(*) as total_kontrak'), DB::raw('SUM(osmdlc) as total_outstanding'))
            ->whereNotNull('kdprd')
            ->where('kdprd', '!=', '')
            ->groupBy('kdprd')
            ->orderBy('total_outstanding', 'desc')
            ->limit(5);

        $topProducts = $topProductsQuery->get()
            ->map(function ($item) use ($productMapping) {
                $item->nama_produk = $productMapping[$item->kdprd] ?? 'Produk ' . $item->kdprd;
                return $item;
            });

        // Top 5 Area/Cabang (Account Officer)
        $aoMapping = [
            '017' => 'AGUS SETIAWAN',
            '018' => 'ADITYA FATAHILLAH MUHARAM',
            '020' => 'TAUFAN NUGRAHA',
            '021' => 'SURYA SEPTIANNANDA',
            '022' => 'FACHRI EKA PUTRA',
            '023' => 'RIZKI NIRMALA',
            '024' => 'GUNANTO',
            '025' => 'SANDI M ILHAM',
            '026' => 'FEISHAL JUAENI',
            '027' => 'ZAINAL ARIFIN',
            '028' => 'RIVI NUGRAHA',
            '029' => 'YOHAN EKA PUTRA',
            '030' => 'YUSRON WIJAYA',
            '031' => 'SABIQ KHUSNAIDI',
            '032' => 'YUNITA HERDIANA',
            '033' => 'YUSI IRMAYANTI',
            '034' => 'LARIZA AFRIANTI',
            '035' => 'DEVI NURLIANTO',
            '036' => 'FAUZIA NURUL AFINAH',
            '037' => 'ENDANG SITI MULYANI',
            '038' => 'RADEN MUHAMMAD ROBIANTARA PUTR',
            '039' => 'BALQIS CITRA SULISTYANA',
            '11' => 'DERRY NUR MUHAMMAD',
            '12' => 'FATTAH YASIN',
            'GR01' => 'AO GRAMINDO 01',
            'GR02' => 'AO GRAMINDO 02',
            'GR03' => 'AO GRAMINDO 03',
            'GR04' => 'AO GRAMINDO 04',
            'GR05' => 'AO GRAMINDO 05',
            'GR06' => 'AO BTB-GRAMIN 06',
            'GR07' => 'AO BTB-GRAMIN 07',
            'GR08' => 'AO BTB-GRAMIN 08',
            'GR09' => 'AO BTB-GRAMIN 09',
            'GR10' => 'AO BTB-GRAMIN 10',
            'GR11' => 'AO BTB-GRAMIN 11',
            'GR12' => 'AO BTB-GRAMIN 12',
            'GR13' => 'AO BTB-GRAMIN 13',
            'GR14' => 'AO BTB-GRAMIN 14',
            'GR15' => 'AO BTB-GRAMIN 15',
            'GR16' => 'AO BTB-GRAMIN 16',
            'GR17' => 'AO BTB-GRAMIN 17',
            'SDI' => 'SDI',
        ];

        $topAreasQuery = (clone $query)->select('kdaoh', DB::raw('COUNT(*) as total_kontrak'), DB::raw('SUM(osmdlc) as total_outstanding'))
            ->whereNotNull('kdaoh')
            ->where('kdaoh', '!=', '')
            ->groupBy('kdaoh')
            ->orderBy('total_outstanding', 'desc')
            ->limit(5);

        $topAreas = $topAreasQuery->get()
            ->map(function ($item) use ($aoMapping) {
                $item->nama_ao = $aoMapping[$item->kdaoh] ?? 'AO ' . $item->kdaoh;
                return $item;
            });

        // Segmentasi distribution for pie chart - group by main categories only
        $segmentasiDistribution = [
            'labels' => [],
            'values' => []
        ];

        // Group data by category for chart
        $categoryTotals = [];
        foreach ($segmentasiData as $segment) {
            if (!$segment['is_total'] && $segment['outstanding'] > 0) {
                $category = $segment['category'];
                if (!isset($categoryTotals[$category])) {
                    $categoryTotals[$category] = 0;
                }
                $categoryTotals[$category] += $segment['outstanding'];
            }
        }

        // Add to chart data
        foreach ($categoryTotals as $category => $totalOutstanding) {
            $segmentasiDistribution['labels'][] = $category;
            $segmentasiDistribution['values'][] = round($totalOutstanding / 1000000000, 2); // Konversi ke miliar
        }

        // Kolektibilitas Distribution for pie chart
        $kolektibilitasDistribution = [
            'labels' => [],
            'series' => []
        ];

        // Get actual data for collectibility chart
        $collectibilityChartData = (clone $query)
            ->select('colbaru', DB::raw('SUM(osmdlc) as total_outstanding'))
            ->whereNotNull('colbaru')
            ->groupBy('colbaru')
            ->orderBy('colbaru')
            ->get();

        foreach ($collectibilityChartData as $col) {
            $kolektibilitasDistribution['labels'][] = 'Kol ' . $col->colbaru;
            $kolektibilitasDistribution['series'][] = round($col->total_outstanding / 1000000000, 2);
        }

        // Kolektibilitas Categories with Month-over-Month Comparison
        $kolektibilitasComparison = [];
        $prevMonth = $filterMonth - 1;
        $prevYear = $filterYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $filterYear - 1;
        }

        // Current month data
        $currentKolektibilitas = (clone $query)
            ->select(
                'colbaru',
                DB::raw('COUNT(*) as jumlah_nasabah'),
                DB::raw('SUM(osmdlc) as total_outstanding')
            )
            ->whereNotNull('colbaru')
            ->groupBy('colbaru')
            ->orderBy('colbaru')
            ->get()
            ->keyBy('colbaru');

        // Previous month data
        $prevKolektibilitas = Pembiayaan::where('period_month', str_pad($prevMonth, 2, '0', STR_PAD_LEFT))
            ->where('period_year', $prevYear)
            ->select(
                'colbaru',
                DB::raw('COUNT(*) as jumlah_nasabah'),
                DB::raw('SUM(osmdlc) as total_outstanding')
            )
            ->whereNotNull('colbaru')
            ->groupBy('colbaru')
            ->orderBy('colbaru')
            ->get()
            ->keyBy('colbaru');

        // Build comparison data for categories 1-5
        for ($i = 1; $i <= 5; $i++) {
            $current = $currentKolektibilitas->get($i);
            $previous = $prevKolektibilitas->get($i);

            $currentJumlah = $current ? $current->jumlah_nasabah : 0;
            $currentNominal = $current ? $current->total_outstanding : 0;
            $prevJumlah = $previous ? $previous->jumlah_nasabah : 0;
            $prevNominal = $previous ? $previous->total_outstanding : 0;

            $kolektibilitasComparison[$i] = [
                'kategori' => $i,
                'nama_kategori' => ['Lancar', 'Dalam Perhatian Khusus', 'Kurang Lancar', 'Diragukan', 'Macet'][$i - 1],
                'current_jumlah' => $currentJumlah,
                'current_nominal' => $currentNominal,
                'prev_jumlah' => $prevJumlah,
                'prev_nominal' => $prevNominal,
                'jumlah_growth' => $prevJumlah > 0 ? (($currentJumlah - $prevJumlah) / $prevJumlah) * 100 : ($currentJumlah > 0 ? 100 : 0),
                'nominal_growth' => $prevNominal > 0 ? (($currentNominal - $prevNominal) / $prevNominal) * 100 : ($currentNominal > 0 ? 100 : 0),
            ];
        }

        // Top Products Chart Data (for bar chart)
        $topProductsChart = [
            'categories' => $topProducts->pluck('nama_produk')->toArray(),
            'data' => $topProducts->map(function ($item) {
                return round($item->total_outstanding / 1000000000, 2);
            })->toArray()
        ];

        // Portfolio Summary
        $portfolioSummary = [
            'total_kontrak' => $totalNasabah,
            'total_outstanding' => $totalLendingModal,
            'total_plafon' => $totalModalAwal,
            'utilisasi' => $totalModalAwal > 0 ? round(($totalLendingModal / $totalModalAwal) * 100, 2) : 0,
            'avg_outstanding' => $totalNasabah > 0 ? round($totalLendingModal / $totalNasabah, 2) : 0
        ];

        // Sebaran Nasabah per Kecamatan
        $kecamatanData = (clone $query)
            ->select(
                'kecamatan',
                'kota',
                DB::raw('COUNT(*) as total_nasabah'),
                DB::raw('SUM(osmdlc) as total_outstanding')
            )
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->groupBy('kecamatan', 'kota')
            ->orderBy('total_nasabah', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'kecamatan' => $item->kecamatan,
                    'kota' => $item->kota ?? '',
                    'total_nasabah' => $item->total_nasabah,
                    'total_outstanding' => $item->total_outstanding,
                    'outstanding_miliar' => round($item->total_outstanding / 1000000000, 2)
                ];
            });

        // Top AO Performance (All AOs)
        // NOTE: In many datasets, pembiayaan.nmao is empty while kdaoh is filled.
        // Use kdaoh as the grouping key, then map to a display name.
        $topAOQuery = (clone $query)
            ->select(
                'kdaoh',
                DB::raw('COUNT(*) as total_nasabah'),
                DB::raw('SUM(osmdlc) as total_outstanding'),
                DB::raw('SUM(mdlawal) as total_plafon'),
                DB::raw('SUM(CASE WHEN colbaru >= 3 THEN osmdlc ELSE 0 END) as total_npf'),
                DB::raw('COUNT(CASE WHEN colbaru >= 3 THEN 1 END) as jumlah_npf')
            )
            ->whereNotNull('kdaoh')
            ->where('kdaoh', '!=', '')
            ->groupBy('kdaoh')
            ->orderBy('total_outstanding', 'desc');

        try {
            $topAORawSql = $topAOQuery->toSql();
            $topAOBindings = $topAOQuery->getBindings();
        } catch (\Exception $e) {
            $topAORawSql = null;
            $topAOBindings = [];
        }

        $topAOCollection = $topAOQuery->get();

        // Log SQL and counts for debugging when card is empty
        try {
            \Log::debug('Top AO query', ['sql' => $topAORawSql, 'bindings' => $topAOBindings, 'count' => $topAOCollection->count(), 'sample' => $topAOCollection->first()]);
        } catch (\Exception $e) {
            // ignore logging errors
        }

        $topAOData = $topAOCollection->map(function ($item) {
            $npfRatio = $item->total_outstanding > 0
                ? ($item->total_npf / $item->total_outstanding) * 100
                : 0;

            $aoKey = $item->kdaoh ?? null;
            $displayName = $this->getAoDisplayName($aoKey);

            return [
                'ao_key' => $aoKey,
                'nmao' => $displayName,
                'total_nasabah' => $item->total_nasabah,
                'total_outstanding' => $item->total_outstanding,
                'total_plafon' => $item->total_plafon,
                'total_npf' => $item->total_npf,
                'jumlah_npf' => $item->jumlah_npf,
                'npf_ratio' => round($npfRatio, 2),
                'outstanding_miliar' => round($item->total_outstanding / 1000000000, 2)
            ];
        });

        // AO Funding Performance - Only Depositos
        $currentDate = now()->format('Y-m-d');

        // Calculate pencairan deposito per AO (deposito yang hilang dari bulan sebelumnya)
        $prevMonth = $filterMonth == '01' ? '12' : str_pad($filterMonth - 1, 2, '0', STR_PAD_LEFT);
        $prevYear = $filterMonth == '01' ? $filterYear - 1 : $filterYear;

        $pencairanByAO = DB::table('depositos as prev')
            ->leftJoin('depositos as curr', function ($join) use ($filterMonth, $filterYear) {
                $join->on('prev.nobilyet', '=', 'curr.nobilyet')
                    ->where('curr.period_month', $filterMonth)
                    ->where('curr.period_year', $filterYear);
            })
            ->where('prev.period_month', $prevMonth)
            ->where('prev.period_year', $prevYear)
            ->whereNull('curr.nobilyet')
            ->selectRaw('prev.kodeaoh, COUNT(*) as total_cairkan, SUM(prev.nomrp) as nominal_cairkan')
            ->whereNotNull('prev.kodeaoh')
            ->where('prev.kodeaoh', '!=', '')
            ->groupBy('prev.kodeaoh')
            ->get()
            ->keyBy('kodeaoh');

        // Query depositos grouped by AO with categorization
        $depositoByAO = DB::table('depositos')
            ->selectRaw("
                kodeaoh,
                SUM(CASE WHEN kdprd = '31' THEN 1 ELSE 0 END) as total_deposito,
                SUM(CASE WHEN kdprd = '41' THEN 1 ELSE 0 END) as total_abp,
                SUM(CASE WHEN kdprd = '31' THEN nomrp ELSE 0 END) as nominal_deposito,
                SUM(CASE WHEN kdprd = '41' THEN nomrp ELSE 0 END) as nominal_abp
            ")
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear)
            ->when($dashboardStartDate, function ($q) use ($dashboardStartDate) {
                return $q->whereDate('tglbuka', '>=', $dashboardStartDate);
            })
            ->when($dashboardEndDate, function ($q) use ($dashboardEndDate) {
                return $q->whereDate('tglbuka', '<=', $dashboardEndDate);
            })
            ->where('stsrec', 'A')
            ->whereNotNull('kodeaoh')
            ->where('kodeaoh', '!=', '')
            ->groupBy('kodeaoh')
            ->get()
            ->keyBy('kodeaoh');

        // AO mapping
        $aoMapping = $this->getAoMapping();

        // Build aoFundingData with deposito categorization
        $aoFundingData = collect();
        foreach ($depositoByAO as $kodeaoh => $data) {
            $aoName = $aoMapping[$kodeaoh] ?? ($aoMapping[ltrim((string)$kodeaoh, '0')] ?? $kodeaoh);
            $totalDeposits = ($data->total_deposito ?? 0) + ($data->total_abp ?? 0);
            $totalNominal = ($data->nominal_deposito ?? 0) + ($data->nominal_abp ?? 0);

            // Get pencairan data for this AO
            $pencairanData = $pencairanByAO[$kodeaoh] ?? null;

            $aoFundingData->push([
                'kodeaoh' => $kodeaoh,
                'nmao' => $aoName,
                'total_deposito' => $data->total_deposito ?? 0,
                'total_abp' => $data->total_abp ?? 0,
                'nominal_deposito' => $data->nominal_deposito ?? 0,
                'nominal_abp' => $data->nominal_abp ?? 0,
                'total_cairkan' => $pencairanData->total_cairkan ?? 0,
                'nominal_cairkan' => $pencairanData->nominal_cairkan ?? 0,
                'total_nasabah' => $totalDeposits,
                'total_funding' => $totalNominal
            ]);
        }

        // Sort by total funding descending
        $aoFundingData = $aoFundingData->sortByDesc('total_funding')->values();

        // Grafik Kontrak Baru vs Lunas vs Pelunasan Cepat
        $nasabahStatusData = $this->getNasabahStatusData($startDay, $endDay, $filterMonth, $filterYear);

        // Trend Kontrak per Bulan (6 bulan terakhir)
        $nasabahTrendData = $this->getNasabahTrendData();

        // Top 5 Produk Tabungan berdasarkan nominal terbanyak
        $topTabunganProducts = DB::table('tabungans')
            ->select('kodeprd', DB::raw('SUM(sahirrp) as total_nominal'), DB::raw('COUNT(*) as jumlah_rekening'))
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear)
            ->when($dashboardStartDate, function ($q) use ($dashboardStartDate) {
                return $q->whereDate('tgltrnakh', '>=', $dashboardStartDate);
            })
            ->when($dashboardEndDate, function ($q) use ($dashboardEndDate) {
                return $q->whereDate('tgltrnakh', '<=', $dashboardEndDate);
            })
            ->whereNotNull('kodeprd')
            ->where('kodeprd', '!=', '')
            ->groupBy('kodeprd')
            ->orderBy('total_nominal', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                // Mapping kode produk ke nama produk
                $productMapping = [
                    '02' => 'TABUNGAN BERIMAN',
                    '04' => 'TABUNGAN BERIMAN GAYATRI',
                    '05' => 'TABUNGAN BERIMAN PEGAWAI',
                    '10' => 'Tabungan Simpanan Pelajar',
                    '11' => 'Tabungan Simpanan Masyarakat',
                    '12' => 'Tabungan Haji',
                    '13' => 'Tabungan Umum',
                    '14' => 'Tabungan Berjangka',
                    '15' => 'Tabungan SiMuda',
                    '16' => 'Tabungan SiDewasa',
                    '17' => 'Tabungan SiAnak',
                    '18' => 'Tabungan SiPintar',
                    '19' => 'Tabungan SiCerdas',
                    '20' => 'Tabungan SiBijak',
                    '21' => 'TABUNGAN TEGAR',
                    '22' => 'TABUNGAN SIMPANAN PELAJAR',
                    '25' => 'TABUNGAN PASAR',
                    '50' => 'TAB BANSOS BUPATI BOGOR',
                ];

                $item->nama_produk = $productMapping[$item->kodeprd] ?? 'Tabungan ' . $item->kodeprd;
                return $item;
            });

        $svrPredictions = null;
        if (Schema::hasTable('financial_metric_predictions') && ctype_digit((string)$filterYear) && ctype_digit((string)$filterMonth)) {
            $filterYearInt = (int)$filterYear;
            $filterMonthInt = (int)$filterMonth;

            if ($filterYearInt > 0 && $filterMonthInt >= 1 && $filterMonthInt <= 12) {
                $targetYear = $filterYearInt;
                $targetMonth = $filterMonthInt;

                try {
                    $rows = FinancialMetricPrediction::query()
                        ->where('model_name', 'svr')
                        ->where('period_year', $targetYear)
                        ->where('period_month', $targetMonth)
                        ->whereIn('metric_key', ['funding_total', 'lending_outstanding', 'npf_ratio'])
                        ->get()
                        ->keyBy('metric_key');
                } catch (\Throwable $e) {
                    $rows = collect();
                }

                $svrPredictions = [
                    'target_year' => $targetYear,
                    'target_month' => $targetMonth,
                    'funding_total' => $rows->has('funding_total') ? [
                        'predicted_value' => (float)$rows['funding_total']->predicted_value,
                        'r2' => $rows['funding_total']->r2 !== null ? (float)$rows['funding_total']->r2 : null,
                        'mape' => $rows['funding_total']->mape !== null ? (float)$rows['funding_total']->mape : null,
                    ] : null,
                    'lending_outstanding' => $rows->has('lending_outstanding') ? [
                        'predicted_value' => (float)$rows['lending_outstanding']->predicted_value,
                        'r2' => $rows['lending_outstanding']->r2 !== null ? (float)$rows['lending_outstanding']->r2 : null,
                        'mape' => $rows['lending_outstanding']->mape !== null ? (float)$rows['lending_outstanding']->mape : null,
                    ] : null,
                    'npf_ratio' => $rows->has('npf_ratio') ? [
                        'predicted_value' => (float)$rows['npf_ratio']->predicted_value,
                        'r2' => $rows['npf_ratio']->r2 !== null ? (float)$rows['npf_ratio']->r2 : null,
                        'mape' => $rows['npf_ratio']->mape !== null ? (float)$rows['npf_ratio']->mape : null,
                    ] : null,
                ];
            }
        }

        $segmentCodes = $this->getSegmentCodes();

        $sections = view('dashboard', compact(
            'funding',
            'lending',
            'npf',
            'monthlyTrends',
            'npfDistribution',
            'topNpfContributors',
            'collectibilityStats',
            'topProducts',
            'topAreas',
            'segmentasiData',
            'segmentTableData',
            'segmentasiDistribution',
            'kolektibilitasDistribution',
            'kolektibilitasComparison',
            'topProductsChart',
            'portfolioSummary',
            'kecamatanData',
            'topAOData',
            'aoFundingData',
            'nasabahStatusData',
            'nasabahTrendData',
            'fundingDetails',
            'nasabahBothFunding',
            'nasabahLending',
            'user',
            'filterMonth',
            'filterYear',
            'startDay',
            'endDay',
            'topTabunganProducts',
            'lastUpdated',
            'segmentCodes',
            'range',
            'trendRangeLabel',
            'svrPredictions',
            'segmentTableGroupBy'
        ))->renderSections();

        return response()->json([
            'html'    => $sections['content'] ?? '',
            'styles'  => $sections['styles']  ?? '',
            'scripts' => $sections['scripts'] ?? '',
        ]);
    }

    private function getNasabahStatusData($startDay, $endDay, $filterMonth, $filterYear)
    {
        // Query base dengan filter yang sama
        $query = Pembiayaan::query()
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear);

        // Apply optional date range filter
        if ($startDay && $endDay) {
            $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
            $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
            $query->whereDate('tgleff', '>=', $startDate)
                ->whereDate('tgleff', '<=', $endDate);
        }

        // Calculate previous month for comparison
        $prevMonth = $filterMonth - 1;
        $prevYear = $filterYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $filterYear - 1;
        }

        // Format month dengan leading zero
        $filterMonthStr = str_pad($filterMonth, 2, '0', STR_PAD_LEFT);
        $prevMonthStr = str_pad($prevMonth, 2, '0', STR_PAD_LEFT);

        // 1. KONTRAK BARU - kontrak dengan tgleff di bulan ini, AMBIL dari period_month bulan ini
        $startOfMonth = $filterYear . '-' . $filterMonthStr . '-01';
        $endOfMonth = $filterYear . '-' . $filterMonthStr . '-' . date('t', strtotime($startOfMonth));

        // Ambil DISTINCT nokontrak dengan tgleff di bulan ini DARI period bulan ini
        $uniqueKontrakBaru = Pembiayaan::where('period_month', $filterMonthStr)
            ->where('period_year', $filterYear)
            ->whereDate('tgleff', '>=', $startOfMonth)
            ->whereDate('tgleff', '<=', $endOfMonth)
            ->selectRaw('DISTINCT nokontrak')
            ->pluck('nokontrak')
            ->toArray();

        $jumlahNasabahBaru = count($uniqueKontrakBaru);
        $nominalNasabahBaru = 0;
        if ($jumlahNasabahBaru > 0) {
            $nominalNasabahBaru = Pembiayaan::where('period_month', $filterMonthStr)
                ->where('period_year', $filterYear)
                ->whereIn('nokontrak', $uniqueKontrakBaru)
                ->selectRaw('nokontrak, MAX(mdlawal) as mdlawal')
                ->groupBy('nokontrak')
                ->get()
                ->sum('mdlawal');
        }

        // Get kontrak yang hilang untuk pelunasan cepat dan lunas
        $kontrakBulanLalu = Pembiayaan::where('period_month', $prevMonthStr)
            ->where('period_year', $prevYear)
            ->selectRaw('DISTINCT nokontrak')
            ->pluck('nokontrak')
            ->toArray();

        $kontrakBulanIni = Pembiayaan::where('period_month', $filterMonthStr)
            ->where('period_year', $filterYear)
            ->selectRaw('DISTINCT nokontrak')
            ->pluck('nokontrak')
            ->toArray();
        $kontrakHilang = array_diff($kontrakBulanLalu, $kontrakBulanIni);

        // 2. PELUNASAN CEPAT - kontrak ada di bulan lalu, hilang di bulan ini, dan masih banyak tenor
        // Dari kontrak yang hilang, ambil data terakhir di bulan lalu
        $pelunasanCepatContracts = Pembiayaan::where('period_month', $prevMonthStr)
            ->where('period_year', $prevYear)
            ->whereIn('nokontrak', $kontrakHilang)
            ->whereRaw('angs_ke < jw') // Masih banyak tenor (lunas sebelum jatuh tempo)
            ->where('jw', '>', 0)
            ->selectRaw('nokontrak, MAX(mdlawal) as mdlawal')
            ->groupBy('nokontrak')
            ->get();

        // 3. NASABAH LUNAS - kontrak ada di bulan lalu, hilang di bulan ini, dan tenor sudah habis/hampir habis
        $nasabahLunas = Pembiayaan::where('period_month', $prevMonthStr)
            ->where('period_year', $prevYear)
            ->whereIn('nokontrak', $kontrakHilang)
            ->whereRaw('angs_ke >= jw') // Tenor sudah selesai (lunas tepat waktu)
            ->where('jw', '>', 0)
            ->where('osmdlc', '<=', 2000000) // Outstanding max 2 juta
            ->selectRaw('COUNT(*) as jumlah, SUM(mdlawal) as total_nominal')
            ->first();

        return [
            'nasabah_baru' => [
                'jumlah' => $jumlahNasabahBaru,
                'nominal' => $nominalNasabahBaru,
                'nominal_miliar' => round($nominalNasabahBaru / 1000000000, 2)
            ],
            'nasabah_lunas' => [
                'jumlah' => $nasabahLunas->jumlah ?? 0,
                'nominal' => $nasabahLunas->total_nominal ?? 0,
                'nominal_miliar' => round(($nasabahLunas->total_nominal ?? 0) / 1000000000, 2)
            ],
            'pelunasan_cepat' => [
                'jumlah' => $pelunasanCepatContracts->count(),
                'nominal' => $pelunasanCepatContracts->sum('mdlawal'),
                'nominal_miliar' => round($pelunasanCepatContracts->sum('mdlawal') / 1000000000, 2)
            ]
        ];
    }

    private function getNasabahTrendData()
    {
        // Ambil 6 bulan terakhir
        $months = [];
        $nasabahBaruData = [];
        $pelunasanCepatData = [];
        $nasabahLunasData = [];
        $nasabahBaruNominal = [];
        $pelunasanCepatNominal = [];
        $nasabahLunasNominal = [];

        // Generate 6 bulan terakhir
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            // Label bulan
            $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
            $months[] = $monthNames[$month] . ' ' . $year;

            // Hitung kontrak baru: kontrak dengan tgleff di bulan ini, AMBIL dari period_month bulan ini
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $startOfMonth = $year . '-' . $monthStr . '-01';
            $endOfMonth = $year . '-' . $monthStr . '-' . date('t', strtotime($startOfMonth));

            // Ambil DISTINCT nokontrak dengan tgleff di bulan ini DARI period bulan ini
            $uniqueKontrak = Pembiayaan::where('period_month', $monthStr)
                ->where('period_year', $year)
                ->whereDate('tgleff', '>=', $startOfMonth)
                ->whereDate('tgleff', '<=', $endOfMonth)
                ->selectRaw('DISTINCT nokontrak')
                ->pluck('nokontrak')
                ->toArray();

            $jumlahNasabahBaru = count($uniqueKontrak);
            $nominalNasabahBaru = 0;
            if ($jumlahNasabahBaru > 0) {
                $nominalNasabahBaru = Pembiayaan::where('period_month', $monthStr)
                    ->where('period_year', $year)
                    ->whereIn('nokontrak', $uniqueKontrak)
                    ->selectRaw('nokontrak, MAX(mdlawal) as mdlawal')
                    ->groupBy('nokontrak')
                    ->get()
                    ->sum('mdlawal');
            }

            $nasabahBaruData[] = $jumlahNasabahBaru;
            $nasabahBaruNominal[] = round($nominalNasabahBaru / 1000000000, 2);

            // Hitung pelunasan cepat dan lunas (kontrak hilang dari bulan sebelumnya)
            $prevMonth = $month - 1;
            $prevYear = $year;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear = $year - 1;
            }
            $prevMonthStr = str_pad($prevMonth, 2, '0', STR_PAD_LEFT);

            $kontrakBulanLalu = Pembiayaan::where('period_month', $prevMonthStr)
                ->where('period_year', $prevYear)
                ->selectRaw('DISTINCT nokontrak')
                ->pluck('nokontrak')
                ->toArray();

            $kontrakBulanIni = Pembiayaan::where('period_month', $monthStr)
                ->where('period_year', $year)
                ->selectRaw('DISTINCT nokontrak')
                ->pluck('nokontrak')
                ->toArray();

            $kontrakHilang = array_diff($kontrakBulanLalu, $kontrakBulanIni);

            // Pelunasan Cepat
            $pelunasanCepatContracts = Pembiayaan::where('period_month', $prevMonthStr)
                ->where('period_year', $prevYear)
                ->whereIn('nokontrak', $kontrakHilang)
                ->whereRaw('angs_ke < jw')
                ->where('jw', '>', 0)
                ->selectRaw('nokontrak, MAX(mdlawal) as mdlawal')
                ->groupBy('nokontrak')
                ->get();

            $pelunasanCepatData[] = $pelunasanCepatContracts->count();
            $pelunasanCepatNominal[] = round($pelunasanCepatContracts->sum('mdlawal') / 1000000000, 2);

            // Nasabah Lunas
            $nasabahLunasResult = Pembiayaan::where('period_month', $prevMonthStr)
                ->where('period_year', $prevYear)
                ->whereIn('nokontrak', $kontrakHilang)
                ->whereRaw('angs_ke >= jw')
                ->where('jw', '>', 0)
                ->where('osmdlc', '<=', 2000000)
                ->where(function ($q) use ($year, $month) {
                    $q->whereYear('tgleff', '!=', $year)
                        ->orWhereMonth('tgleff', '!=', (int) $month);
                })
                ->selectRaw('COUNT(*) as jumlah, SUM(mdlawal) as nominal')
                ->first();

            $nasabahLunasData[] = $nasabahLunasResult->jumlah ?? 0;
            $nasabahLunasNominal[] = round(($nasabahLunasResult->nominal ?? 0) / 1000000000, 2);
        }

        return [
            'labels' => $months,
            'nasabah_baru' => $nasabahBaruData,
            'pelunasan_cepat' => $pelunasanCepatData,
            'nasabah_lunas' => $nasabahLunasData,
            'nasabah_baru_nominal' => $nasabahBaruNominal,
            'pelunasan_cepat_nominal' => $pelunasanCepatNominal,
            'nasabah_lunas_nominal' => $nasabahLunasNominal
        ];
    }

    public function getTrendKontrakDetail(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $kategori = $request->input('kategori');

        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
        $startOfMonth = $year . '-' . $monthStr . '-01';
        $endOfMonth = $year . '-' . $monthStr . '-' . date('t', strtotime($startOfMonth));

        $kontrakData = [];
        $totalKontrak = 0;
        $totalNominal = 0;

        if ($kategori === 'kontrak_baru') {
            // Kontrak baru: kontrak dengan tgleff di bulan ini, AMBIL dari period_month bulan ini
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $startOfMonth = $year . '-' . $monthStr . '-01';
            $endOfMonth = $year . '-' . $monthStr . '-' . date('t', strtotime($startOfMonth));

            // Ambil DISTINCT nokontrak dengan tgleff di bulan ini DARI period bulan ini
            $uniqueKontrak = Pembiayaan::where('period_month', $monthStr)
                ->where('period_year', $year)
                ->whereDate('tgleff', '>=', $startOfMonth)
                ->whereDate('tgleff', '<=', $endOfMonth)
                ->selectRaw('DISTINCT nokontrak')
                ->pluck('nokontrak')
                ->toArray();

            // Ambil data kontrak dalam 1 query (hindari N+1)
            $kontrakData = Pembiayaan::where('period_month', $monthStr)
                ->where('period_year', $year)
                ->whereIn('nokontrak', $uniqueKontrak)
                ->select('nokontrak', 'nama', 'nocif', 'tgleff', 'mdlawal', 'osmdlc', 'angs_ke', 'jw', 'nmao')
                ->orderByDesc('mdlawal')
                ->get()
                ->unique('nokontrak')
                ->values();

            $totalKontrak = count($uniqueKontrak);
            $totalNominal = $kontrakData->sum('mdlawal');
        } elseif ($kategori === 'pelunasan_cepat' || $kategori === 'kontrak_lunas') {
            // Hitung bulan sebelumnya
            $prevMonth = $month - 1;
            $prevYear = $year;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear = $year - 1;
            }

            $prevMonthStr = str_pad($prevMonth, 2, '0', STR_PAD_LEFT);

            // Kontrak yang hilang
            $kontrakBulanLalu = Pembiayaan::where('period_month', $prevMonthStr)
                ->where('period_year', $prevYear)
                ->pluck('nokontrak')
                ->toArray();

            $kontrakBulanIni = Pembiayaan::where('period_month', $monthStr)
                ->where('period_year', $year)
                ->pluck('nokontrak')
                ->toArray();

            $kontrakHilang = array_diff($kontrakBulanLalu, $kontrakBulanIni);

            if ($kategori === 'pelunasan_cepat') {
                // Pelunasan Cepat - ambil unique nokontrak dulu
                $uniqueNokontrak = Pembiayaan::where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $kontrakHilang)
                    ->whereRaw('angs_ke < jw')
                    ->where('jw', '>', 0)
                    ->selectRaw('DISTINCT nokontrak')
                    ->pluck('nokontrak')
                    ->toArray();

                // Ambil data dalam 1 query (hindari N+1)
                $kontrakData = Pembiayaan::where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $uniqueNokontrak)
                    ->select('nokontrak', 'nama', 'nocif', 'tgleff', 'mdlawal', 'osmdlc', 'angs_ke', 'jw', 'nmao')
                    ->orderByDesc('mdlawal')
                    ->get()
                    ->unique('nokontrak')
                    ->values();
            } else {
                // Kontrak Lunas - ambil unique nokontrak dulu
                $uniqueNokontrak = Pembiayaan::where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $kontrakHilang)
                    ->whereRaw('angs_ke >= jw')
                    ->where('jw', '>', 0)
                    ->where('osmdlc', '<=', 2000000)
                    ->where(function ($q) use ($year, $month) {
                        $q->whereYear('tgleff', '!=', $year)
                            ->orWhereMonth('tgleff', '!=', (int) $month);
                    })
                    ->selectRaw('DISTINCT nokontrak')
                    ->pluck('nokontrak')
                    ->toArray();

                // Ambil data dalam 1 query (hindari N+1)
                $kontrakData = Pembiayaan::where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $uniqueNokontrak)
                    ->select('nokontrak', 'nama', 'nocif', 'tgleff', 'mdlawal', 'osmdlc', 'angs_ke', 'jw', 'nmao')
                    ->orderByDesc('mdlawal')
                    ->get()
                    ->unique('nokontrak')
                    ->values();
            }

            $totalKontrak = $kontrakData->count();
            $totalNominal = $kontrakData->sum('mdlawal');
        }

        return response()->json([
            'summary' => [
                'total_kontrak' => $totalKontrak,
                'total_nominal' => $totalNominal
            ],
            'kontrak' => $kontrakData
        ]);
    }

    public function getTrendFundingDetail(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $kategori = $request->input('kategori');
        $type = $request->input('type', 'nominal'); // nominal or jumlah

        $data = collect();
        $total = 0;

        if ($kategori === 'tabungan') {
            if ($type === 'nominal') {
                $data = Tabungan::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('notab as account', 'fnama as nama', 'sahirrp as nominal', 'tgltrnakh as tgleff', 'kodeprd')
                    ->orderBy('sahirrp', 'desc')
                    ->limit(100) // Limit to prevent encoding issues
                    ->get();
                $total = $data->sum('nominal');
            } else {
                $data = Tabungan::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('notab as account', 'fnama as nama', 'sahirrp as nominal', 'tgltrnakh as tgleff', 'kodeprd')
                    ->limit(100)
                    ->get();
                $total = $data->count();
            }
        } elseif ($kategori === 'deposito') {
            if ($type === 'nominal') {
                $data = Deposito::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('nobilyet as account', 'nama', 'nomrp as nominal', 'jkwaktu as jw', 'tglbuka as tgleff', 'kdprd')
                    ->orderBy('nomrp', 'desc')
                    ->limit(100)
                    ->get();
                $total = $data->sum('nominal');
            } else {
                $data = Deposito::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('nobilyet as account', 'nama', 'nomrp as nominal', 'jkwaktu as jw', 'tglbuka as tgleff', 'kdprd')
                    ->limit(100)
                    ->get();
                $total = $data->count();
            }
        } elseif ($kategori === 'total_funding') {
            // Combine tabungan and deposito
            $tabunganData = Tabungan::where('period_month', $month)
                ->where('period_year', $year)
                ->selectRaw("'Tabungan' as jenis, notab as no_rek, fnama as nama, sahirrp as nominal, tgltrnakh as tanggal")
                ->limit(50)
                ->get();

            $depositoData = Deposito::where('period_month', $month)
                ->where('period_year', $year)
                ->selectRaw("'Deposito' as jenis, nobilyet as no_rek, nama, nomrp as nominal, tglbuka as tanggal")
                ->limit(50)
                ->get();

            $data = $tabunganData->concat($depositoData);

            if ($type === 'nominal') {
                $data = $data->sortByDesc('nominal');
                $total = $data->sum('nominal');
            } else {
                $total = $data->count();
            }
        } elseif ($kategori === 'pencairan_deposito') {
            // Pencairan deposito - deposito yang ada di bulan sebelumnya tapi tidak ada di bulan ini
            $prevMonth = $month == '01' ? '12' : str_pad($month - 1, 2, '0', STR_PAD_LEFT);
            $prevYear = $month == '01' ? $year - 1 : $year;

            $data = DB::table('depositos as prev')
                ->leftJoin('depositos as curr', function ($join) use ($month, $year) {
                    $join->on('prev.nobilyet', '=', 'curr.nobilyet')
                        ->where('curr.period_month', $month)
                        ->where('curr.period_year', $year);
                })
                ->where('prev.period_month', $prevMonth)
                ->where('prev.period_year', $prevYear)
                ->whereNull('curr.nobilyet')
                ->select('prev.nobilyet', 'prev.nama', 'prev.nomrp', 'prev.tgleff as tglcair')
                ->orderBy('prev.nomrp', 'desc')
                ->get();

            if ($type === 'nominal') {
                $total = $data->sum('nomrp');
            } else {
                $total = $data->count();
            }
        } elseif (str_starts_with($kategori, 'linkage_')) {
            // Handle linkage categories
            $sumberDana = null;
            if ($kategori === 'linkage_dana1') $sumberDana = 'Dana Pihak 1';
            elseif ($kategori === 'linkage_dana2') $sumberDana = 'Dana Pihak 2';
            elseif ($kategori === 'linkage_dana3') $sumberDana = 'Dana Pihak 3';

            if ($sumberDana) {
                // Specific dana pihak
                $data = Linkage::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('nokontrak as norek', 'nama', 'plafon', 'tgleff', 'kelompok', 'jnsakad')
                    ->orderBy('plafon', 'desc')
                    ->limit(100)
                    ->get();
            } elseif ($kategori === 'linkage_total') {
                // All linkage data
                $data = Linkage::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('nokontrak as norek', 'nama', 'plafon', 'tgleff', 'kelompok', 'jnsakad')
                    ->orderBy('plafon', 'desc')
                    ->limit(100)
                    ->get();
            } else {
                // Specific linkage type (tabungan, deposito, pembiayaan)
                $linkageType = str_replace('linkage_', '', $kategori);
                if ($linkageType === 'tabungan') {
                    $data = Tabungan::where('period_month', $month)
                        ->where('period_year', $year)
                        ->where('linkage', '>', 0)
                        ->select('notab as norek', 'fnama as nama', 'linkage as nominal', 'tgltrnakh as tgleff', 'kodeprd')
                        ->orderBy('linkage', 'desc')
                        ->limit(100)
                        ->get();
                } elseif ($linkageType === 'deposito') {
                    $data = Deposito::where('period_month', $month)
                        ->where('period_year', $year)
                        ->where('linkage', '>', 0)
                        ->select('nobilyet as norek', 'nama', 'linkage as nominal', 'tgleff', 'kdprd')
                        ->orderBy('linkage', 'desc')
                        ->limit(100)
                        ->get();
                } elseif ($linkageType === 'pembiayaan') {
                    $data = Pembiayaan::where('period_month', $month)
                        ->where('period_year', $year)
                        ->where('linkage', '>', 0)
                        ->select('nokontrak as norek', 'nama', 'linkage as nominal', 'tgleff', 'kelompok', 'jnsakad')
                        ->orderBy('linkage', 'desc')
                        ->limit(100)
                        ->get();
                }
            }

            if ($type === 'nominal') {
                $total = $data->sum('nominal') ?? $data->sum('plafon') ?? 0;
            } else {
                $total = $data->count();
            }
        }

        return response()->json([
            'summary' => [
                'total_nasabah' => $type === 'nominal' ? $data->count() : $data->count(),
                'total_nominal' => $total
            ],
            'nasabah' => $data->toArray(),
            'total' => $total,
            'type' => $type
        ]);
    }

    public function getTrendProductDetail(Request $request)
    {
        $jenis = $request->input('jenis'); // 'tabungan' or 'deposito'
        $type = $request->input('type', 'nominal'); // nominal or jumlah

        // Apply the same global range window used by the dashboard header.
        // This endpoint returns multi-month series, so we trim the returned periods.
        $range = strtolower((string)$request->input('range', 'all'));
        $allowedRanges = ['1d', '1w', '1m', '3m', '1y', 'ytd', 'all'];
        if (!in_array($range, $allowedRanges, true)) {
            $range = 'all';
        }

        $rangeMonthsMap = [
            '1d' => 1,
            '1w' => 1,
            '1m' => 1,
            '3m' => 3,
            '1y' => 12,
            'ytd' => null,
            'all' => null,
        ];

        $filterMonth = $request->input('month');
        $filterYear = $request->input('year');

        $endYear = (is_string($filterYear) && ctype_digit($filterYear)) ? (int)$filterYear : null;
        $endMonth = (is_string($filterMonth) && ctype_digit($filterMonth)) ? (int)$filterMonth : null;

        // If month/year aren't provided, fall back to the latest available pembiayaan snapshot.
        if ($endYear === null || $endMonth === null) {
            $latestPeriod = Pembiayaan::query()
                ->select('period_year', 'period_month')
                ->whereNotNull('period_year')
                ->whereNotNull('period_month')
                ->orderByRaw('(period_year * 100 + period_month) DESC')
                ->first();

            $endYear = (int)($latestPeriod?->period_year ?: now()->year);
            $endMonth = (int)($latestPeriod?->period_month ?: now()->month);
        }

        $periodStartKey = null;
        $periodEndKey = null;

        $rangeMonths = $rangeMonthsMap[$range];
        if ($range === 'ytd' || $rangeMonths !== null) {
            $endDate = Carbon::createFromDate($endYear, $endMonth, 1)->startOfMonth();
            if ($range === 'ytd') {
                $startDate = Carbon::createFromDate($endYear, 1, 1)->startOfMonth();
            } else {
                $startDate = (clone $endDate)->subMonthsNoOverflow(max(0, (int)$rangeMonths - 1));
            }

            $periodStartKey = ((int)$startDate->format('Y')) * 100 + (int)$startDate->format('m');
            $periodEndKey = ((int)$endDate->format('Y')) * 100 + (int)$endDate->format('m');
        }

        $data = [];

        if ($jenis === 'tabungan') {
            // Get tabungan data grouped by kodeprd and period
            $query = Tabungan::select(
                'kodeprd',
                'period_year',
                'period_month',
                DB::raw('SUM(sahirrp) as total_nominal'),
                DB::raw('COUNT(*) as total_rekening')
            )
                ->whereNotNull('kodeprd')
                ->where('kodeprd', '!=', '')
                ->when($periodStartKey !== null && $periodEndKey !== null, function ($q) use ($periodStartKey, $periodEndKey) {
                    return $q->whereRaw('(period_year * 100 + period_month) BETWEEN ? AND ?', [$periodStartKey, $periodEndKey]);
                })
                ->groupBy('kodeprd', 'period_year', 'period_month')
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc');

            $results = $query->get();

            // Group by kodeprd
            $groupedData = [];
            foreach ($results as $result) {
                $kodeprd = $result->kodeprd;
                if (!isset($groupedData[$kodeprd])) {
                    $groupedData[$kodeprd] = [
                        'kodeprd' => $kodeprd,
                        'data' => []
                    ];
                }

                $monthKey = $result->period_year . '-' . str_pad($result->period_month, 2, '0', STR_PAD_LEFT);
                $groupedData[$kodeprd]['data'][$monthKey] = [
                    'nominal' => (float) $result->total_nominal,
                    'jumlah' => (int) $result->total_rekening
                ];
            }

            $data = array_values($groupedData);
        } elseif ($jenis === 'deposito') {
            // Get deposito data grouped by kdprd and period
            $query = Deposito::select(
                'kdprd',
                'period_year',
                'period_month',
                DB::raw('SUM(nomrp) as total_nominal'),
                DB::raw('COUNT(*) as total_rekening')
            )
                ->whereNotNull('kdprd')
                ->where('kdprd', '!=', '')
                ->when($periodStartKey !== null && $periodEndKey !== null, function ($q) use ($periodStartKey, $periodEndKey) {
                    return $q->whereRaw('(period_year * 100 + period_month) BETWEEN ? AND ?', [$periodStartKey, $periodEndKey]);
                })
                ->groupBy('kdprd', 'period_year', 'period_month')
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc');

            $results = $query->get();

            // Group by kdprd
            $groupedData = [];
            foreach ($results as $result) {
                $kdprd = $result->kdprd;
                if (!isset($groupedData[$kdprd])) {
                    $groupedData[$kdprd] = [
                        'kdprd' => $kdprd,
                        'data' => []
                    ];
                }

                $monthKey = $result->period_year . '-' . str_pad($result->period_month, 2, '0', STR_PAD_LEFT);
                $groupedData[$kdprd]['data'][$monthKey] = [
                    'nominal' => (float) $result->total_nominal,
                    'jumlah' => (int) $result->total_rekening
                ];
            }

            $data = array_values($groupedData);
        } elseif ($jenis === 'pembiayaan') {
            // Get pembiayaan data grouped by kelompok and period
            $query = Pembiayaan::select(
                'kelompok',
                'period_year',
                'period_month',
                DB::raw('SUM(plafon) as total_nominal'),
                DB::raw('COUNT(*) as total_rekening')
            )
                ->whereNotNull('kelompok')
                ->where('kelompok', '!=', '')
                ->when($periodStartKey !== null && $periodEndKey !== null, function ($q) use ($periodStartKey, $periodEndKey) {
                    return $q->whereRaw('(period_year * 100 + period_month) BETWEEN ? AND ?', [$periodStartKey, $periodEndKey]);
                })
                ->groupBy('kelompok', 'period_year', 'period_month')
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc');

            $results = $query->get();

            // Group by kelompok
            $groupedData = [];
            foreach ($results as $result) {
                $kelompok = $result->kelompok;
                if (!isset($groupedData[$kelompok])) {
                    $groupedData[$kelompok] = [
                        'kelompok' => $kelompok,
                        'data' => []
                    ];
                }

                $monthKey = $result->period_year . '-' . str_pad($result->period_month, 2, '0', STR_PAD_LEFT);
                $groupedData[$kelompok]['data'][$monthKey] = [
                    'nominal' => (float) $result->total_nominal,
                    'jumlah' => (int) $result->total_rekening
                ];
            }

            $data = array_values($groupedData);
        } elseif ($jenis === 'linkage') {
            // Get linkage data grouped by kelompok and period
            $query = Linkage::select(
                'kelompok',
                'period_year',
                'period_month',
                DB::raw('SUM(plafon) as total_nominal'),
                DB::raw('COUNT(*) as total_rekening')
            )
                ->whereNotNull('kelompok')
                ->where('kelompok', '!=', '')
                ->when($periodStartKey !== null && $periodEndKey !== null, function ($q) use ($periodStartKey, $periodEndKey) {
                    return $q->whereRaw('(period_year * 100 + period_month) BETWEEN ? AND ?', [$periodStartKey, $periodEndKey]);
                })
                ->groupBy('kelompok', 'period_year', 'period_month')
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc');

            $results = $query->get();

            // Group by kelompok
            $groupedData = [];
            foreach ($results as $result) {
                $kelompok = $result->kelompok;
                if (!isset($groupedData[$kelompok])) {
                    $groupedData[$kelompok] = [
                        'kelompok' => $kelompok,
                        'data' => []
                    ];
                }

                $monthKey = $result->period_year . '-' . str_pad($result->period_month, 2, '0', STR_PAD_LEFT);
                $groupedData[$kelompok]['data'][$monthKey] = [
                    'nominal' => (float) $result->total_nominal,
                    'jumlah' => (int) $result->total_rekening
                ];
            }

            $data = array_values($groupedData);
        } elseif ($jenis === 'pencairan_deposito') {
            // Get pencairan deposito data - deposits that existed in previous period but not in current period
            // This matches the logic used in the main funding trend chart
            $query = DB::table('depositos')
                ->select('period_year', 'period_month')
                ->distinct()
                ->when($periodStartKey !== null && $periodEndKey !== null, function ($q) use ($periodStartKey, $periodEndKey) {
                    return $q->whereRaw('(period_year * 100 + period_month) BETWEEN ? AND ?', [$periodStartKey, $periodEndKey]);
                })
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc')
                ->get();

            $groupedData = [];
            foreach ($query as $period) {
                $currentYear = $period->period_year;
                $currentMonth = $period->period_month;

                // Calculate previous period
                $prevMonth = $currentMonth == '01' ? '12' : str_pad($currentMonth - 1, 2, '0', STR_PAD_LEFT);
                $prevYear = $currentMonth == '01' ? $currentYear - 1 : $currentYear;

                // Query pencairan deposito for this period (deposits from previous period that don't exist in current period)
                $pencairan = DB::table('depositos as prev')
                    ->leftJoin('depositos as curr', function ($join) use ($currentMonth, $currentYear) {
                        $join->on('prev.nobilyet', '=', 'curr.nobilyet')
                            ->where('curr.period_month', $currentMonth)
                            ->where('curr.period_year', $currentYear);
                    })
                    ->where('prev.period_month', $prevMonth)
                    ->where('prev.period_year', $prevYear)
                    ->whereNull('curr.nobilyet')
                    ->select(
                        DB::raw('COUNT(*) as jumlah'),
                        DB::raw('SUM(prev.nomrp) as total')
                    )
                    ->first();

                $monthKey = $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT);
                $groupedData[$monthKey] = [
                    'kdprd' => 'PENCAIRAN', // Use a fixed product code for pencairan
                    'data' => [
                        $monthKey => [
                            'nominal' => (float) ($pencairan->total ?? 0),
                            'jumlah' => (int) ($pencairan->jumlah ?? 0)
                        ]
                    ]
                ];
            }

            $data = array_values($groupedData);
        }

        return response()->json([
            'data' => $data,
            'jenis' => $jenis,
            'type' => $type
        ]);
    }

    public function displayBoard(Request $request)
    {
        $monthNamesShort = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'Mei',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Agt',
            '09' => 'Sep',
            '10' => 'Okt',
            '11' => 'Nov',
            '12' => 'Des',
        ];
        $monthNames = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        // Latest available period (from pembiayaan)
        $latestPeriod = Pembiayaan::query()
            ->select('period_year', 'period_month')
            ->whereNotNull('period_year')->whereNotNull('period_month')
            ->orderByRaw('(period_year * 100 + period_month) DESC')
            ->first();

        $filterYear  = $latestPeriod ? (string) $latestPeriod->period_year : date('Y');
        $filterMonth = $latestPeriod
            ? str_pad((string) (int) $latestPeriod->period_month, 2, '0', STR_PAD_LEFT)
            : date('m');

        $periodeLabel = ($monthNames[$filterMonth] ?? $filterMonth) . ' ' . $filterYear;

        // Fast path: return skeleton shell immediately (before any heavy DB queries)
        if (! $request->boolean('_render')) {
            // Determine the render endpoint:
            //   - Token-based (/tv): render at /tv/render?token=X&_render=1
            //   - Auth-based (/display-board): render at /display-board/render?_render=1
            if ($request->has('token')) {
                $renderUrl = '/tv/render?token=' . rawurlencode($request->query('token')) . '&_render=1';
            } else {
                $renderUrl = '/display-board/render?_render=1';
            }
            return view('display-board-shell', compact('periodeLabel', 'renderUrl'));
        }

        // ── Lending ───────────────────────────────────────────────────────
        $totalLending = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)->sum('osmdlc');
        $totalPlafon  = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)->sum('mdlawal');
        $totalNasabah = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)->count();
        $totalNPF     = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->whereIn('colbaru', ['3', '4', '5'])->sum('osmdlc');
        $npfRatio     = $totalLending > 0 ? round(($totalNPF / $totalLending) * 100, 2) : 0;

        // Lending growth vs prev month
        $prevMonthInt = (int) $filterMonth - 1;
        $prevYearInt  = (int) $filterYear;
        if ($prevMonthInt < 1) {
            $prevMonthInt = 12;
            $prevYearInt--;
        }
        $prevMonth    = str_pad($prevMonthInt, 2, '0', STR_PAD_LEFT);
        $prevLending  = Pembiayaan::where('period_month', $prevMonth)->where('period_year', $prevYearInt)->sum('osmdlc');
        $lendingGrowth = $prevLending > 0 ? round((($totalLending - $prevLending) / $prevLending) * 100, 2) : 0;

        // ── Funding ───────────────────────────────────────────────────────
        $totalTabungan = Tabungan::where('period_month', $filterMonth)->where('period_year', $filterYear)->sum('sahirrp');
        $totalDeposito = Deposito::where('period_month', $filterMonth)->where('period_year', $filterYear)->sum('nomrp');
        $totalFunding  = $totalTabungan + $totalDeposito;

        $prevFunding = Tabungan::where('period_month', $prevMonth)->where('period_year', $prevYearInt)->sum('sahirrp')
            + Deposito::where('period_month', $prevMonth)->where('period_year', $prevYearInt)->sum('nomrp');
        $fundingGrowth = $prevFunding > 0 ? round((($totalFunding - $prevFunding) / $prevFunding) * 100, 2) : 0;

        // ── Kolektibilitas distribution (nilai & count) ───────────────────
        $kolDistrib = [];
        $kolCount   = [];
        foreach (['1', '2', '3', '4', '5'] as $kol) {
            $rows = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
                ->where('colbaru', $kol);
            $kolDistrib[$kol] = $rows->sum('osmdlc');
            $kolCount[$kol]   = $rows->count();
        }

        // ── Monthly trend (all periods) ───────────────────────────────────
        $monthlyData = Pembiayaan::select(
            'period_year',
            'period_month',
            DB::raw('SUM(mdlawal) as plafon'),
            DB::raw('SUM(osmdlc) as outstanding'),
            DB::raw('COUNT(*) as kontrak')
        )
            ->whereNotNull('period_year')->whereNotNull('period_month')
            ->groupBy('period_year', 'period_month')
            ->orderByRaw('(period_year * 100 + period_month) ASC')
            ->get();

        $monthlyTrends = [
            'labels'  => $monthlyData->map(
                fn($r) => ($monthNamesShort[str_pad((int) $r->period_month, 2, '0', STR_PAD_LEFT)] ?? $r->period_month)
                    . ' ' . $r->period_year
            )->values()->toArray(),
            'plafon'  => $monthlyData->map(fn($r) => round($r->plafon / 1e9, 2))->values()->toArray(),
            'lending' => $monthlyData->map(fn($r) => round($r->outstanding / 1e9, 2))->values()->toArray(),
            'kontrak' => $monthlyData->map(fn($r) => (int) $r->kontrak)->values()->toArray(),
            'funding' => $monthlyData->map(function ($r) {
                $m = str_pad((int) $r->period_month, 2, '0', STR_PAD_LEFT);
                $y = (int) $r->period_year;
                return round(
                    (Tabungan::where('period_month', $m)->where('period_year', $y)->sum('sahirrp')
                        + Deposito::where('period_month', $m)->where('period_year', $y)->sum('nomrp')) / 1e9,
                    2
                );
            })->values()->toArray(),
            'tabungan' => $monthlyData->map(function ($r) {
                $m = str_pad((int) $r->period_month, 2, '0', STR_PAD_LEFT);
                $y = (int) $r->period_year;
                return round(Tabungan::where('period_month', $m)->where('period_year', $y)->sum('sahirrp') / 1e9, 2);
            })->values()->toArray(),
            'deposito' => $monthlyData->map(function ($r) {
                $m = str_pad((int) $r->period_month, 2, '0', STR_PAD_LEFT);
                $y = (int) $r->period_year;
                return round(Deposito::where('period_month', $m)->where('period_year', $y)->sum('nomrp') / 1e9, 2);
            })->values()->toArray(),
        ];

        // ── NPF trend ─────────────────────────────────────────────────────
        $npfTrend = $monthlyData->map(function ($r) {
            $m   = str_pad((int) $r->period_month, 2, '0', STR_PAD_LEFT);
            $y   = (int) $r->period_year;
            $os  = (float) $r->outstanding;
            $npf = Pembiayaan::where('period_month', $m)->where('period_year', $y)
                ->whereIn('colbaru', ['3', '4', '5'])->sum('osmdlc');
            return $os > 0 ? round($npf / $os * 100, 2) : 0;
        })->values()->toArray();
        $monthlyTrends['npf_ratio'] = $npfTrend;

        // ── Pelunasan Cepat trend (same logic as main dashboard: kontrak hilang + angs_ke < jw) ──
        $pelunasanCepatByMonth = [];
        $pelunasanCepatCountByMonth = [];
        foreach ($monthlyData as $trendItem) {
            $tMonth    = (int) $trendItem->period_month;
            $tYear     = (int) $trendItem->period_year;
            $tMonthStr = str_pad($tMonth, 2, '0', STR_PAD_LEFT);
            $tPrevMonth = $tMonth - 1;
            $tPrevYear  = $tYear;
            if ($tPrevMonth < 1) {
                $tPrevMonth = 12;
                $tPrevYear = $tYear - 1;
            }
            $tPrevMonthStr = str_pad($tPrevMonth, 2, '0', STR_PAD_LEFT);

            $tKontrakLalu = Pembiayaan::where('period_month', $tPrevMonthStr)
                ->where('period_year', $tPrevYear)
                ->pluck('nokontrak')->unique()->toArray();
            $tKontrakIni = Pembiayaan::where('period_month', $tMonthStr)
                ->where('period_year', $tYear)
                ->pluck('nokontrak')->unique()->toArray();
            $tHilang = array_diff($tKontrakLalu, $tKontrakIni);

            if (!empty($tHilang)) {
                $pelunasanCepatContracts = Pembiayaan::where('period_month', $tPrevMonthStr)
                    ->where('period_year', $tPrevYear)
                    ->whereIn('nokontrak', $tHilang)
                    ->whereRaw('angs_ke < jw')
                    ->where('jw', '>', 0)
                    ->select('nokontrak', 'mdlawal')
                    ->get()->unique('nokontrak')->values();
                $pelunasanCepatByMonth[]      = round($pelunasanCepatContracts->sum('mdlawal') / 1e9, 2);
                $pelunasanCepatCountByMonth[] = $pelunasanCepatContracts->count();
            } else {
                $pelunasanCepatByMonth[]      = 0;
                $pelunasanCepatCountByMonth[] = 0;
            }
        }
        $monthlyTrends['pelunasan_cepat']       = $pelunasanCepatByMonth;
        $monthlyTrends['pelunasan_cepat_count']  = $pelunasanCepatCountByMonth;

        // ── Deposito Jatuh Tempo trend ─────────────────────────────────────
        $latestDepRaw = DB::table('depositos')
            ->whereNotNull('period_year')->whereNotNull('period_month')
            ->selectRaw('MAX(period_year*100+period_month) as latest')
            ->value('latest');
        $latestDepYear  = $latestDepRaw ? (int) substr((string) $latestDepRaw, 0, 4) : (int) $filterYear;
        $latestDepMonth = $latestDepRaw
            ? str_pad((int) substr((string) $latestDepRaw, 4), 2, '0', STR_PAD_LEFT)
            : $filterMonth;

        $depositoJtRaw = DB::table('depositos')
            ->selectRaw('YEAR(tgljtempo) as yr, MONTH(tgljtempo) as mo, SUM(nomrp) as total, COUNT(*) as n')
            ->where('period_year', $latestDepYear)
            ->where('period_month', $latestDepMonth)
            ->whereNotNull('tgljtempo')
            ->groupByRaw('YEAR(tgljtempo), MONTH(tgljtempo)')
            ->get()
            ->keyBy(fn($r) => $r->yr . str_pad((int) $r->mo, 2, '0', STR_PAD_LEFT));

        $monthlyTrends['deposito_jatuh_tempo'] = $monthlyData->map(function ($r) use ($depositoJtRaw) {
            $key = $r->period_year . str_pad((int) $r->period_month, 2, '0', STR_PAD_LEFT);
            $val = $depositoJtRaw->get($key);
            return $val ? round((float) $val->total / 1e9, 2) : 0;
        })->values()->toArray();

        $monthlyTrends['deposito_jatuh_tempo_count'] = $monthlyData->map(function ($r) use ($depositoJtRaw) {
            $key = $r->period_year . str_pad((int) $r->period_month, 2, '0', STR_PAD_LEFT);
            $val = $depositoJtRaw->get($key);
            return $val ? (int) $val->n : 0;
        })->values()->toArray();

        // ── Pencairan Deposito monthly trend (same logic as main dashboard: nobilyet comparison) ──
        $displayPeriods = $monthlyData->map(fn($r) => [
            'year'  => (int) $r->period_year,
            'month' => str_pad((int) $r->period_month, 2, '0', STR_PAD_LEFT),
        ])->values()->toArray();

        $pencairanMonthly      = [];
        $pencairanMonthlyCount = [];
        foreach ($displayPeriods as $i => $currP) {
            if ($i === 0) {
                $pencairanMonthly[]      = 0;
                $pencairanMonthlyCount[] = 0;
                continue;
            }
            $prevP  = $displayPeriods[$i - 1];
            $pcResult = DB::table('depositos as prev')
                ->leftJoin('depositos as curr', function ($join) use ($currP) {
                    $join->on('prev.nobilyet', '=', 'curr.nobilyet')
                        ->where('curr.period_month', $currP['month'])
                        ->where('curr.period_year',  $currP['year']);
                })
                ->where('prev.period_month', $prevP['month'])
                ->where('prev.period_year',  $prevP['year'])
                ->whereNull('curr.nobilyet')
                ->selectRaw('COUNT(*) as n, SUM(prev.nomrp) as total')
                ->first();
            $pencairanMonthly[]      = round(($pcResult->total ?? 0) / 1e9, 2);
            $pencairanMonthlyCount[] = (int) ($pcResult->n ?? 0);
        }
        $monthlyTrends['deposito_pencairan']       = $pencairanMonthly;
        $monthlyTrends['deposito_pencairan_count'] = $pencairanMonthlyCount;

        // ── Kolektibilitas count trend per bulan ──────────────────────────
        $kolCountRaw = Pembiayaan::select('period_year', 'period_month', 'colbaru', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('period_year')->whereNotNull('period_month')
            ->whereIn('colbaru', ['1', '2', '3', '4', '5'])
            ->groupBy('period_year', 'period_month', 'colbaru')
            ->get()
            ->groupBy(fn($r) => $r->period_year . str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT));
        foreach (['1', '2', '3', '4', '5'] as $kol) {
            $monthlyTrends['kol_count_' . $kol] = $monthlyData->map(function ($r) use ($kolCountRaw, $kol) {
                $key = $r->period_year . str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT);
                $row = ($kolCountRaw->get($key) ?? collect())->firstWhere('colbaru', $kol);
                return $row ? (int)$row->cnt : 0;
            })->values()->toArray();
        }

        // Current period pencairan for KPI card
        $jumlahPencairan = (int) end($pencairanMonthlyCount);
        $totalPencairan  = (float) (end($pencairanMonthly) * 1e9);

        // ── Top 10 NPF ─────────────────────────────────────────────────────
        $topNpf = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->whereIn('colbaru', ['3', '4', '5'])
            ->select('nama', 'nokontrak', 'osmdlc', 'colbaru', 'kdprd')
            ->orderByDesc('osmdlc')
            ->limit(10)->get();

        // ── Top 5 NPF by kolektibilitas ───────────────────────────────────
        $topKol3 = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->where('colbaru', '3')->orderByDesc('osmdlc')->limit(3)
            ->select('nama', 'osmdlc')->get();
        $topKol4 = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->where('colbaru', '4')->orderByDesc('osmdlc')->limit(3)
            ->select('nama', 'osmdlc')->get();
        $topKol5 = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->where('colbaru', '5')->orderByDesc('osmdlc')->limit(3)
            ->select('nama', 'osmdlc')->get();

        // ── Financial Highlights ──────────────────────────────────────────
        $financialHighlight = \App\Models\FinancialHighlight::orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->first();

        $fhPrev = null;
        $fhChanges = [];
        if ($financialHighlight) {
            $fhPrev = \App\Models\FinancialHighlight::getPreviousPeriod(
                $financialHighlight->period_year,
                $financialHighlight->period_month,
                'MOM'
            );
            $fields = [
                'car',
                'roa',
                'roe',
                'aset',
                'pembiayaan',
                'laba_rugi',
                'biaya',
                'pendapatan',
                'dpk',
                'fdr',
                'npf',
                'bopo',
                'cash_ratio'
            ];
            foreach ($fields as $f) {
                $fhChanges[$f] = $financialHighlight->getPercentageChange($f, $fhPrev);
            }
        }

        // ── FH historical trend (for sparklines) ──────────────────────────
        $fhHistory = \App\Models\FinancialHighlight::orderByRaw('(period_year * 100 + period_month) ASC')
            ->select(
                'period_year',
                'period_month',
                'car',
                'roa',
                'roe',
                'fdr',
                'npf',
                'bopo',
                'cash_ratio',
                'aset',
                'dpk',
                'pembiayaan',
                'laba_rugi',
                'biaya',
                'pendapatan'
            )
            ->get();

        $fhTrends = [
            'labels'     => $fhHistory->map(
                fn($r) => ($monthNamesShort[str_pad($r->period_month, 2, '0', STR_PAD_LEFT)] ?? $r->period_month)
                    . ' ' . $r->period_year
            )->values()->toArray(),
            'car'        => $fhHistory->map(fn($r) => (float) $r->car)->values()->toArray(),
            'roa'        => $fhHistory->map(fn($r) => (float) $r->roa)->values()->toArray(),
            'roe'        => $fhHistory->map(fn($r) => (float) $r->roe)->values()->toArray(),
            'fdr'        => $fhHistory->map(fn($r) => (float) $r->fdr)->values()->toArray(),
            'npf'        => $fhHistory->map(fn($r) => (float) $r->npf)->values()->toArray(),
            'bopo'       => $fhHistory->map(fn($r) => (float) $r->bopo)->values()->toArray(),
            'cash_ratio' => $fhHistory->map(fn($r) => (float) $r->cash_ratio)->values()->toArray(),
            'aset'       => $fhHistory->map(fn($r) => round((float) $r->aset / 1e9, 2))->values()->toArray(),
            'dpk'        => $fhHistory->map(fn($r) => round((float) $r->dpk / 1e9, 2))->values()->toArray(),
            'pembiayaan' => $fhHistory->map(fn($r) => round((float) $r->pembiayaan / 1e9, 2))->values()->toArray(),
            'laba_rugi'  => $fhHistory->map(fn($r) => round((float) $r->laba_rugi / 1e9, 2))->values()->toArray(),
            'biaya'      => $fhHistory->map(fn($r) => round((float) $r->biaya / 1e9, 2))->values()->toArray(),
            'pendapatan' => $fhHistory->map(fn($r) => round((float) $r->pendapatan / 1e9, 2))->values()->toArray(),
        ];

        // ── Top 5 Produk Pembiayaan ──────────────────────────────────────
        $productMapping = [
            '55' => 'Musyarakah',
            '50' => 'Murabahah',
            '56' => 'MMQ',
            '88' => 'Isthisna',
            '86' => 'Multijasa Piutang',
        ];
        $topProducts = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->select('kdprd', DB::raw('COUNT(*) as total_kontrak'), DB::raw('SUM(osmdlc) as total_outstanding'))
            ->whereNotNull('kdprd')->where('kdprd', '!=', '')
            ->groupBy('kdprd')->orderByDesc('total_outstanding')->limit(7)->get()
            ->map(function ($item) use ($productMapping) {
                $item->nama_produk = $productMapping[$item->kdprd] ?? 'Produk ' . $item->kdprd;
                return $item;
            });

        // ── Top 5 Produk Tabungan ────────────────────────────────────────
        $tabunganProductMapping = [
            '02' => 'Tabungan Beriman',
            '04' => 'Tab. Beriman Gayatri',
            '05' => 'Tab. Beriman Pegawai',
            '10' => 'Simpanan Pelajar',
            '11' => 'Simpanan Masyarakat',
            '12' => 'Tabungan Haji',
            '21' => 'Tabungan Tegar',
            '22' => 'Tab. Simpanan Pelajar',
            '25' => 'Tabungan Pasar',
        ];
        $topTabunganProducts = DB::table('tabungans')
            ->select('kodeprd', DB::raw('SUM(sahirrp) as total_nominal'), DB::raw('COUNT(*) as jumlah_rekening'))
            ->where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->whereNotNull('kodeprd')->where('kodeprd', '!=', '')
            ->groupBy('kodeprd')->orderByDesc('total_nominal')->limit(5)->get()
            ->map(function ($item) use ($tabunganProductMapping) {
                $item->nama_produk = $tabunganProductMapping[$item->kodeprd] ?? 'Tabungan ' . $item->kodeprd;
                return $item;
            });

        // ── Top 5 Produk Deposito ────────────────────────────────────────
        $depositoProductMapping = ['31' => 'Deposito', '41' => 'ABP/Mudharabah'];
        $topDepositoProducts = DB::table('depositos')
            ->select('kdprd', DB::raw('SUM(nomrp) as total_nominal'), DB::raw('COUNT(*) as jumlah_bilyet'))
            ->where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->whereNotNull('kdprd')->where('kdprd', '!=', '')
            ->groupBy('kdprd')->orderByDesc('total_nominal')->limit(5)->get()
            ->map(function ($item) use ($depositoProductMapping) {
                $item->nama_produk = $depositoProductMapping[$item->kdprd] ?? 'Deposito ' . $item->kdprd;
                return $item;
            });

        // ── Top 5 AO Lending ────────────────────────────────────────────
        $topAOLending = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->select(
                'kdaoh',
                DB::raw('COUNT(*) as total_nasabah'),
                DB::raw('SUM(osmdlc) as total_outstanding'),
                DB::raw('SUM(CASE WHEN colbaru >= 3 THEN osmdlc ELSE 0 END) as total_npf')
            )
            ->whereNotNull('kdaoh')->where('kdaoh', '!=', '')
            ->groupBy('kdaoh')->orderByDesc('total_outstanding')->limit(7)->get()
            ->map(function ($item) {
                $npfRatio = $item->total_outstanding > 0
                    ? round(($item->total_npf / $item->total_outstanding) * 100, 2) : 0;
                return [
                    'nmao'             => $this->getAoDisplayName($item->kdaoh),
                    'total_nasabah'    => $item->total_nasabah,
                    'total_outstanding' => $item->total_outstanding,
                    'npf_ratio'        => $npfRatio,
                ];
            });

        // ── Top 5 AO Funding (Deposito) ──────────────────────────────────
        $topAOFunding = DB::table('depositos')
            ->select('kodeaoh', DB::raw('SUM(nomrp) as total_funding'), DB::raw('COUNT(*) as total_bilyet'))
            ->where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->whereNotNull('kodeaoh')->where('kodeaoh', '!=', '')
            ->groupBy('kodeaoh')->orderByDesc('total_funding')->limit(7)->get()
            ->map(function ($item) {
                return [
                    'nmao'         => $this->getAoDisplayName($item->kodeaoh),
                    'total_funding' => $item->total_funding,
                    'total_bilyet' => $item->total_bilyet,
                ];
            });

        // ── Segmentasi full table (for display-board slide) ──────────────
        $segmentasiData = $this->getSegmentasiData(null, null, $filterMonth, $filterYear);

        // ── Segmentasi (kdprd) distribution ─────────────────────────────
        $segmentasiDistrib = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->select('kdprd', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(osmdlc) as outstanding'))
            ->whereNotNull('kdprd')->where('kdprd', '!=', '')
            ->groupBy('kdprd')->orderByDesc('outstanding')->get()
            ->map(function ($item) use ($productMapping) {
                $item->nama = $productMapping[$item->kdprd] ?? 'Produk ' . $item->kdprd;
                return $item;
            });

        // ── AO Lending monthly trend (top 5 by current period) ───────────
        $top5AOLendingKodes = $topAOLending->pluck('nmao')->take(5)->toArray();
        $top5AOLendingKodeIds = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->select('kdaoh', DB::raw('SUM(osmdlc) as os'))
            ->whereNotNull('kdaoh')->where('kdaoh', '!=', '')
            ->groupBy('kdaoh')->orderByDesc('os')->limit(5)->pluck('kdaoh')->toArray();
        $aoLendingTrendRaw = Pembiayaan::select('period_year', 'period_month', 'kdaoh', DB::raw('SUM(osmdlc) as os'))
            ->whereIn('kdaoh', $top5AOLendingKodeIds)
            ->whereNotNull('period_year')->whereNotNull('period_month')
            ->groupBy('period_year', 'period_month', 'kdaoh')
            ->orderByRaw('(period_year*100+period_month) ASC')->get()
            ->groupBy('kdaoh');
        $aoTrendLabels = $monthlyData->map(
            fn($r) => ($monthNamesShort[str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT)] ?? $r->period_month) . ' ' . $r->period_year
        )->values()->toArray();
        $aoLendingTrend = ['labels' => $aoTrendLabels, 'datasets' => []];
        $lendChartColors = ['#4fc3f7', '#81c784', '#ffb74d', '#f06292', '#ce93d8'];
        foreach ($top5AOLendingKodeIds as $ci => $kd) {
            $byPeriod = ($aoLendingTrendRaw->get($kd) ?? collect())->keyBy(
                fn($r) => $r->period_year . str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT)
            );
            $aoLendingTrend['datasets'][] = [
                'label' => $this->getAoDisplayName($kd),
                'color' => $lendChartColors[$ci % 5],
                'data'  => $monthlyData->map(function ($r) use ($byPeriod) {
                    $key = $r->period_year . str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT);
                    $row = $byPeriod->get($key);
                    return $row ? round($row->os / 1e9, 2) : 0;
                })->values()->toArray(),
            ];
        }

        // ── AO Funding monthly trend (top 5 by current period) ───────────
        $top5AOFundingKodeIds = DB::table('depositos')
            ->select('kodeaoh', DB::raw('SUM(nomrp) as fn'))
            ->where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->whereNotNull('kodeaoh')->where('kodeaoh', '!=', '')
            ->groupBy('kodeaoh')->orderByDesc('fn')->limit(5)->pluck('kodeaoh')->toArray();
        $depPeriods = DB::table('depositos')->select('period_year', 'period_month')
            ->whereNotNull('period_year')->whereNotNull('period_month')
            ->groupBy('period_year', 'period_month')
            ->orderByRaw('(period_year*100+period_month) ASC')->get();
        $depLabels = $depPeriods->map(
            fn($r) => ($monthNamesShort[str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT)] ?? $r->period_month) . ' ' . $r->period_year
        )->values()->toArray();
        $aoFundingTrendRaw = DB::table('depositos')
            ->select('period_year', 'period_month', 'kodeaoh', DB::raw('SUM(nomrp) as fn'))
            ->whereIn('kodeaoh', $top5AOFundingKodeIds)
            ->whereNotNull('period_year')->whereNotNull('period_month')
            ->groupBy('period_year', 'period_month', 'kodeaoh')
            ->orderByRaw('(period_year*100+period_month) ASC')->get()
            ->groupBy('kodeaoh');
        $aoFundingTrend = ['labels' => $depLabels, 'datasets' => []];
        $fundChartColors = ['#4dd0e1', '#aed581', '#ffd54f', '#ef9a9a', '#b39ddb'];
        foreach ($top5AOFundingKodeIds as $ci => $kd) {
            $byPeriod = ($aoFundingTrendRaw->get($kd) ?? collect())->keyBy(
                fn($r) => $r->period_year . str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT)
            );
            $aoFundingTrend['datasets'][] = [
                'label' => $this->getAoDisplayName($kd),
                'color' => $fundChartColors[$ci % 5],
                'data'  => $depPeriods->map(function ($r) use ($byPeriod) {
                    $key = $r->period_year . str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT);
                    $row = $byPeriod->get($key);
                    return $row ? round($row->fn / 1e9, 2) : 0;
                })->values()->toArray(),
            ];
        }

        // ── Segmentasi Pembiayaan trend per bulan ─────────────────────────
        $topSegKodes = $segmentasiDistrib->take(5)->pluck('kdprd')->toArray();
        $segTrendRaw = Pembiayaan::select('period_year', 'period_month', 'kdprd', DB::raw('SUM(osmdlc) as os'))
            ->whereIn('kdprd', $topSegKodes)
            ->whereNotNull('period_year')->whereNotNull('period_month')
            ->groupBy('period_year', 'period_month', 'kdprd')
            ->orderByRaw('(period_year*100+period_month) ASC')->get()
            ->groupBy('kdprd');
        $segmentasiTrend = ['labels' => $aoTrendLabels, 'datasets' => []];
        $segColors = ['#4fc3f7', '#81c784', '#ffb74d', '#f06292', '#ce93d8'];
        foreach ($topSegKodes as $si => $kd) {
            $byPeriod = ($segTrendRaw->get($kd) ?? collect())->keyBy(
                fn($r) => $r->period_year . str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT)
            );
            $namaMap = $productMapping;
            $segmentasiTrend['datasets'][] = [
                'label' => $namaMap[$kd] ?? 'Produk ' . $kd,
                'color' => $segColors[$si % 5],
                'data'  => $monthlyData->map(function ($r) use ($byPeriod) {
                    $key = $r->period_year . str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT);
                    $row = $byPeriod->get($key);
                    return $row ? round($row->os / 1e9, 2) : 0;
                })->values()->toArray(),
            ];
        }

        // ── Produk Funding trend per bulan (Tabungan + Deposito by produk) ─
        $fundingProdukTrend = [
            'labels'   => $depLabels,
            'tabungan' => $depPeriods->map(function ($r) {
                $m = str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT);
                return round(DB::table('tabungans')->where('period_month', $m)->where('period_year', $r->period_year)->sum('sahirrp') / 1e9, 2);
            })->values()->toArray(),
            'deposito' => $depPeriods->map(function ($r) {
                $m = str_pad((int)$r->period_month, 2, '0', STR_PAD_LEFT);
                return round(DB::table('depositos')->where('period_month', $m)->where('period_year', $r->period_year)->sum('nomrp') / 1e9, 2);
            })->values()->toArray(),
        ];

        // ── Komposisi DPK ────────────────────────────────────────────────
        $abpTotal   = DB::table('depositos')->where('period_month', $filterMonth)->where('period_year', $filterYear)->where('kdprd', '41')->sum('nomrp');
        $linkageTotal = DB::table('linkages')->where('period_month', $filterMonth)->where('period_year', $filterYear)->sum('plafon') ?? 0;
        $dp1Modal   = 75000000000; // Modal Utama (konstanta)
        $dp3TabDep  = $totalTabungan + ($totalDeposito - $abpTotal);
        $dp2LinkAbp = $linkageTotal + $abpTotal;
        $totalDanaReal = $dp1Modal + $dp2LinkAbp + $dp3TabDep;

        // ── Sebaran Nasabah per Kecamatan (Jawa Barat only) ──────────────
        $jabarKota = [
            'BANDUNG',
            'BANDUNG BARAT',
            'BANJAR',
            'BEKASI',
            'BOGOR',
            'CIAMIS',
            'CIANJUR',
            'CIMAHI',
            'CIREBON',
            'DEPOK',
            'GARUT',
            'INDRAMAYU',
            'KARAWANG',
            'KUNINGAN',
            'MAJALENGKA',
            'PANGANDARAN',
            'PURWAKARTA',
            'SUBANG',
            'SUKABUMI',
            'SUMEDANG',
            'TASIKMALAYA',
        ];
        $sebaranNasabah = Pembiayaan::where('period_month', $filterMonth)->where('period_year', $filterYear)
            ->select('kecamatan', 'kota', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(osmdlc) as outstanding'))
            ->whereIn('kota', $jabarKota)
            ->whereNotNull('kecamatan')->where('kecamatan', '!=', '')
            ->groupBy('kecamatan', 'kota')->orderByDesc('jumlah')->get();

        // ── Top 10 Nasabah DPK (Tabungan + Deposito) ─────────────────────
        $topDpkNasabah = DB::table(DB::raw('(
            SELECT nocif, fnama AS nama, SUM(sahirrp) AS tab_total, 0 AS dep_total
            FROM tabungans
            WHERE period_month = \'' . $filterMonth . '\' AND period_year = \'' . $filterYear . '\'
              AND nocif IS NOT NULL AND nocif != \'\'
            GROUP BY nocif, fnama
            UNION ALL
            SELECT nocif, nama, 0 AS tab_total, SUM(nomrp) AS dep_total
            FROM depositos
            WHERE period_month = \'' . $filterMonth . '\' AND period_year = \'' . $filterYear . '\'
              AND nocif IS NOT NULL AND nocif != \'\'
            GROUP BY nocif, nama
        ) AS dpk_union'))
            ->select(
                'nocif',
                DB::raw('MAX(nama) AS nama'),
                DB::raw('SUM(tab_total) AS tab_total'),
                DB::raw('SUM(dep_total) AS dep_total'),
                DB::raw('SUM(tab_total + dep_total) AS grand_total')
            )
            ->groupBy('nocif')
            ->orderByDesc('grand_total')
            ->limit(10)
            ->get();

        // ── Render and return JSON ─────────────────────────────────────────
        $sections = view('display-board', compact(
            'totalFunding',
            'totalTabungan',
            'totalDeposito',
            'jumlahPencairan',
            'totalPencairan',
            'totalLending',
            'totalPlafon',
            'totalNasabah',
            'totalNPF',
            'npfRatio',
            'fundingGrowth',
            'lendingGrowth',
            'kolDistrib',
            'kolCount',
            'monthlyTrends',
            'topNpf',
            'topKol3',
            'topKol4',
            'topKol5',
            'financialHighlight',
            'fhPrev',
            'fhChanges',
            'fhTrends',
            'periodeLabel',
            'topProducts',
            'topTabunganProducts',
            'topDepositoProducts',
            'topAOLending',
            'topAOFunding',
            'segmentasiDistrib',
            'segmentasiData',
            'aoLendingTrend',
            'aoFundingTrend',
            'segmentasiTrend',
            'fundingProdukTrend',
            'dp1Modal',
            'dp2LinkAbp',
            'dp3TabDep',
            'totalDanaReal',
            'sebaranNasabah',
            'topDpkNasabah'
        ))->renderSections();

        return response()->json([
            'html'    => $sections['content'] ?? '',
            'scripts' => $sections['scripts'] ?? '',
        ]);
    }

    public function indexSimple(Request $request)
    {
        $selectedMonth = $request->input('month', now()->month);
        $selectedYear = $request->input('year', now()->year);

        // Get monthly trends data - using same approach as main dashboard
        $monthlyData = Pembiayaan::select(
            'period_year',
            'period_month',
            DB::raw('SUM(mdlawal) as plafon'),
            DB::raw('SUM(osmdlc) as outstanding')
        )
            ->whereNotNull('period_year')
            ->whereNotNull('period_month')
            ->groupBy('period_year', 'period_month')
            ->orderByRaw('period_year DESC, period_month DESC')
            ->limit(6)
            ->get()
            ->reverse();

        $monthlyTrends = [
            'labels' => $monthlyData->map(function ($item) {
                $monthNames = [
                    '01' => 'Jan',
                    '02' => 'Feb',
                    '03' => 'Mar',
                    '04' => 'Apr',
                    '05' => 'Mei',
                    '06' => 'Jun',
                    '07' => 'Jul',
                    '08' => 'Agt',
                    '09' => 'Sep',
                    '10' => 'Okt',
                    '11' => 'Nov',
                    '12' => 'Des'
                ];
                return ($monthNames[$item->period_month] ?? $item->period_month) . ' ' . $item->period_year;
            })->values()->toArray(),
            'funding' => $monthlyData->map(function ($item) {
                return round($item->plafon / 1000000000, 2);
            })->values()->toArray(),
            'lending' => $monthlyData->map(function ($item) {
                return round($item->outstanding / 1000000000, 2);
            })->values()->toArray()
        ];

        return view('dashboard-simple', compact('monthlyTrends', 'selectedMonth', 'selectedYear'));
    }

    public function getSegmentasiDetail(Request $request, $category, $type)
    {
        // Get filter parameters
        $startDay = $request->input('start_day');
        $endDay = $request->input('end_day');
        $filterMonth = $request->input('month');
        $filterYear = $request->input('year');
        $range = $this->normalizeDashboardRange($request->input('range', 'all'));

        // Build base query with combined filters
        $query = Pembiayaan::query();

        // Step 1: Filter by period_month dan period_year - WAJIB
        if ($filterMonth && $filterYear) {
            $query->where('period_month', $filterMonth);
            $query->where('period_year', $filterYear);

            [$absStart, $absEnd] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$filterMonth, (string)$filterYear);
            $this->applyOptionalDateFilter($query, 'tgleff', $absStart, $absEnd);
        }

        $segmentTableGroupBy = $this->normalizeSegmentTableGroupBy($request->input('group_by', 'segmentasi'));

        if (!$this->applySegmentGroupingFilter($query, $category, $type, $segmentTableGroupBy)) {
            return response()->json(['error' => 'Segment not found'], 404);
        }

        // Get detail data
        $details = $query
            ->select('nokontrak', 'nama', 'osmdlc', 'mdlawal', 'colbaru', 'kdgroupdeb', 'nmao')
            ->orderBy('osmdlc', 'desc')
            ->limit(100) // Limit untuk performa
            ->get()
            ->map(function ($item) {
                return [
                    'nokontrak' => $item->nokontrak,
                    'nama' => $item->nama,
                    'osmdlc' => $item->osmdlc,
                    'mdlawal' => $item->mdlawal,
                    'colbaru' => $item->colbaru,
                    'colbaru_label' => $this->getCollectibilityLabel($item->colbaru),
                    'kdgroupdeb' => $item->kdgroupdeb,
                    'nmao' => $item->nmao ?? '-'
                ];
            });

        $summary = [
            'total_outstanding' => $details->sum('osmdlc'),
            'total_disburse' => $details->sum('mdlawal'),
            'total_kontrak' => $details->count()
        ];

        return response()->json([
            'group_by' => $segmentTableGroupBy,
            'category' => $category,
            'type' => $type,
            'summary' => $summary,
            'details' => $details
        ]);
    }

    public function getSegmentasiKolDetail(Request $request, $category, $type, $kol)
    {
        try {
            // Get filter parameters
            $startDay = $request->input('start_day');
            $endDay = $request->input('end_day');
            $filterMonth = $request->input('month');
            $filterYear = $request->input('year');
            $range = $this->normalizeDashboardRange($request->input('range', 'all'));

            // Build base query with combined filters
            $query = Pembiayaan::query();

            // Step 1: Filter by period_month dan period_year - WAJIB
            if ($filterMonth && $filterYear) {
                $query->where('period_month', $filterMonth);
                $query->where('period_year', $filterYear);

                [$absStart, $absEnd] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$filterMonth, (string)$filterYear);
                $this->applyOptionalDateFilter($query, 'tgleff', $absStart, $absEnd);
            }

            // Filter by kolektibilitas
            $query->where('colbaru', $kol);

            $segmentTableGroupBy = $this->normalizeSegmentTableGroupBy($request->input('group_by', 'segmentasi'));

            if (!$this->applySegmentGroupingFilter($query, $category, $type, $segmentTableGroupBy)) {
                return response()->json(['error' => 'Segment not found'], 404);
            }

            // Get detail data. Only select `dpd` if the column exists in the table
            $selectCols = ['nokontrak', 'nama', 'osmdlc', 'mdlawal', 'colbaru', 'kdgroupdeb', 'nmao'];
            if (Schema::hasColumn('pembiayaans', 'dpd')) {
                $selectCols[] = 'dpd';
            }

            $details = $query
                ->select($selectCols)
                ->orderBy('osmdlc', 'desc')
                ->limit(100) // Limit untuk performa
                ->get()
                ->map(function ($item) {
                    return [
                        'nokontrak' => $item->nokontrak,
                        'nama' => $item->nama,
                        'osmdlc' => $item->osmdlc,
                        'mdlawal' => $item->mdlawal,
                        'colbaru' => $item->colbaru,
                        'colbaru_label' => $this->getCollectibilityLabel($item->colbaru),
                        'kdgroupdeb' => $item->kdgroupdeb,
                        'nmao' => $item->nmao ?? '-',
                        'dpd' => isset($item->dpd) ? $item->dpd : 0
                    ];
                });

            $summary = [
                'total_nasabah' => $details->count(),
                'total_kontrak' => $details->count(),
                'total_outstanding' => $details->sum('osmdlc'),
                'avg_outstanding' => $details->count() > 0 ? $details->avg('osmdlc') : 0,
                'total_disburse' => $details->sum('mdlawal'),
            ];

            return response()->json([
                'group_by' => $segmentTableGroupBy,
                'category' => $category,
                'type' => $type,
                'kol' => $kol,
                'kol_label' => $this->getCollectibilityLabel($kol),
                'summary' => $summary,
                'details' => $details
            ]);
        } catch (\Throwable $e) {
            \Log::error('getSegmentasiKolDetail error: ' . $e->getMessage(), ['exception' => $e]);

            $message = config('app.debug') ? $e->getMessage() : 'Internal Server Error';

            return response()->json([
                'error' => 'Server Error',
                'message' => $message
            ], 500);
        }
    }

    public function getKecamatanDetail($kecamatan)
    {
        $startDay = request('start_day');
        $endDay = request('end_day');
        $filterMonth = request('month', now()->format('m'));
        $filterYear = request('year', now()->year);
        $range = $this->normalizeDashboardRange(request('range', 'all'));

        $query = Pembiayaan::query()
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear);

        [$absStart, $absEnd] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$filterMonth, (string)$filterYear);
        $this->applyOptionalDateFilter($query, 'tgleff', $absStart, $absEnd);

        // Filter by kecamatan
        $query->where('kecamatan', $kecamatan);

        $details = $query
            ->select('nokontrak', 'nama', 'osmdlc', 'mdlawal', 'colbaru', 'kdgroupdeb', 'kdaoh', 'nmao', 'kecamatan')
            ->get()
            ->map(function ($item) {
                return [
                    'nokontrak' => $item->nokontrak,
                    'nama' => $item->nama,
                    'osmdlc' => $item->osmdlc,
                    'mdlawal' => $item->mdlawal,
                    'colbaru' => $item->colbaru,
                    'colbaru_label' => $this->getCollectibilityLabel($item->colbaru),
                    'kdgroupdeb' => $item->kdgroupdeb,
                    'nmao' => $item->nmao ?? '-',
                    'kecamatan' => $item->kecamatan
                ];
            });

        $summary = [
            'total_outstanding' => $details->sum('osmdlc'),
            'total_disburse' => $details->sum('mdlawal'),
            'total_kontrak' => $details->count()
        ];

        return response()->json([
            'kecamatan' => $kecamatan,
            'summary' => $summary,
            'details' => $details
        ]);
    }

    public function getAODetail($nmao)
    {
        $startDay = request('start_day');
        $endDay = request('end_day');
        $filterMonth = request('month', now()->format('m'));
        $filterYear = request('year', now()->year);
        $range = $this->normalizeDashboardRange(request('range', 'all'));

        // Normalize 'all' period selection to latest available pembiayaan snapshot
        if ($filterYear === 'all' || $filterMonth === 'all') {
            $latestPeriod = Pembiayaan::query()
                ->select('period_year', 'period_month')
                ->whereNotNull('period_year')
                ->whereNotNull('period_month')
                ->orderByRaw('(period_year * 100 + period_month) DESC')
                ->first();

            $filterYear = (string)($latestPeriod?->period_year ?: now()->year);
            $resolvedMonth = $latestPeriod?->period_month ?: now()->format('m');
            $filterMonth = str_pad((string)(int)$resolvedMonth, 2, '0', STR_PAD_LEFT);

            $startDay = null;
            $endDay = null;
        } else {
            $filterMonth = str_pad((string)(int)$filterMonth, 2, '0', STR_PAD_LEFT);
            $filterYear = (string)$filterYear;
        }

        $query = Pembiayaan::query()
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear);

        [$absStart, $absEnd] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$filterMonth, (string)$filterYear);
        $this->applyOptionalDateFilter($query, 'tgleff', $absStart, $absEnd);

        // Filter by AO key (kdaoh) or name (nmao)
        $query->where(function ($subQuery) use ($nmao) {
            $subQuery->where('kdaoh', $nmao)
                ->orWhere('nmao', $nmao);
        });

        $details = $query
            ->select('nokontrak', 'nama', 'osmdlc', 'mdlawal', 'colbaru', 'kdgroupdeb', 'nmao', 'kecamatan')
            ->get()
            ->map(function ($item) {
                return [
                    'nokontrak' => $item->nokontrak,
                    'nama' => $item->nama,
                    'osmdlc' => $item->osmdlc,
                    'mdlawal' => $item->mdlawal,
                    'colbaru' => $item->colbaru,
                    'colbaru_label' => $this->getCollectibilityLabel($item->colbaru),
                    'kdgroupdeb' => $item->kdgroupdeb,
                    'nmao' => $this->getAoDisplayName($item->kdaoh ?? ($item->nmao ?? null)),
                    'kecamatan' => $item->kecamatan
                ];
            });

        $summary = [
            'total_outstanding' => $details->sum('osmdlc'),
            'total_disburse' => $details->sum('mdlawal'),
            'total_kontrak' => $details->count(),
            'total_npf' => $details->where('colbaru', '>=', 3)->sum('osmdlc'),
            'jumlah_npf' => $details->where('colbaru', '>=', 3)->count()
        ];

        return response()->json([
            'nmao' => $nmao,
            'ao_key' => $nmao,
            'ao_name' => $this->getAoDisplayName($nmao),
            'summary' => $summary,
            'details' => $details
        ]);
    }

    public function getAONpfDetail($nmao)
    {
        $startDay = request('start_day');
        $endDay = request('end_day');
        $filterMonth = request('month', now()->format('m'));
        $filterYear = request('year', now()->year);
        $range = $this->normalizeDashboardRange(request('range', 'all'));

        // Normalize 'all' period selection to latest available pembiayaan snapshot
        if ($filterYear === 'all' || $filterMonth === 'all') {
            $latestPeriod = Pembiayaan::query()
                ->select('period_year', 'period_month')
                ->whereNotNull('period_year')
                ->whereNotNull('period_month')
                ->orderByRaw('(period_year * 100 + period_month) DESC')
                ->first();

            $filterYear = (string)($latestPeriod?->period_year ?: now()->year);
            $resolvedMonth = $latestPeriod?->period_month ?: now()->format('m');
            $filterMonth = str_pad((string)(int)$resolvedMonth, 2, '0', STR_PAD_LEFT);

            $startDay = null;
            $endDay = null;
        } else {
            $filterMonth = str_pad((string)(int)$filterMonth, 2, '0', STR_PAD_LEFT);
            $filterYear = (string)$filterYear;
        }

        $query = Pembiayaan::query()
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear);

        [$absStart, $absEnd] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$filterMonth, (string)$filterYear);
        $this->applyOptionalDateFilter($query, 'tgleff', $absStart, $absEnd);

        // Filter by AO key (kdaoh) or name (nmao) and NPF (colbaru >= 3)
        $query->where(function ($subQuery) use ($nmao) {
            $subQuery->where('kdaoh', $nmao)
                ->orWhere('nmao', $nmao);
        })
            ->where('colbaru', '>=', 3);

        $selectCols = ['nokontrak', 'nama', 'osmdlc', 'mdlawal', 'colbaru', 'kdgroupdeb', 'kdaoh', 'nmao', 'kecamatan'];
        if (Schema::hasColumn('pembiayaans', 'dpd')) {
            $selectCols[] = 'dpd';
        }

        $details = $query
            ->select($selectCols)
            ->orderBy('osmdlc', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($item) {
                return [
                    'nokontrak' => $item->nokontrak,
                    'nama' => $item->nama,
                    'osmdlc' => $item->osmdlc,
                    'mdlawal' => $item->mdlawal,
                    'colbaru' => $item->colbaru,
                    'colbaru_label' => $this->getCollectibilityLabel($item->colbaru),
                    'kdgroupdeb' => $item->kdgroupdeb,
                    'nmao' => $this->getAoDisplayName($item->kdaoh ?? ($item->nmao ?? null)),
                    'kecamatan' => $item->kecamatan,
                    'dpd' => $item->dpd ?? 0
                ];
            });

        // Get total outstanding for this AO (for NPF ratio calculation)
        $totalOutstandingAOQuery = Pembiayaan::query()
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear)
            ->where(function ($subQuery) use ($nmao) {
                $subQuery->where('kdaoh', $nmao)
                    ->orWhere('nmao', $nmao);
            });

        $this->applyOptionalDateFilter($totalOutstandingAOQuery, 'tgleff', $absStart, $absEnd);
        $totalOutstandingAO = $totalOutstandingAOQuery->sum('osmdlc');

        $summary = [
            'total_nasabah' => $details->count(),
            'total_outstanding' => $details->sum('osmdlc'),
            'avg_outstanding' => $details->count() > 0 ? $details->avg('osmdlc') : 0,
            'total_disburse' => $details->sum('mdlawal'),
            'npf_ratio' => $totalOutstandingAO > 0 ? ($details->sum('osmdlc') / $totalOutstandingAO) * 100 : 0
        ];

        return response()->json([
            'nmao' => $nmao,
            'ao_key' => $nmao,
            'ao_name' => $this->getAoDisplayName($nmao),
            'summary' => $summary,
            'details' => $details
        ]);
    }

    private function getCollectibilityLabel($col)
    {
        $labels = [
            '1' => 'Lancar',
            '2' => 'DPK',
            '3' => 'Kurang Lancar',
            '4' => 'Diragukan',
            '5' => 'Macet'
        ];
        return $labels[$col] ?? '-';
    }

    private function applySegmentGroupingFilter($query, string $category, string $type, string $groupBy): bool
    {
        if ($groupBy === 'sektor_ekonomi') {
            if ($category === 'TANPA SEKTOR') {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('kdsektor')
                        ->orWhere('kdsektor', '');
                });
            } else {
                $query->where('kdsektor', $category);
            }

            if ($type === 'LAINNYA') {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('kdsub')
                        ->orWhere('kdsub', '');
                });
            } else {
                $query->where('kdsub', $type);
            }

            return true;
        }

        // Default grouping: segmentasi (kdgroupdeb mapping)
        if ($category === 'LAIN-LAIN' && $type === 'Lainnya') {
            $segmentCodes = $this->getSegmentCodes();
            $mappedCodes = [];
            foreach ($segmentCodes as $segments) {
                foreach ($segments as $codes) {
                    $mappedCodes = array_merge($mappedCodes, $codes);
                }
            }

            $query->whereNotIn('kdgroupdeb', $mappedCodes)
                ->whereNotNull('kdgroupdeb')
                ->where('kdgroupdeb', '!=', '');

            return true;
        }

        $segmentCodes = $this->getSegmentCodes();
        $codes = $segmentCodes[$category][$type] ?? [];

        if (empty($codes)) {
            return false;
        }

        $query->whereIn('kdgroupdeb', $codes);

        return true;
    }

    private function getSegmentCodes()
    {
        return [
            'FIX INCOME' => [
                'PPPK PARUH WAKTU' => ['PPPK-PW'],
                'PPPK' => ['PPPK', 'P3KDINKES', 'P3KDISDIK', 'P3K'],
                'SKPD' => [
                    '061',
                    '13',
                    '047',
                    '088',
                    '18',
                    '70',
                    '073',
                    '025',
                    '069',
                    '21',
                    '10',
                    '20',
                    '023',
                    '026',
                    '16',
                    '068',
                    '087',
                    '024',
                    '076',
                    '077',
                    '77',
                    '15',
                    '090',
                    '074',
                    '089',
                    '027',
                    '028',
                    '19',
                    '055',
                    '029',
                    '049',
                    '030',
                    '031',
                    '095',
                    '032',
                    '033',
                    '034',
                    '035',
                    '036',
                    '06',
                    '064',
                    '065',
                    '037',
                    '084',
                    '14',
                    '056',
                    '038',
                    '093',
                    '075',
                    '048',
                    '039',
                    '094',
                    '040',
                    'RUMPIN',
                    '17',
                    '041',
                    '083',
                    '042',
                    '067',
                    '22',
                    '043',
                    '044',
                    '045',
                    '12',
                    '059',
                    '070',
                    '07',
                    '086',
                    '079',
                    '081',
                    '092',
                    '08',
                    '082',
                    '078',
                    'PONPES'
                ],
            ],
            'SME' => [
                'PROPERTI' => ['PROPERTI'],
                'MIKRO' => ['MIKRO', '096', 'NULL', 'MOTEKAR'],
                'KONTRAKTOR' => ['KONTRAKTOR'],
                'PPR' => ['PPR', 'PPRSMF'],
                'PPK' => ['PPK'],
            ],
            'CHANNELLING' => [
                'KOPERASI GRAMINDO' => ['KPRGRAM'],
            ],
            'PASAR TRADISIONAL' => [
                'PASAR CIKERETEG' => ['PS 001'],
                'PASAR TAMANSARI' => ['PS 002'],
            ],
        ];
    }

    private function getSegmentasiData($startDay = null, $endDay = null, $filterMonth = null, $filterYear = null, $absoluteStartDate = null, $absoluteEndDate = null, $groupBy = 'segmentasi')
    {
        $groupBy = $this->normalizeSegmentTableGroupBy($groupBy);

        // Build base query with combined filters
        $baseQuery = function () use ($startDay, $endDay, $filterMonth, $filterYear, $absoluteStartDate, $absoluteEndDate) {
            $query = Pembiayaan::query();

            // Step 1: Filter by period_month dan period_year - WAJIB
            $query->where('period_month', $filterMonth);
            $query->where('period_year', $filterYear);

            // Step 2: Filter by tanggal range (tgleff)
            if ($absoluteStartDate || $absoluteEndDate) {
                $this->applyOptionalDateFilter($query, 'tgleff', $absoluteStartDate, $absoluteEndDate);
            } elseif ($startDay || $endDay) {
                [$dayStart, $dayEnd] = $this->resolveDashboardDateWindow('all', $startDay, $endDay, (string)$filterMonth, (string)$filterYear);
                $this->applyOptionalDateFilter($query, 'tgleff', $dayStart, $dayEnd);
            }

            return $query;
        };

        if ($groupBy === 'sektor_ekonomi') {
            $rows = $baseQuery()
                ->selectRaw("COALESCE(NULLIF(TRIM(kdsektor), ''), 'TANPA SEKTOR') as category")
                ->selectRaw("COALESCE(NULLIF(TRIM(kdsub), ''), 'LAINNYA') as type")
                ->selectRaw('SUM(osmdlc) as outstanding')
                ->selectRaw('SUM(mdlawal) as disburse')
                ->selectRaw('COUNT(*) as noa')
                ->selectRaw('COUNT(DISTINCT nocif) as cif')
                ->selectRaw("SUM(CASE WHEN colbaru = '1' THEN 1 ELSE 0 END) as col1")
                ->selectRaw("SUM(CASE WHEN colbaru = '2' THEN 1 ELSE 0 END) as col2")
                ->selectRaw("SUM(CASE WHEN colbaru = '3' THEN 1 ELSE 0 END) as col3")
                ->selectRaw("SUM(CASE WHEN colbaru = '4' THEN 1 ELSE 0 END) as col4")
                ->selectRaw("SUM(CASE WHEN colbaru = '5' THEN 1 ELSE 0 END) as col5")
                ->selectRaw("SUM(CASE WHEN colbaru = '1' THEN osmdlc ELSE 0 END) as col1_sum")
                ->selectRaw("SUM(CASE WHEN colbaru = '2' THEN osmdlc ELSE 0 END) as col2_sum")
                ->selectRaw("SUM(CASE WHEN colbaru = '3' THEN osmdlc ELSE 0 END) as col3_sum")
                ->selectRaw("SUM(CASE WHEN colbaru = '4' THEN osmdlc ELSE 0 END) as col4_sum")
                ->selectRaw("SUM(CASE WHEN colbaru = '5' THEN osmdlc ELSE 0 END) as col5_sum")
                ->groupBy('category', 'type')
                ->orderBy('category')
                ->orderBy('type')
                ->get();

            $grouped = $rows->groupBy('category');

            $data = [];
            $grandTotalOutstanding = 0;
            $grandTotalDisburse = 0;
            $grandNoa = 0;
            $grandCif = 0;
            $totalCol1Count = 0;
            $totalCol2Count = 0;
            $totalCol3Count = 0;
            $totalCol4Count = 0;
            $totalCol5Count = 0;
            $totalCol1Sum = 0;
            $totalCol2Sum = 0;
            $totalCol3Sum = 0;
            $totalCol4Sum = 0;
            $totalCol5Sum = 0;

            foreach ($grouped as $category => $items) {
                $rowspan = $items->count();
                foreach ($items->values() as $index => $item) {
                    $outstanding = (float) ($item->outstanding ?? 0);
                    $disburse = (float) ($item->disburse ?? 0);
                    $noa = (int) ($item->noa ?? 0);
                    $cif = (int) ($item->cif ?? 0);
                    $col1 = (int) ($item->col1 ?? 0);
                    $col2 = (int) ($item->col2 ?? 0);
                    $col3 = (int) ($item->col3 ?? 0);
                    $col4 = (int) ($item->col4 ?? 0);
                    $col5 = (int) ($item->col5 ?? 0);
                    $col1Sum = (float) ($item->col1_sum ?? 0);
                    $col2Sum = (float) ($item->col2_sum ?? 0);
                    $col3Sum = (float) ($item->col3_sum ?? 0);
                    $col4Sum = (float) ($item->col4_sum ?? 0);
                    $col5Sum = (float) ($item->col5_sum ?? 0);

                    $grandTotalOutstanding += $outstanding;
                    $grandTotalDisburse += $disburse;
                    $grandNoa += $noa;
                    $grandCif += $cif;

                    $totalCol1Count += $col1;
                    $totalCol2Count += $col2;
                    $totalCol3Count += $col3;
                    $totalCol4Count += $col4;
                    $totalCol5Count += $col5;

                    $totalCol1Sum += $col1Sum;
                    $totalCol2Sum += $col2Sum;
                    $totalCol3Sum += $col3Sum;
                    $totalCol4Sum += $col4Sum;
                    $totalCol5Sum += $col5Sum;

                    $data[] = [
                        'category' => (string) $category,
                        'type' => (string) ($item->type ?? 'LAINNYA'),
                        'outstanding' => $outstanding,
                        'pct_outstanding' => 0,
                        'noa' => $noa,
                        'cif' => $cif,
                        'disburse' => $disburse,
                        'pct_disburse' => 0,
                        'col1' => $col1,
                        'col2' => $col2,
                        'col3' => $col3,
                        'col4' => $col4,
                        'col5' => $col5,
                        'col1_sum' => $col1Sum,
                        'col2_sum' => $col2Sum,
                        'col3_sum' => $col3Sum,
                        'col4_sum' => $col4Sum,
                        'col5_sum' => $col5Sum,
                        'rowspan' => $index === 0 ? $rowspan : 0,
                        'is_total' => false,
                    ];
                }
            }

            $data[] = [
                'category' => 'TOTAL',
                'type' => '',
                'outstanding' => $grandTotalOutstanding,
                'pct_outstanding' => 100,
                'noa' => $grandNoa,
                'cif' => $grandCif,
                'disburse' => $grandTotalDisburse,
                'pct_disburse' => 100,
                'col1' => $totalCol1Count,
                'col2' => $totalCol2Count,
                'col3' => $totalCol3Count,
                'col4' => $totalCol4Count,
                'col5' => $totalCol5Count,
                'col1_sum' => $totalCol1Sum,
                'col2_sum' => $totalCol2Sum,
                'col3_sum' => $totalCol3Sum,
                'col4_sum' => $totalCol4Sum,
                'col5_sum' => $totalCol5Sum,
                'rowspan' => 1,
                'is_total' => true,
            ];

            foreach ($data as &$item) {
                if (!$item['is_total']) {
                    $item['pct_outstanding'] = $grandTotalOutstanding > 0 ? ($item['outstanding'] / $grandTotalOutstanding) * 100 : 0;
                    $item['pct_disburse'] = $grandTotalDisburse > 0 ? ($item['disburse'] / $grandTotalDisburse) * 100 : 0;
                }
            }

            return $data;
        }

        // Definisi struktur segmentasi yang akan ditampilkan
        $segmentStructure = [
            'FIX INCOME' => [
                ['label' => 'PPPK PARUH WAKTU', 'codes' => ['PPPK-PW']],
                ['label' => 'PPPK', 'codes' => ['PPPK', 'P3KDINKES', 'P3KDISDIK', 'P3K']],
                ['label' => 'SKPD', 'codes' => [
                    '061',
                    '13',
                    '047',
                    '088',
                    '18',
                    '70',
                    '073',
                    '025',
                    '069',
                    '21',
                    '10',
                    '20',
                    '023',
                    '026',
                    '16',
                    '068',
                    '087',
                    '024',
                    '076',
                    '077',
                    '77',
                    '15',
                    '090',
                    '074',
                    '089',
                    '027',
                    '028',
                    '19',
                    '055',
                    '029',
                    '049',
                    '030',
                    '031',
                    '095',
                    '032',
                    '033',
                    '034',
                    '035',
                    '036',
                    '06',
                    '064',
                    '065',
                    '037',
                    '084',
                    '14',
                    '056',
                    '038',
                    '093',
                    '075',
                    '048',
                    '039',
                    '094',
                    '040',
                    'RUMPIN',
                    '17',
                    '041',
                    '083',
                    '042',
                    '067',
                    '22',
                    '043',
                    '044',
                    '045',
                    '12',
                    '059',
                    '070',
                    '07',
                    '086',
                    '079',
                    '081',
                    '092',
                    '08',
                    '082',
                    '078',
                    'PONPES'
                ]],
            ],
            'SME' => [
                ['label' => 'PROPERTI', 'codes' => ['PROPERTI']],
                ['label' => 'MIKRO', 'codes' => ['MIKRO', '096', 'NULL', 'MOTEKAR']],
                ['label' => 'KONTRAKTOR', 'codes' => ['KONTRAKTOR']],
                ['label' => 'PPR', 'codes' => ['PPR', 'PPRSMF']],
                ['label' => 'PPK', 'codes' => ['PPK']],
            ],
            'CHANNELLING' => [
                ['label' => 'KOPERASI GRAMINDO', 'codes' => ['KPRGRAM']],
            ],
            'PASAR TRADISIONAL' => [
                ['label' => 'PASAR CIKERETEG', 'codes' => ['PS 001']],
                ['label' => 'PASAR TAMANSARI', 'codes' => ['PS 002']],
            ],
        ];

        // Kumpulkan semua kode yang sudah dipetakan
        $mappedCodes = [];
        $segmentCodeToLabel = [];
        $segmentLabelToCategory = [];
        foreach ($segmentStructure as $category => $segments) {
            foreach ($segments as $segment) {
                $segmentLabelToCategory[$segment['label']] = $category;
                foreach ($segment['codes'] as $segmentCode) {
                    $segmentCodeToLabel[$segmentCode] = $segment['label'];
                }
                $mappedCodes = array_merge($mappedCodes, $segment['codes']);
            }
        }
        $mappedCodes = array_values(array_unique($mappedCodes));

        $data = [];
        $grandTotalOutstanding = 0;
        $grandTotalDisburse = 0;
        $grandNoa = 0;
        $grandCif = 0;

        // Ambil agregasi semua kode segmentasi dalam satu query saja.
        $segmentAggregates = $baseQuery()
            ->whereIn('kdgroupdeb', $mappedCodes)
            ->select('kdgroupdeb')
            ->selectRaw('COALESCE(SUM(osmdlc), 0) as outstanding')
            ->selectRaw('COALESCE(SUM(mdlawal), 0) as disburse')
            ->selectRaw('COUNT(*) as noa')
            ->selectRaw('COUNT(DISTINCT nocif) as cif')
            ->selectRaw("SUM(CASE WHEN colbaru = '1' THEN 1 ELSE 0 END) as col1")
            ->selectRaw("SUM(CASE WHEN colbaru = '2' THEN 1 ELSE 0 END) as col2")
            ->selectRaw("SUM(CASE WHEN colbaru = '3' THEN 1 ELSE 0 END) as col3")
            ->selectRaw("SUM(CASE WHEN colbaru = '4' THEN 1 ELSE 0 END) as col4")
            ->selectRaw("SUM(CASE WHEN colbaru = '5' THEN 1 ELSE 0 END) as col5")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '1' THEN osmdlc ELSE 0 END), 0) as col1_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '2' THEN osmdlc ELSE 0 END), 0) as col2_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '3' THEN osmdlc ELSE 0 END), 0) as col3_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '4' THEN osmdlc ELSE 0 END), 0) as col4_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '5' THEN osmdlc ELSE 0 END), 0) as col5_sum")
            ->groupBy('kdgroupdeb')
            ->get();

        $segmentBucket = [];
        foreach ($segmentAggregates as $segmentRow) {
            $segmentCode = (string) ($segmentRow->kdgroupdeb ?? '');
            $segmentLabel = $segmentCodeToLabel[$segmentCode] ?? null;
            if (!$segmentLabel) {
                continue;
            }

            if (!isset($segmentBucket[$segmentLabel])) {
                $segmentBucket[$segmentLabel] = [
                    'outstanding' => 0.0,
                    'disburse' => 0.0,
                    'noa' => 0,
                    'cif_values' => [],
                    'col1' => 0,
                    'col2' => 0,
                    'col3' => 0,
                    'col4' => 0,
                    'col5' => 0,
                    'col1_sum' => 0.0,
                    'col2_sum' => 0.0,
                    'col3_sum' => 0.0,
                    'col4_sum' => 0.0,
                    'col5_sum' => 0.0,
                ];
            }

            $segmentBucket[$segmentLabel]['outstanding'] += (float) ($segmentRow->outstanding ?? 0);
            $segmentBucket[$segmentLabel]['disburse'] += (float) ($segmentRow->disburse ?? 0);
            $segmentBucket[$segmentLabel]['noa'] += (int) ($segmentRow->noa ?? 0);
            $segmentBucket[$segmentLabel]['col1'] += (int) ($segmentRow->col1 ?? 0);
            $segmentBucket[$segmentLabel]['col2'] += (int) ($segmentRow->col2 ?? 0);
            $segmentBucket[$segmentLabel]['col3'] += (int) ($segmentRow->col3 ?? 0);
            $segmentBucket[$segmentLabel]['col4'] += (int) ($segmentRow->col4 ?? 0);
            $segmentBucket[$segmentLabel]['col5'] += (int) ($segmentRow->col5 ?? 0);
            $segmentBucket[$segmentLabel]['col1_sum'] += (float) ($segmentRow->col1_sum ?? 0);
            $segmentBucket[$segmentLabel]['col2_sum'] += (float) ($segmentRow->col2_sum ?? 0);
            $segmentBucket[$segmentLabel]['col3_sum'] += (float) ($segmentRow->col3_sum ?? 0);
            $segmentBucket[$segmentLabel]['col4_sum'] += (float) ($segmentRow->col4_sum ?? 0);
            $segmentBucket[$segmentLabel]['col5_sum'] += (float) ($segmentRow->col5_sum ?? 0);
        }

        // Hitung CIF unik per label dengan satu query tambahan (tetap jauh lebih kecil dari sebelumnya).
        $segmentCifRows = $baseQuery()
            ->whereIn('kdgroupdeb', $mappedCodes)
            ->whereNotNull('nocif')
            ->where('nocif', '!=', '')
            ->select('kdgroupdeb', 'nocif')
            ->distinct()
            ->get();

        foreach ($segmentCifRows as $segmentCifRow) {
            $segmentCode = (string) ($segmentCifRow->kdgroupdeb ?? '');
            $segmentLabel = $segmentCodeToLabel[$segmentCode] ?? null;
            if (!$segmentLabel || !isset($segmentBucket[$segmentLabel])) {
                continue;
            }
            $segmentBucket[$segmentLabel]['cif_values'][(string) $segmentCifRow->nocif] = true;
        }

        // Process segmentasi berdasarkan struktur yang baru
        foreach ($segmentStructure as $category => $segments) {
            $rowCount = 0;
            $categoryData = [];

            foreach ($segments as $segment) {
                $bucket = $segmentBucket[$segment['label']] ?? null;

                if ($bucket) {
                    $outstanding = (float) ($bucket['outstanding'] ?? 0);
                    $disburse = (float) ($bucket['disburse'] ?? 0);
                    $noa = (int) ($bucket['noa'] ?? 0);
                    $cif = isset($bucket['cif_values']) ? count($bucket['cif_values']) : 0;
                    $col1Count = (int) ($bucket['col1'] ?? 0);
                    $col2Count = (int) ($bucket['col2'] ?? 0);
                    $col3Count = (int) ($bucket['col3'] ?? 0);
                    $col4Count = (int) ($bucket['col4'] ?? 0);
                    $col5Count = (int) ($bucket['col5'] ?? 0);
                    $col1Sum = (float) ($bucket['col1_sum'] ?? 0);
                    $col2Sum = (float) ($bucket['col2_sum'] ?? 0);
                    $col3Sum = (float) ($bucket['col3_sum'] ?? 0);
                    $col4Sum = (float) ($bucket['col4_sum'] ?? 0);
                    $col5Sum = (float) ($bucket['col5_sum'] ?? 0);
                } else {
                    $outstanding = 0;
                    $disburse = 0;
                    $noa = 0;
                    $cif = 0;
                    $col1Count = 0;
                    $col2Count = 0;
                    $col3Count = 0;
                    $col4Count = 0;
                    $col5Count = 0;
                    $col1Sum = 0;
                    $col2Sum = 0;
                    $col3Sum = 0;
                    $col4Sum = 0;
                    $col5Sum = 0;
                }

                if ($outstanding > 0 || $disburse > 0 || $noa > 0) {
                    $grandTotalOutstanding += $outstanding;
                    $grandTotalDisburse += $disburse;
                    $grandNoa += $noa;
                    $grandCif += $cif;

                    $categoryData[] = [
                        'category' => $category,
                        'type' => $segment['label'],
                        'outstanding' => $outstanding,
                        'pct_outstanding' => 0,
                        'noa' => $noa,
                        'cif' => $cif,
                        'disburse' => $disburse,
                        'pct_disburse' => 0,
                        'col1' => $col1Count,
                        'col2' => $col2Count,
                        'col3' => $col3Count,
                        'col4' => $col4Count,
                        'col5' => $col5Count,
                        'col1_sum' => $col1Sum,
                        'col2_sum' => $col2Sum,
                        'col3_sum' => $col3Sum,
                        'col4_sum' => $col4Sum,
                        'col5_sum' => $col5Sum,
                        'rowspan' => 0,
                        'is_total' => false
                    ];
                    $rowCount++;
                }
            }

            // Add category data with rowspan
            if (!empty($categoryData)) {
                $categoryData[0]['rowspan'] = $rowCount;
                $data = array_merge($data, $categoryData);
            }
        }

        // Process LAIN-LAIN (data yang tidak masuk kategori) juga dipadatkan menjadi satu query agregasi.
        $lainSummary = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)
            ->whereNotNull('kdgroupdeb')
            ->where('kdgroupdeb', '!=', '')
            ->selectRaw('COALESCE(SUM(osmdlc), 0) as outstanding')
            ->selectRaw('COALESCE(SUM(mdlawal), 0) as disburse')
            ->selectRaw('COUNT(*) as noa')
            ->selectRaw('COUNT(DISTINCT nocif) as cif')
            ->selectRaw("SUM(CASE WHEN colbaru = '1' THEN 1 ELSE 0 END) as col1")
            ->selectRaw("SUM(CASE WHEN colbaru = '2' THEN 1 ELSE 0 END) as col2")
            ->selectRaw("SUM(CASE WHEN colbaru = '3' THEN 1 ELSE 0 END) as col3")
            ->selectRaw("SUM(CASE WHEN colbaru = '4' THEN 1 ELSE 0 END) as col4")
            ->selectRaw("SUM(CASE WHEN colbaru = '5' THEN 1 ELSE 0 END) as col5")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '1' THEN osmdlc ELSE 0 END), 0) as col1_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '2' THEN osmdlc ELSE 0 END), 0) as col2_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '3' THEN osmdlc ELSE 0 END), 0) as col3_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '4' THEN osmdlc ELSE 0 END), 0) as col4_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN colbaru = '5' THEN osmdlc ELSE 0 END), 0) as col5_sum")
            ->first();

        $lainOutstanding = (float) ($lainSummary->outstanding ?? 0);
        $lainDisburse = (float) ($lainSummary->disburse ?? 0);
        $lainNoa = (int) ($lainSummary->noa ?? 0);
        $lainCif = (int) ($lainSummary->cif ?? 0);
        $lainCol1Count = (int) ($lainSummary->col1 ?? 0);
        $lainCol2Count = (int) ($lainSummary->col2 ?? 0);
        $lainCol3Count = (int) ($lainSummary->col3 ?? 0);
        $lainCol4Count = (int) ($lainSummary->col4 ?? 0);
        $lainCol5Count = (int) ($lainSummary->col5 ?? 0);
        $lainCol1Sum = (float) ($lainSummary->col1_sum ?? 0);
        $lainCol2Sum = (float) ($lainSummary->col2_sum ?? 0);
        $lainCol3Sum = (float) ($lainSummary->col3_sum ?? 0);
        $lainCol4Sum = (float) ($lainSummary->col4_sum ?? 0);
        $lainCol5Sum = (float) ($lainSummary->col5_sum ?? 0);

        if ($lainOutstanding > 0 || $lainDisburse > 0 || $lainNoa > 0) {
            $grandTotalOutstanding += $lainOutstanding;
            $grandTotalDisburse += $lainDisburse;
            $grandNoa += $lainNoa;
            $grandCif += $lainCif;

            $data[] = [
                'category' => 'LAIN-LAIN',
                'type' => 'Lainnya',
                'outstanding' => $lainOutstanding,
                'pct_outstanding' => 0,
                'noa' => $lainNoa,
                'cif' => $lainCif,
                'disburse' => $lainDisburse,
                'pct_disburse' => 0,
                'col1' => $lainCol1Count,
                'col2' => $lainCol2Count,
                'col3' => $lainCol3Count,
                'col4' => $lainCol4Count,
                'col5' => $lainCol5Count,
                'col1_sum' => $lainCol1Sum,
                'col2_sum' => $lainCol2Sum,
                'col3_sum' => $lainCol3Sum,
                'col4_sum' => $lainCol4Sum,
                'col5_sum' => $lainCol5Sum,
                'rowspan' => 1,
                'is_total' => false
            ];
        }

        // Calculate total collectibility dari data yang sudah disusun (tanpa query tambahan).
        $totalCol1Count = 0;
        $totalCol2Count = 0;
        $totalCol3Count = 0;
        $totalCol4Count = 0;
        $totalCol5Count = 0;
        $totalCol1Sum = 0.0;
        $totalCol2Sum = 0.0;
        $totalCol3Sum = 0.0;
        $totalCol4Sum = 0.0;
        $totalCol5Sum = 0.0;

        foreach ($data as $segmentItem) {
            if ($segmentItem['is_total']) {
                continue;
            }
            $totalCol1Count += (int) ($segmentItem['col1'] ?? 0);
            $totalCol2Count += (int) ($segmentItem['col2'] ?? 0);
            $totalCol3Count += (int) ($segmentItem['col3'] ?? 0);
            $totalCol4Count += (int) ($segmentItem['col4'] ?? 0);
            $totalCol5Count += (int) ($segmentItem['col5'] ?? 0);
            $totalCol1Sum += (float) ($segmentItem['col1_sum'] ?? 0);
            $totalCol2Sum += (float) ($segmentItem['col2_sum'] ?? 0);
            $totalCol3Sum += (float) ($segmentItem['col3_sum'] ?? 0);
            $totalCol4Sum += (float) ($segmentItem['col4_sum'] ?? 0);
            $totalCol5Sum += (float) ($segmentItem['col5_sum'] ?? 0);
        }

        // Add TOTAL row
        $data[] = [
            'category' => 'TOTAL',
            'type' => '',
            'outstanding' => $grandTotalOutstanding,
            'pct_outstanding' => 100,
            'noa' => $grandNoa,
            'cif' => $grandCif,
            'disburse' => $grandTotalDisburse,
            'pct_disburse' => 100,
            'col1' => $totalCol1Count,
            'col2' => $totalCol2Count,
            'col3' => $totalCol3Count,
            'col4' => $totalCol4Count,
            'col5' => $totalCol5Count,
            'col1_sum' => $totalCol1Sum,
            'col2_sum' => $totalCol2Sum,
            'col3_sum' => $totalCol3Sum,
            'col4_sum' => $totalCol4Sum,
            'col5_sum' => $totalCol5Sum,
            'rowspan' => 1,
            'is_total' => true
        ];

        // Calculate percentages
        foreach ($data as &$item) {
            if (!$item['is_total']) {
                $item['pct_outstanding'] = $grandTotalOutstanding > 0 ? ($item['outstanding'] / $grandTotalOutstanding) * 100 : 0;
                $item['pct_disburse'] = $grandTotalDisburse > 0 ? ($item['disburse'] / $grandTotalDisburse) * 100 : 0;
            }
        }

        return $data;
    }

    public function getNasabahStatusDetail(Request $request, $status)
    {
        // Get filter parameters
        $filterMonth = $request->input('month', now()->month);
        $filterYear = $request->input('year', now()->year);
        $startDay = $request->input('start_day');
        $endDay = $request->input('end_day');
        $range = $this->normalizeDashboardRange($request->input('range', 'all'));

        // Calculate previous month
        $prevMonth = $filterMonth - 1;
        $prevYear = $filterYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $filterYear - 1;
        }

        $filterMonthStr = str_pad((string)(int)$filterMonth, 2, '0', STR_PAD_LEFT);
        $prevMonthStr = str_pad((string)(int)$prevMonth, 2, '0', STR_PAD_LEFT);

        // Base query with filters
        $query = Pembiayaan::query()
            ->where('period_month', $filterMonthStr)
            ->where('period_year', $filterYear);

        [$absStart, $absEnd] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$filterMonth, (string)$filterYear);
        $this->applyOptionalDateFilter($query, 'tgleff', $absStart, $absEnd);

        // Apply status filter
        switch ($status) {
            case 'nasabah_baru':
                // Kontrak dengan tgleff di bulan ini
                $startOfMonth = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-01';
                $endOfMonth = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime($startOfMonth));

                $query->whereDate('tgleff', '>=', $startOfMonth)
                    ->whereDate('tgleff', '<=', $endOfMonth);

                $title = 'Nasabah Baru (Tgl Efektif Bulan Ini)';
                break;

            case 'pelunasan_cepat':
                // Kontrak yang ada di bulan lalu tapi hilang di bulan ini, dan masih banyak tenor
                $kontrakBulanLalu = Pembiayaan::where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->pluck('nokontrak')
                    ->toArray();

                $kontrakBulanIni = Pembiayaan::where('period_month', $filterMonthStr)
                    ->where('period_year', $filterYear)
                    ->pluck('nokontrak')
                    ->toArray();

                $kontrakHilang = array_diff($kontrakBulanLalu, $kontrakBulanIni);

                // Ambil data dari bulan lalu
                $query = Pembiayaan::query()
                    ->where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $kontrakHilang)
                    ->whereRaw('angs_ke < jw')
                    ->where('jw', '>', 0);

                $title = 'Pelunasan Cepat (Lunas Sebelum Tenor Selesai)';
                break;

            case 'nasabah_lunas':
                // Kontrak yang ada di bulan lalu tapi hilang di bulan ini, dan tenor sudah habis
                $kontrakBulanLalu = Pembiayaan::where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->pluck('nokontrak')
                    ->toArray();

                $kontrakBulanIni = Pembiayaan::where('period_month', $filterMonthStr)
                    ->where('period_year', $filterYear)
                    ->pluck('nokontrak')
                    ->toArray();

                $kontrakHilang = array_diff($kontrakBulanLalu, $kontrakBulanIni);

                // Ambil data dari bulan lalu
                $query = Pembiayaan::query()
                    ->where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $kontrakHilang)
                    ->whereRaw('angs_ke >= jw')
                    ->where('jw', '>', 0)
                    ->where('osmdlc', '<=', 2000000) // Outstanding max 2 juta
                    ->where(function ($q) use ($filterYear, $filterMonth) {
                        $q->whereYear('tgleff', '!=', $filterYear)
                            ->orWhereMonth('tgleff', '!=', (int) $filterMonth);
                    }); // Exclude nasabah baru

                $title = 'Nasabah Lunas (Lunas Tepat Waktu)';
                break;

            default:
                return response()->json(['error' => 'Invalid status'], 400);
        }

        // Get data with pagination
        $data = $query->select(
            'nokontrak',
            'nama',
            'tgleff',
            'jw',
            'angs_ke',
            'mdlawal',
            'osmdlc',
            'colbaru',
            'nmao',
            'nmjenis',
            'kecamatan'
        )
            ->orderBy('tgleff', 'desc')
            ->limit(100) // Limit to 100 records for performance
            ->get()
            ->unique('nokontrak')
            ->values();

        // Format data
        $formattedData = $data->map(function ($item) {
            return [
                'nokontrak' => $item->nokontrak,
                'nama' => $item->nama,
                'tgleff' => $item->tgleff ? date('d/m/Y', strtotime($item->tgleff)) : '-',
                'jw' => $item->jw,
                'angs_ke' => $item->angs_ke,
                'progress' => $item->jw > 0 ? round(($item->angs_ke / $item->jw) * 100, 1) : 0,
                'mdlawal' => number_format($item->mdlawal, 0, ',', '.'),
                'osmdlc' => number_format($item->osmdlc, 0, ',', '.'),
                'colbaru' => $item->colbaru ?? '-',
                'nmao' => $item->nmao ?? '-',
                'nmjenis' => $item->nmjenis ?? '-',
                'kecamatan' => $item->kecamatan ?? '-'
            ];
        });

        return response()->json([
            'title' => $title,
            'total' => $data->count(),
            'data' => $formattedData
        ]);
    }

    /**
     * Get customer details for a specific metric
     */
    public function getCustomerDetails(Request $request)
    {
        $jenis = $request->input('jenis'); // tabungan, deposito, pencairan_deposito
        $type = $request->input('type', 'nominal'); // nominal or jumlah
        $limit = $request->input('limit', 100); // Default 100 customers

        // Respect dashboard period filters when provided.
        $filterMonth = $request->input('month');
        $filterYear = $request->input('year');
        $startDay = $request->input('start_day');
        $endDay = $request->input('end_day');
        $range = $this->normalizeDashboardRange($request->input('range', 'all'));

        // Normalize 'all' / missing to latest available pembiayaan snapshot
        if ($filterMonth === 'all' || $filterYear === 'all' || $filterMonth === null || $filterYear === null || $filterMonth === '' || $filterYear === '') {
            $latestPeriod = Pembiayaan::query()
                ->select('period_year', 'period_month')
                ->whereNotNull('period_year')
                ->whereNotNull('period_month')
                ->orderByRaw('(period_year * 100 + period_month) DESC')
                ->first();

            $resolvedYear = (string)($latestPeriod?->period_year ?: date('Y'));

            if ($filterYear === 'all' || $filterYear === null || $filterYear === '' || !ctype_digit((string)$filterYear)) {
                $filterYear = $resolvedYear;
            }

            if ($filterMonth === 'all' || $filterMonth === null || $filterMonth === '' || !ctype_digit((string)$filterMonth)) {
                $resolvedMonth = (string)($latestPeriod?->period_month ?: date('m'));
                $filterMonth = str_pad((string)(int)$resolvedMonth, 2, '0', STR_PAD_LEFT);
            } else {
                $filterMonth = str_pad((string)(int)$filterMonth, 2, '0', STR_PAD_LEFT);
            }
        } else {
            $filterMonth = str_pad((string)(int)$filterMonth, 2, '0', STR_PAD_LEFT);
            $filterYear = (string)$filterYear;
        }

        [$absStart, $absEnd] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$filterMonth, (string)$filterYear);

        $customers = [];

        if ($jenis === 'tabungan') {
            // Get top customers by tabungan amount
            $customers = DB::table('tabungans')
                ->select(
                    'fnama',
                    'notab',
                    'sahirrp',
                    'period_year',
                    'period_month'
                )
                ->where('period_month', $filterMonth)
                ->where('period_year', $filterYear)
                ->when($absStart, function ($q) use ($absStart) {
                    return $q->whereDate('tgltrnakh', '>=', $absStart);
                })
                ->when($absEnd, function ($q) use ($absEnd) {
                    return $q->whereDate('tgltrnakh', '<=', $absEnd);
                })
                ->orderBy('sahirrp', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($customer) {
                    return [
                        'nama' => $customer->fnama,
                        'account' => $customer->notab,
                        'amount' => (float) $customer->sahirrp,
                        'type' => 'Tabungan',
                        'period' => $customer->period_year . '-' . str_pad($customer->period_month, 2, '0', STR_PAD_LEFT)
                    ];
                });
        } elseif ($jenis === 'deposito') {
            // Get top customers by deposito amount
            $customers = DB::table('depositos')
                ->select(
                    'nama',
                    'nobilyet',
                    'nomrp',
                    'period_year',
                    'period_month'
                )
                ->where('period_month', $filterMonth)
                ->where('period_year', $filterYear)
                ->when($absStart, function ($q) use ($absStart) {
                    return $q->whereDate('tglbuka', '>=', $absStart);
                })
                ->when($absEnd, function ($q) use ($absEnd) {
                    return $q->whereDate('tglbuka', '<=', $absEnd);
                })
                ->orderBy('nomrp', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($customer) {
                    return [
                        'nama' => $customer->nama,
                        'account' => $customer->nobilyet,
                        'amount' => (float) $customer->nomrp,
                        'type' => 'Deposito',
                        'period' => $customer->period_year . '-' . str_pad($customer->period_month, 2, '0', STR_PAD_LEFT)
                    ];
                });
        } elseif ($jenis === 'pencairan_deposito') {
            // Deposito pencairan for the selected snapshot: deposits from previous period
            // that no longer exist in the current period.
            $currMonth = $filterMonth;
            $currYear = (int)$filterYear;
            $prevMonth = $currMonth === '01' ? '12' : str_pad(((int)$currMonth) - 1, 2, '0', STR_PAD_LEFT);
            $prevYear = $currMonth === '01' ? $currYear - 1 : $currYear;

            $customers = DB::table('depositos as prev')
                ->leftJoin('depositos as curr', function ($join) use ($currMonth, $currYear) {
                    // Join on nodep which is a stable deposit identifier across imports
                    $join->on('prev.nodep', '=', 'curr.nodep')
                        ->where('curr.period_month', $currMonth)
                        ->where('curr.period_year', (string)$currYear);
                })
                ->where('prev.period_month', $prevMonth)
                ->where('prev.period_year', (string)$prevYear)
                ->whereNull('curr.nodep')
                ->select(
                    'prev.nama',
                    'prev.nodep as nobilyet',
                    'prev.nomrp',
                    'prev.period_year',
                    'prev.period_month'
                )
                ->orderBy('prev.nomrp', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($customer) {
                    return [
                        'nama' => $customer->nama,
                        'account' => $customer->nobilyet,
                        'amount' => (float) $customer->nomrp,
                        'type' => 'Pencairan Deposito',
                        'period' => $customer->period_year . '-' . str_pad($customer->period_month, 2, '0', STR_PAD_LEFT)
                    ];
                });
        }

        return response()->json([
            'jenis' => $jenis,
            'type' => $type,
            'total' => count($customers),
            'customers' => $customers
        ]);
    }

    /**
     * Get AO Funding Detail - shows monthly deposito/abp/pencairan summary for a specific AO
     */
    public function getAOFundingDetail(Request $request, $kodeaoh)
    {
        $currentYear = (string)$request->input('year', date('Y'));
        if ($currentYear === 'all' || !ctype_digit($currentYear)) {
            $currentYear = date('Y');
        }

        // AO mapping
        $aoMapping = [
            '017' => 'AGUS SETIAWAN',
            '018' => 'ADITYA FATAHILLAH MUHARAM',
            '020' => 'TAUFAN NUGRAHA',
            '021' => 'SURYA SEPTIANNANDA',
            '022' => 'FACHRI EKA PUTRA',
            '023' => 'RIZKI NIRMALA',
            '024' => 'GUNANTO',
            '025' => 'SANDI M ILHAM',
            '026' => 'FEISHAL JUAENI',
            '027' => 'ZAINAL ARIFIN',
            '028' => 'RIVI NUGRAHA',
            '029' => 'YOHAN EKA PUTRA',
            '030' => 'YUSRON WIJAYA',
            '031' => 'SABIQ KHUSNAIDI',
            '032' => 'YUNITA HERDIANA',
            '033' => 'YUSI IRMAYANTI',
            '034' => 'LARIZA AFRIANTI',
            '035' => 'DEVI NURLIANTO',
            '036' => 'FAUZIA NURUL AFINAH',
            '037' => 'ENDANG SITI MULYANI',
            '038' => 'RADEN MUHAMMAD ROBIANTARA PUTR',
            '039' => 'BALQIS CITRA SULISTYANA',
            '11' => 'DERRY NUR MUHAMMAD',
            '12' => 'FATTAH YASIN',
            'GR01' => 'AO GRAMINDO 01',
            'GR02' => 'AO GRAMINDO 02',
            'GR03' => 'AO GRAMINDO 03',
            'GR04' => 'AO GRAMINDO 04',
            'GR05' => 'AO GRAMINDO 05',
            'GR06' => 'AO BTB-GRAMIN 06',
            'GR07' => 'AO BTB-GRAMIN 07',
            'GR08' => 'AO BTB-GRAMIN 08',
            'GR09' => 'AO BTB-GRAMIN 09',
            'GR10' => 'AO BTB-GRAMIN 10',
            'GR11' => 'AO BTB-GRAMIN 11',
            'GR12' => 'AO BTB-GRAMIN 12',
            'GR13' => 'AO BTB-GRAMIN 13',
            'GR14' => 'AO BTB-GRAMIN 14',
            'GR15' => 'AO BTB-GRAMIN 15',
            'GR16' => 'AO BTB-GRAMIN 16',
            'GR17' => 'AO BTB-GRAMIN 17',
            'SDI' => 'SDI'
        ];

        $aoName = $aoMapping[$kodeaoh] ?? $kodeaoh;

        // Get monthly data for current year
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $startOfMonth = sprintf('%04d-%02d-01', (int) $currentYear, (int) $month);
            $endOfMonth = date('Y-m-t', strtotime($startOfMonth));

            // Get deposito data for this month - depositos that exist in this period AND were opened in this month
            $depositos = DB::table('depositos')
                ->where('kodeaoh', $kodeaoh)
                ->where('period_month', $monthStr)
                ->where('period_year', $currentYear)
                ->whereBetween('tglbuka', [$startOfMonth, $endOfMonth])
                ->where('stsrec', 'A')
                ->get();

            // Categorize depositos
            $depositoRegular = $depositos->where('kdprd', '31');
            $depositoAbp = $depositos->where('kdprd', '41');

            // Calculate pencairan (depositos that existed in this month but not in next month)
            // But only if next month has data - otherwise pencairan = 0
            $nextMonth = $month == 12 ? 1 : $month + 1;
            $nextYear = $month == 12 ? $currentYear + 1 : $currentYear;
            $nextMonthStr = str_pad($nextMonth, 2, '0', STR_PAD_LEFT);

            $nextMonthHasData = DB::table('depositos')
                ->where('kodeaoh', $kodeaoh)
                ->where('period_month', $nextMonthStr)
                ->where('period_year', $nextYear)
                ->where('stsrec', 'A')
                ->exists();

            if ($nextMonthHasData) {
                $depositoCairkan = DB::table('depositos as curr')
                    ->leftJoin('depositos as next', function ($join) use ($nextMonthStr, $nextYear) {
                        $join->on('curr.nobilyet', '=', 'next.nobilyet')
                            ->where('next.period_month', $nextMonthStr)
                            ->where('next.period_year', $nextYear)
                            ->where('next.stsrec', 'A');
                    })
                    ->where('curr.kodeaoh', $kodeaoh)
                    ->where('curr.period_month', $monthStr)
                    ->where('curr.period_year', $currentYear)
                    ->where('curr.stsrec', 'A')
                    ->whereNull('next.nobilyet') // Tidak ada di bulan berikutnya
                    ->select('curr.nomrp')
                    ->get();
            } else {
                // No data for next month, so no pencairan can be calculated
                $depositoCairkan = collect();
            }

            $monthlyData[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'deposito' => [
                    'count' => $depositoRegular->count(),
                    'nominal' => $depositoRegular->sum('nomrp')
                ],
                'abp' => [
                    'count' => $depositoAbp->count(),
                    'nominal' => $depositoAbp->sum('nomrp')
                ],
                'pencairan' => [
                    'count' => $depositoCairkan->count(),
                    'nominal' => $depositoCairkan->sum('nomrp')
                ],
                'total' => [
                    'count' => $depositos->count(),
                    'nominal' => $depositos->sum('nomrp')
                ]
            ];
        }

        // Calculate totals - sum of all depositos opened throughout the year
        $allOpenedDepositos = DB::table('depositos')
            ->where('kodeaoh', $kodeaoh)
            ->whereBetween('tglbuka', [
                sprintf('%04d-01-01', (int) $currentYear),
                sprintf('%04d-12-31', (int) $currentYear),
            ])
            ->where('stsrec', 'A')
            ->get();

        $depositoRegularTotal = $allOpenedDepositos->where('kdprd', '31');
        $depositoAbpTotal = $allOpenedDepositos->where('kdprd', '41');

        $totals = [
            'deposito_count' => $depositoRegularTotal->count(),
            'deposito_nominal' => $depositoRegularTotal->sum('nomrp'),
            'abp_count' => $depositoAbpTotal->count(),
            'abp_nominal' => $depositoAbpTotal->sum('nomrp'),
            'pencairan_count' => array_sum(array_column(array_column($monthlyData, 'pencairan'), 'count')),
            'pencairan_nominal' => array_sum(array_column(array_column($monthlyData, 'pencairan'), 'nominal')),
            'total_count' => $allOpenedDepositos->count(),
            'total_nominal' => $allOpenedDepositos->sum('nomrp')
        ];

        return response()->json([
            'ao_code' => $kodeaoh,
            'ao_name' => $aoName,
            'year' => $currentYear,
            'monthly_data' => $monthlyData,
            'totals' => $totals
        ]);
    }

    public function getAOCustomerDetails(Request $request, $ao, $month, $category)
    {
        $currentYear = (string)$request->input('year', date('Y'));
        if ($currentYear === 'all' || !ctype_digit($currentYear)) {
            $currentYear = date('Y');
        }

        // AO mapping
        $aoMapping = [
            '017' => 'AGUS SETIAWAN',
            '018' => 'ADITYA FATAHILLAH MUHARAM',
            '020' => 'TAUFAN NUGRAHA',
            '021' => 'SURYA SEPTIANNANDA',
            '022' => 'FACHRI EKA PUTRA',
            '023' => 'RIZKI NIRMALA',
            '024' => 'GUNANTO',
            '025' => 'SANDI M ILHAM',
            '026' => 'FEISHAL JUAENI',
            '027' => 'ZAINAL ARIFIN',
            '028' => 'RIVI NUGRAHA',
            '029' => 'YOHAN EKA PUTRA',
            '030' => 'YUSRON WIJAYA',
            '031' => 'SABIQ KHUSNAIDI',
            '032' => 'YUNITA HERDIANA',
            '033' => 'YUSI IRMAYANTI',
            '034' => 'LARIZA AFRIANTI',
            '035' => 'DEVI NURLIANTO',
            '036' => 'FAUZIA NURUL AFINAH',
            '037' => 'ENDANG SITI MULYANI',
            '038' => 'RADEN MUHAMMAD ROBIANTARA PUTR',
            '039' => 'BALQIS CITRA SULISTYANA',
            '11' => 'DERRY NUR MUHAMMAD',
            '12' => 'FATTAH YASIN',
            'GR01' => 'AO GRAMINDO 01',
            'GR02' => 'AO GRAMINDO 02',
            'GR03' => 'AO GRAMINDO 03',
            'GR04' => 'AO GRAMINDO 04',
            'GR05' => 'AO GRAMINDO 05',
            'GR06' => 'AO BTB-GRAMIN 06',
            'GR07' => 'AO BTB-GRAMIN 07',
            'GR08' => 'AO BTB-GRAMIN 08',
            'GR09' => 'AO BTB-GRAMIN 09',
            'GR10' => 'AO BTB-GRAMIN 10',
            'GR11' => 'AO BTB-GRAMIN 11',
            'GR12' => 'AO BTB-GRAMIN 12',
            'GR13' => 'AO BTB-GRAMIN 13',
            'GR14' => 'AO BTB-GRAMIN 14',
            'GR15' => 'AO BTB-GRAMIN 15',
            'GR16' => 'AO BTB-GRAMIN 16',
            'GR17' => 'AO BTB-GRAMIN 17',
            'SDI' => 'SDI'
        ];

        $aoName = $aoMapping[$ao] ?? $ao;

        // Build query based on month
        $query = DB::table('depositos')
            ->where('kodeaoh', $ao)
            ->where('stsrec', 'A');

        if ($month !== 'all') {
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $startOfMonth = sprintf('%04d-%02d-01', (int) $currentYear, (int) $month);
            $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
            // For pencairan, don't filter by opening date since withdrawn depositos could be opened in any month
            if ($category !== 'pencairan') {
                $query->where('period_month', $monthStr)
                    ->where('period_year', $currentYear)
                    ->whereBetween('tglbuka', [$startOfMonth, $endOfMonth]);
            } else {
                // For pencairan, just filter by period
                $query->where('period_month', $monthStr)
                    ->where('period_year', $currentYear);
            }
        } else {
            // For "all" months, show all depositos for the year that still exist
            $query->where('period_year', $currentYear);
        }

        // Filter by category
        switch ($category) {
            case 'deposito':
                $query->where('kdprd', '31');
                break;
            case 'abp':
                $query->where('kdprd', '41');
                break;
            case 'pencairan':
                // Depositos that exist in this month but not in next month (consistent with monthly table)
                if ($month === 'all') {
                    // For "all months", we can't calculate pencairan meaningfully
                    // Return empty result
                    return response()->json([
                        'ao' => $ao,
                        'ao_name' => $aoName,
                        'month' => $month,
                        'category' => $category,
                        'year' => $currentYear,
                        'customers' => [],
                        'total_nominal' => 0,
                        'total_nominal_formatted' => 'Rp 0',
                        'count' => 0
                    ]);
                }

                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                $nextMonth = $month == 12 ? 1 : $month + 1;
                $nextYear = $month == 12 ? $currentYear + 1 : $currentYear;
                $nextMonthStr = str_pad($nextMonth, 2, '0', STR_PAD_LEFT);

                // Check if next month has data
                $nextMonthHasData = DB::table('depositos')
                    ->where('kodeaoh', $ao)
                    ->where('period_month', $nextMonthStr)
                    ->where('period_year', $nextYear)
                    ->where('stsrec', 'A')
                    ->exists();

                if ($nextMonthHasData) {
                    // Get depositos that exist in current month but not in next month
                    $cairkanBilyets = DB::table('depositos as curr')
                        ->leftJoin('depositos as next', function ($join) use ($nextMonthStr, $nextYear) {
                            $join->on('curr.nobilyet', '=', 'next.nobilyet')
                                ->where('next.period_month', $nextMonthStr)
                                ->where('next.period_year', $nextYear)
                                ->where('next.stsrec', 'A');
                        })
                        ->where('curr.kodeaoh', $ao)
                        ->where('curr.period_month', $monthStr)
                        ->where('curr.period_year', $currentYear)
                        ->where('curr.stsrec', 'A')
                        ->whereNull('next.nobilyet') // Tidak ada di bulan berikutnya
                        ->pluck('curr.nobilyet');

                    $query->whereIn('nobilyet', $cairkanBilyets);
                } else {
                    // No data for next month, so no pencairan can be calculated
                    $query->whereRaw('1 = 0'); // Return no results
                }
                break;
            case 'total':
                // No additional filter for total
                break;
            default:
                return response()->json(['error' => 'Invalid category'], 400);
        }

        // Get customers - ensure no duplicates
        $customers = $query->select([
            'nobilyet',
            'nama',
            'nomrp',
            'tglbuka',
            'tgljtempo',
            'kdprd',
            'stsrec'
        ])
            ->distinct() // Prevent duplicates
            ->orderBy('nomrp', 'desc')
            ->get();

        // Format data
        $formattedCustomers = $customers->map(function ($customer) use ($category) {
            $currentDate = now()->format('Y-m-d');
            $isCairkan = $category === 'pencairan' || $customer->tgljtempo < $currentDate;

            return [
                'nobilyet' => $customer->nobilyet,
                'nama' => $customer->nama,
                'nomrp' => $customer->nomrp,
                'nomrp_formatted' => 'Rp ' . number_format($customer->nomrp, 0, ',', '.'),
                'tglbuka' => $customer->tglbuka ? date('d/m/Y', strtotime($customer->tglbuka)) : '-',
                'tgljtempo' => $customer->tgljtempo ? date('d/m/Y', strtotime($customer->tgljtempo)) : '-',
                'kdprd' => $customer->kdprd,
                'status' => $isCairkan ? 'Cairkan' : 'Aktif',
                'is_cairkan' => $isCairkan
            ];
        });

        $totalNominal = $customers->sum('nomrp');

        return response()->json([
            'ao' => $ao,
            'ao_name' => $aoName,
            'month' => $month,
            'category' => $category,
            'year' => $currentYear,
            'customers' => $formattedCustomers,
            'total_nominal' => $totalNominal,
            'total_nominal_formatted' => 'Rp ' . number_format($totalNominal, 0, ',', '.'),
            'count' => $customers->count()
        ]);
    }

    /**
     * Get kolektibilitas details for a specific category
     */
    public function getKolektibilitasDetails(Request $request)
    {
        $kategori = $request->input('kategori');
        $limit = $request->input('limit', 100);
        $startDay = $request->input('start_day');
        $endDay = $request->input('end_day');
        $range = $this->normalizeDashboardRange($request->input('range', 'all'));

        // Validate kategori
        if (!in_array($kategori, ['1', '2', '3', '4', '5'])) {
            return response()->json(['error' => 'Invalid kategori'], 400);
        }

        // Product mapping for pembiayaan (financing) products
        $productMapping = [
            '55' => 'Musyarakah',
            '50' => 'Murabahah',
            '56' => 'MMQ',
            '88' => 'Isthisna',
            '86' => 'Multijasa Piutang',
        ];

        // Respect dashboard period filters
        $currentMonth = (string)$request->input('month', date('m'));
        $currentYear = (string)$request->input('year', date('Y'));

        // Normalize 'all' to latest available pembiayaan snapshot
        if ($currentMonth === 'all' || $currentYear === 'all' || !ctype_digit($currentMonth) || !ctype_digit($currentYear)) {
            $latestPeriod = Pembiayaan::query()
                ->select('period_year', 'period_month')
                ->whereNotNull('period_year')
                ->whereNotNull('period_month')
                ->orderByRaw('(period_year * 100 + period_month) DESC')
                ->first();

            $currentYear = (string)($latestPeriod?->period_year ?: date('Y'));
            $resolvedMonth = (string)($latestPeriod?->period_month ?: date('m'));
            $currentMonth = str_pad((string)(int)$resolvedMonth, 2, '0', STR_PAD_LEFT);
            $startDay = null;
            $endDay = null;
        } else {
            $currentMonth = str_pad((string)(int)$currentMonth, 2, '0', STR_PAD_LEFT);
        }

        [$absStart, $absEnd] = $this->resolveDashboardDateWindow($range, $startDay, $endDay, (string)$currentMonth, (string)$currentYear);

        // Query pembiayaan data for selected period with kolektibilitas filter
        $query = Pembiayaan::where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->where('colbaru', $kategori)
            ->when($absStart, function ($q) use ($absStart) {
                return $q->whereDate('tgleff', '>=', $absStart);
            })
            ->when($absEnd, function ($q) use ($absEnd) {
                return $q->whereDate('tgleff', '<=', $absEnd);
            })
            ->orderBy('osmdlc', 'desc') // Order by outstanding descending
            ->limit($limit);

        $customers = $query->select([
            'nama',
            'nokontrak',
            'osmdlc',
            'kdprd',
            'kdaoh',
            'nmao',
            'tgleff'
        ])->get();

        // Get total count for this kategori
        $totalCountQuery = Pembiayaan::where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->where('colbaru', $kategori);
        $this->applyOptionalDateFilter($totalCountQuery, 'tgleff', $absStart, $absEnd);
        $totalCount = $totalCountQuery->count();

        // Format data
        $formattedCustomers = $customers->map(function ($customer) use ($productMapping) {
            return [
                'nama' => $customer->nama,
                'nokontrak' => $customer->nokontrak,
                'osmdlc' => (float) $customer->osmdlc,
                'nama_produk' => $productMapping[$customer->kdprd] ?? 'Produk ' . ($customer->kdprd ?: 'Unknown'),
                'kodeaoh' => $customer->kdaoh,
                'nama_ao' => $customer->nmao,
                'tgl_akad' => $customer->tgleff ? $customer->tgleff->format('d/m/Y') : null,
                'jenis_akad' => $customer->kdprd // Using product code as contract type
            ];
        });

        return response()->json([
            'kategori' => $kategori,
            'customers' => $formattedCustomers,
            'total' => $totalCount,
            'limit' => $limit,
            'month' => $currentMonth,
            'year' => $currentYear
        ]);
    }
}
