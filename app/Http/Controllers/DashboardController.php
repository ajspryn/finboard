<?php

namespace App\Http\Controllers;

use App\Models\Pembiayaan;
use App\Models\Tabungan;
use App\Models\Deposito;
use App\Models\Linkage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with banking data
     */
    public function index(Request $request)
    {
        // Get current user
        $user = Auth::user();

        // Get filter parameters
        $startDay = $request->input('start_day');
        $endDay = $request->input('end_day');
        $filterMonth = $request->input('month', date('m')); // Default bulan berjalan
        $filterYear = $request->input('year', date('Y'));   // Default tahun berjalan

        // Build base query with combined filters
        $query = Pembiayaan::query();

        // Step 1: Filter by period_month dan period_year - WAJIB
        $query->where('period_month', $filterMonth);
        $query->where('period_year', $filterYear);

        // Step 2: Filter by tanggal range (tgleff) - OPSIONAL
        if ($startDay && $endDay) {
            // Jika ada kedua tanggal, filter range
            $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
            $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
            $query->whereDate('tgleff', '>=', $startDate)
                ->whereDate('tgleff', '<=', $endDate);
        } elseif ($startDay) {
            // Hanya ada start day
            $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
            $query->whereDate('tgleff', '>=', $startDate);
        } elseif ($endDay) {
            // Hanya ada end day
            $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
            $query->whereDate('tgleff', '<=', $endDate);
        }

        // Segmentasi Outstanding & Disburse (gabungan)
        $segmentasiData = $this->getSegmentasiData($startDay, $endDay, $filterMonth, $filterYear);

        // Total Outstanding Lending (Pokok Pembiayaan saja)
        $totalLendingModal = (clone $query)->sum('osmdlc'); // Outstanding Modal/Pokok
        $totalLendingMargin = (clone $query)->sum('osmgnc'); // Outstanding Margin

        // Total Modal Awal (Plafon awal pembiayaan)
        $totalModalAwal = (clone $query)->sum('mdlawal');
        $totalMarginAwal = (clone $query)->sum('mgnawal');

        // Count total nasabah/kontrak
        $totalNasabah = (clone $query)->count();

        // Calculate NPF (kolektibilitas >= 3) - hanya pokok
        $npfData = (clone $query)->whereIn('colbaru', ['3', '4', '5'])->get();
        $totalNPF = $npfData->sum('osmdlc'); // NPF hanya dari pokok
        $totalTunggakanPokok = $npfData->sum('tgkpok'); // Tunggakan pokok NPF
        $npfRatio = $totalLendingModal > 0 ? ($totalNPF / $totalLendingModal) * 100 : 0;

        // Funding data (Real dari tabel tabungans dan depositos)
        // Build query dengan filter yang sama seperti lending
        $tabunganQuery = Tabungan::where('period_month', $filterMonth)
            ->where('period_year', $filterYear);

        $depositoQuery = Deposito::where('period_month', $filterMonth)
            ->where('period_year', $filterYear);

        // Apply date range filter jika ada (menggunakan tgltrnakh untuk tabungan, tglbuka untuk deposito)
        if ($startDay && $endDay) {
            $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
            $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);

            $tabunganQuery->whereDate('tgltrnakh', '>=', $startDate)
                ->whereDate('tgltrnakh', '<=', $endDate);

            $depositoQuery->whereDate('tglbuka', '>=', $startDate)
                ->whereDate('tglbuka', '<=', $endDate);
        } elseif ($startDay) {
            $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);

            $tabunganQuery->whereDate('tgltrnakh', '>=', $startDate);
            $depositoQuery->whereDate('tglbuka', '>=', $startDate);
        } elseif ($endDay) {
            $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);

            $tabunganQuery->whereDate('tgltrnakh', '<=', $endDate);
            $depositoQuery->whereDate('tglbuka', '<=', $endDate);
        }

        $totalTabungan = (clone $tabunganQuery)->sum('sahirrp');
        $totalDeposito = (clone $depositoQuery)->sum('nomrp');
        $countTabungan = (clone $tabunganQuery)->count();
        $countDeposito = (clone $depositoQuery)->count();

        $totalFunding = $totalTabungan + $totalDeposito;

        // Calculate growth from previous month
        $prevMonth = $filterMonth == '01' ? '12' : str_pad($filterMonth - 1, 2, '0', STR_PAD_LEFT);
        $prevYear = $filterMonth == '01' ? $filterYear - 1 : $filterYear;

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
                'total' => $totalPencairan
            ]
        ];

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

        // Nasabah dengan Total Saldo Funding Terbesar (Tabungan + Deposito)
        // Gabungkan semua nasabah dari tabungan dan deposito
        $allNasabahFunding = DB::table(DB::raw('(
            SELECT nocif, fnama as nama, sahirrp as saldo, "Tabungan" as jenis, tgltrnakh as tanggal
            FROM tabungans
            WHERE period_month = ' . $filterMonth . ' AND period_year = ' . $filterYear . '
            ' . ($startDay && $endDay ? 'AND DATE(tgltrnakh) >= "' . $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT) . '" AND DATE(tgltrnakh) <= "' . $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT) . '"' : '') . '
            ' . ($startDay && !$endDay ? 'AND DATE(tgltrnakh) >= "' . $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT) . '"' : '') . '
            ' . (!$startDay && $endDay ? 'AND DATE(tgltrnakh) <= "' . $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT) . '"' : '') . '
            UNION ALL
            SELECT nocif, nama, nomrp as saldo, "Deposito" as jenis, tglbuka as tanggal
            FROM depositos
            WHERE period_month = ' . $filterMonth . ' AND period_year = ' . $filterYear . '
            ' . ($startDay && $endDay ? 'AND DATE(tglbuka) >= "' . $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT) . '" AND DATE(tglbuka) <= "' . $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT) . '"' : '') . '
            ' . ($startDay && !$endDay ? 'AND DATE(tglbuka) >= "' . $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT) . '"' : '') . '
            ' . (!$startDay && $endDay ? 'AND DATE(tglbuka) <= "' . $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT) . '"' : '') . '
        ) as combined'))
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
            ->when($startDay && $endDay, function ($query) use ($filterYear, $filterMonth, $startDay, $endDay) {
                $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
                $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
                return $query->whereDate('tgleff', '>=', $startDate)
                    ->whereDate('tgleff', '<=', $endDate);
            })
            ->when($startDay && !$endDay, function ($query) use ($filterYear, $filterMonth, $startDay) {
                $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
                return $query->whereDate('tgleff', '>=', $startDate);
            })
            ->when(!$startDay && $endDay, function ($query) use ($filterYear, $filterMonth, $endDay) {
                $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
                return $query->whereDate('tgleff', '<=', $endDate);
            })
            ->select(
                'nocif',
                DB::raw('MAX(nama) as nama'),
                DB::raw('COUNT(*) as jumlah_pinjaman'),
                DB::raw('SUM(mdlawal) as total_pinjaman'),
                DB::raw('SUM(mgnawal) as total_bunga'),
                DB::raw('SUM(osmdlc + osmgnc) as total_angsuran')
            )
            ->groupBy('nocif')
            ->orderByDesc('total_pinjaman')
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

        // Monthly trends - Group by month from tgleff
        // Use strftime for SQLite compatibility
        // Monthly trends should show period-based data, not tgleff
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
                return round($item->plafon / 1000000000, 2); // Konversi ke miliar
            })->values()->toArray(),
            'lending' => $monthlyData->map(function ($item) {
                return round($item->outstanding / 1000000000, 2); // Konversi ke miliar
            })->values()->toArray()
        ];

        // If no data, use default
        if (empty($monthlyTrends['labels'])) {
            $monthlyTrends = [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                'funding' => [0, 0, 0, 0, 0, 0],
                'lending' => [0, 0, 0, 0, 0, 0]
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

        // Kolektibilitas Distribution (All)
        $col1 = (clone $query)->where('colbaru', '1')->count();
        $col2 = (clone $query)->where('colbaru', '2')->count();
        $col3Count = (clone $query)->where('colbaru', '3')->count();
        $col4Count = (clone $query)->where('colbaru', '4')->count();
        $col5Count = (clone $query)->where('colbaru', '5')->count();

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

        // Segmentasi distribution for pie chart
        $segmentasiDistribution = [
            'labels' => [],
            'values' => []
        ];

        foreach ($segmentasiData as $segment) {
            if (!$segment['is_total'] && $segment['outstanding'] > 0) {
                // Gunakan category untuk main category, atau gabungkan category + type untuk detail
                $label = $segment['category'];
                if ($segment['type'] && $segment['type'] !== '') {
                    $label = $segment['type'];
                }

                $segmentasiDistribution['labels'][] = $label;
                $segmentasiDistribution['values'][] = round($segment['outstanding'] / 1000000000, 2); // Konversi ke miliar
            }
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
        $topAOData = (clone $query)
            ->select(
                'nmao',
                DB::raw('COUNT(*) as total_nasabah'),
                DB::raw('SUM(osmdlc) as total_outstanding'),
                DB::raw('SUM(mdlawal) as total_plafon'),
                DB::raw('SUM(CASE WHEN colbaru >= 3 THEN osmdlc ELSE 0 END) as total_npf'),
                DB::raw('COUNT(CASE WHEN colbaru >= 3 THEN 1 END) as jumlah_npf')
            )
            ->whereNotNull('nmao')
            ->where('nmao', '!=', '')
            ->groupBy('nmao')
            ->orderBy('total_outstanding', 'desc')
            ->get()
            ->map(function ($item) {
                $npfRatio = $item->total_outstanding > 0
                    ? ($item->total_npf / $item->total_outstanding) * 100
                    : 0;

                return [
                    'nmao' => $item->nmao,
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

        // Query depositos grouped by AO with categorization
        $depositoByAO = DB::table('depositos')
            ->selectRaw("
                kodeaoh,
                SUM(CASE WHEN kdprd = '31' THEN 1 ELSE 0 END) as total_deposito,
                SUM(CASE WHEN kdprd = '41' THEN 1 ELSE 0 END) as total_abp,
                SUM(CASE WHEN kdprd = '31' THEN nomrp ELSE 0 END) as nominal_deposito,
                SUM(CASE WHEN kdprd = '41' THEN nomrp ELSE 0 END) as nominal_abp,
                SUM(CASE WHEN tgljtempo < '{$currentDate}' THEN 1 ELSE 0 END) as total_cairkan,
                SUM(CASE WHEN tgljtempo < '{$currentDate}' THEN nomrp ELSE 0 END) as nominal_cairkan
            ")
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear)
            ->where('stsrec', 'A')
            ->whereNotNull('kodeaoh')
            ->where('kodeaoh', '!=', '')
            ->groupBy('kodeaoh')
            ->get()
            ->keyBy('kodeaoh');

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

        // Build aoFundingData with deposito categorization
        $aoFundingData = collect();
        foreach ($depositoByAO as $kodeaoh => $data) {
            $aoName = $aoMapping[$kodeaoh] ?? $kodeaoh;
            $totalDeposits = ($data->total_deposito ?? 0) + ($data->total_abp ?? 0);
            $totalNominal = ($data->nominal_deposito ?? 0) + ($data->nominal_abp ?? 0);

            $aoFundingData->push([
                'kodeaoh' => $kodeaoh,
                'nmao' => $aoName,
                'total_deposito' => $data->total_deposito ?? 0,
                'total_abp' => $data->total_abp ?? 0,
                'nominal_deposito' => $data->nominal_deposito ?? 0,
                'nominal_abp' => $data->nominal_abp ?? 0,
                'total_cairkan' => $data->total_cairkan ?? 0,
                'nominal_cairkan' => $data->nominal_cairkan ?? 0,
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

        return view('dashboard', compact('funding', 'lending', 'npf', 'monthlyTrends', 'npfDistribution', 'topNpfContributors', 'collectibilityStats', 'topProducts', 'topAreas', 'segmentasiData', 'segmentasiDistribution', 'kolektibilitasDistribution', 'topProductsChart', 'portfolioSummary', 'kecamatanData', 'topAOData', 'aoFundingData', 'nasabahStatusData', 'nasabahTrendData', 'fundingDetails', 'nasabahBothFunding', 'nasabahLending', 'user'));
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
            // Ambil mdlawal untuk setiap kontrak unique dari period bulan ini
            foreach ($uniqueKontrakBaru as $nk) {
                $kontrak = Pembiayaan::where('nokontrak', $nk)
                    ->where('period_month', $filterMonthStr)
                    ->where('period_year', $filterYear)
                    ->first(['mdlawal']);
                if ($kontrak) {
                    $nominalNasabahBaru += $kontrak->mdlawal;
                }
            }
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
        $pelunasanCepat = Pembiayaan::where('period_month', $prevMonthStr)
            ->where('period_year', $prevYear)
            ->whereIn('nokontrak', $kontrakHilang)
            ->whereRaw('angs_ke < jw') // Masih banyak tenor (lunas sebelum jatuh tempo)
            ->where('jw', '>', 0)
            ->where('angs_ke', '>=', 1) // Minimal sudah 1x bayar
            ->where('osmdlc', '<=', 2000000) // Outstanding max 2 juta
            ->selectRaw('COUNT(*) as jumlah, SUM(mdlawal) as total_nominal')
            ->first();

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
                'jumlah' => $pelunasanCepat->jumlah ?? 0,
                'nominal' => $pelunasanCepat->total_nominal ?? 0,
                'nominal_miliar' => round(($pelunasanCepat->total_nominal ?? 0) / 1000000000, 2)
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
                // Ambil mdlawal untuk setiap kontrak unique dari period bulan ini
                foreach ($uniqueKontrak as $nk) {
                    $kontrak = Pembiayaan::where('nokontrak', $nk)
                        ->where('period_month', $monthStr)
                        ->where('period_year', $year)
                        ->first(['mdlawal']);
                    if ($kontrak) {
                        $nominalNasabahBaru += $kontrak->mdlawal;
                    }
                }
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
            $pelunasanCepatResult = Pembiayaan::where('period_month', $prevMonthStr)
                ->where('period_year', $prevYear)
                ->whereIn('nokontrak', $kontrakHilang)
                ->whereRaw('angs_ke < jw')
                ->where('jw', '>', 0)
                ->where('angs_ke', '>=', 1)
                ->where('osmdlc', '<=', 2000000)
                ->whereRaw("strftime('%Y-%m', tgleff) != ?", [$year . '-' . $monthStr])
                ->selectRaw('COUNT(*) as jumlah, SUM(mdlawal) as nominal')
                ->first();

            $pelunasanCepatData[] = $pelunasanCepatResult->jumlah ?? 0;
            $pelunasanCepatNominal[] = round(($pelunasanCepatResult->nominal ?? 0) / 1000000000, 2);

            // Nasabah Lunas
            $nasabahLunasResult = Pembiayaan::where('period_month', $prevMonthStr)
                ->where('period_year', $prevYear)
                ->whereIn('nokontrak', $kontrakHilang)
                ->whereRaw('angs_ke >= jw')
                ->where('jw', '>', 0)
                ->where('osmdlc', '<=', 2000000)
                ->whereRaw("strftime('%Y-%m', tgleff) != ?", [$year . '-' . $monthStr])
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

            // Ambil data untuk setiap kontrak unique dari period bulan ini
            $kontrakData = collect();
            foreach ($uniqueKontrak as $nk) {
                $data = Pembiayaan::where('nokontrak', $nk)
                    ->where('period_month', $monthStr)
                    ->where('period_year', $year)
                    ->select('nokontrak', 'nama', 'nocif', 'tgleff', 'mdlawal', 'osmdlc', 'angs_ke', 'jw', 'nmao')
                    ->first();
                if ($data) {
                    $kontrakData->push($data);
                }
            }

            // Sort by mdlawal descending
            $kontrakData = $kontrakData->sortByDesc('mdlawal')->values();

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
                    ->where('angs_ke', '>=', 1)
                    ->where('osmdlc', '<=', 2000000)
                    ->whereRaw("strftime('%Y-%m', tgleff) != ?", [$year . '-' . $monthStr])
                    ->selectRaw('DISTINCT nokontrak')
                    ->pluck('nokontrak')
                    ->toArray();

                // Ambil data untuk setiap kontrak unique
                $kontrakData = collect();
                foreach ($uniqueNokontrak as $nk) {
                    $data = Pembiayaan::where('period_month', $prevMonthStr)
                        ->where('period_year', $prevYear)
                        ->where('nokontrak', $nk)
                        ->select('nokontrak', 'nama', 'nocif', 'tgleff', 'mdlawal', 'osmdlc', 'angs_ke', 'jw', 'nmao')
                        ->first();
                    if ($data) {
                        $kontrakData->push($data);
                    }
                }
                $kontrakData = $kontrakData->sortByDesc('mdlawal')->values();
            } else {
                // Kontrak Lunas - ambil unique nokontrak dulu
                $uniqueNokontrak = Pembiayaan::where('period_month', $prevMonthStr)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $kontrakHilang)
                    ->whereRaw('angs_ke >= jw')
                    ->where('jw', '>', 0)
                    ->where('osmdlc', '<=', 2000000)
                    ->whereRaw("strftime('%Y-%m', tgleff) != ?", [$year . '-' . $monthStr])
                    ->selectRaw('DISTINCT nokontrak')
                    ->pluck('nokontrak')
                    ->toArray();

                // Ambil data untuk setiap kontrak unique
                $kontrakData = collect();
                foreach ($uniqueNokontrak as $nk) {
                    $data = Pembiayaan::where('period_month', $prevMonthStr)
                        ->where('period_year', $prevYear)
                        ->where('nokontrak', $nk)
                        ->select('nokontrak', 'nama', 'nocif', 'tgleff', 'mdlawal', 'osmdlc', 'angs_ke', 'jw', 'nmao')
                        ->first();
                    if ($data) {
                        $kontrakData->push($data);
                    }
                }
                $kontrakData = $kontrakData->sortByDesc('mdlawal')->values();
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
                    ->select('notab as norek', 'fnama as nama', 'sahirrp', 'tgltrnakh as tgleff', 'kodeprd')
                    ->orderBy('sahirrp', 'desc')
                    ->limit(100) // Limit to prevent encoding issues
                    ->get();
                $total = $data->sum('sahirrp');
            } else {
                $data = Tabungan::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('notab as norek', 'fnama as nama', 'sahirrp', 'tgltrnakh as tgleff', 'kodeprd')
                    ->limit(100)
                    ->get();
                $total = $data->count();
            }
        } elseif ($kategori === 'deposito') {
            if ($type === 'nominal') {
                $data = Deposito::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('nobilyet', 'nama', 'nomrp', 'jkwaktu as jw', 'tglbuka', 'kdprd')
                    ->orderBy('nomrp', 'desc')
                    ->limit(100)
                    ->get();
                $total = $data->sum('nomrp');
            } else {
                $data = Deposito::where('period_month', $month)
                    ->where('period_year', $year)
                    ->select('nobilyet', 'nama', 'nomrp', 'jkwaktu as jw', 'tglbuka', 'kdprd')
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
            'data' => $data->toArray(),
            'total' => $total,
            'type' => $type
        ]);
    }

    public function getTrendProductDetail(Request $request)
    {
        $jenis = $request->input('jenis'); // 'tabungan' or 'deposito'
        $type = $request->input('type', 'nominal'); // nominal or jumlah

        $data = [];

        if ($jenis === 'tabungan') {
            // Get tabungan data grouped by kodeprd and period
            $query = Tabungan::select(
                'kodeprd',
                'period_year',
                'period_month',
                DB::raw('SUM(sahirrp) as total_nominal'),
                DB::raw('SUM(linkage) as total_linkage'),
                DB::raw('COUNT(*) as total_rekening')
            )
                ->whereNotNull('kodeprd')
                ->where('kodeprd', '!=', '')
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
                    'linkage' => (float) $result->total_linkage,
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
                DB::raw('SUM(linkage) as total_linkage'),
                DB::raw('COUNT(*) as total_rekening')
            )
                ->whereNotNull('kdprd')
                ->where('kdprd', '!=', '')
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
                    'linkage' => (float) $result->total_linkage,
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
                DB::raw('SUM(linkage) as total_linkage'),
                DB::raw('COUNT(*) as total_rekening')
            )
                ->whereNotNull('kelompok')
                ->where('kelompok', '!=', '')
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
                    'linkage' => (float) $result->total_linkage,
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
            $query = DB::table(DB::raw('(
                SELECT DISTINCT period_year, period_month
                FROM depositos
                ORDER BY period_year DESC, period_month DESC
                LIMIT 12
            ) as periods'))
                ->select('period_year', 'period_month')
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

        // Build base query with combined filters
        $query = Pembiayaan::query();

        // Step 1: Filter by period_month dan period_year - WAJIB
        if ($filterMonth && $filterYear) {
            $query->where('period_month', $filterMonth);
            $query->where('period_year', $filterYear);

            // Step 2: Filter by tanggal range (tgleff) - OPSIONAL
            if ($startDay && $endDay) {
                $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
                $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '>=', $startDate)
                    ->whereDate('tgleff', '<=', $endDate);
            } elseif ($startDay) {
                $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '>=', $startDate);
            } elseif ($endDay) {
                $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '<=', $endDate);
            }
        }

        // Handle LAIN-LAIN category
        if ($category === 'LAIN-LAIN' && $type === 'Lainnya') {
            // Get all mapped codes from segment structure
            $segmentCodes = $this->getSegmentCodes();
            $mappedCodes = [];
            foreach ($segmentCodes as $segments) {
                foreach ($segments as $codes) {
                    $mappedCodes = array_merge($mappedCodes, $codes);
                }
            }

            // Query untuk LAIN-LAIN (yang tidak ada di mapping)
            $query->whereNotIn('kdgroupdeb', $mappedCodes)
                ->whereNotNull('kdgroupdeb')
                ->where('kdgroupdeb', '!=', '');
        } else {
            // Handle normal categories
            $segmentCodes = $this->getSegmentCodes();
            $codes = $segmentCodes[$category][$type] ?? [];

            if (empty($codes)) {
                return response()->json(['error' => 'Segment not found'], 404);
            }

            $query->whereIn('kdgroupdeb', $codes);
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
            'category' => $category,
            'type' => $type,
            'summary' => $summary,
            'details' => $details
        ]);
    }

    public function getSegmentasiKolDetail(Request $request, $category, $type, $kol)
    {
        // Get filter parameters
        $startDay = $request->input('start_day');
        $endDay = $request->input('end_day');
        $filterMonth = $request->input('month');
        $filterYear = $request->input('year');

        // Build base query with combined filters
        $query = Pembiayaan::query();

        // Step 1: Filter by period_month dan period_year - WAJIB
        if ($filterMonth && $filterYear) {
            $query->where('period_month', $filterMonth);
            $query->where('period_year', $filterYear);

            // Step 2: Filter by tanggal range (tgleff) - OPSIONAL
            if ($startDay && $endDay) {
                $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
                $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '>=', $startDate)
                    ->whereDate('tgleff', '<=', $endDate);
            } elseif ($startDay) {
                $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '>=', $startDate);
            } elseif ($endDay) {
                $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '<=', $endDate);
            }
        }

        // Filter by kolektibilitas
        $query->where('colbaru', $kol);

        // Handle LAIN-LAIN category
        if ($category === 'LAIN-LAIN' && $type === 'Lainnya') {
            // Get all mapped codes from segment structure
            $segmentCodes = $this->getSegmentCodes();
            $mappedCodes = [];
            foreach ($segmentCodes as $segments) {
                foreach ($segments as $codes) {
                    $mappedCodes = array_merge($mappedCodes, $codes);
                }
            }

            // Query untuk LAIN-LAIN (yang tidak ada di mapping)
            $query->whereNotIn('kdgroupdeb', $mappedCodes)
                ->whereNotNull('kdgroupdeb')
                ->where('kdgroupdeb', '!=', '');
        } else {
            // Handle normal categories
            $segmentCodes = $this->getSegmentCodes();
            $codes = $segmentCodes[$category][$type] ?? [];

            if (empty($codes)) {
                return response()->json(['error' => 'Segment not found'], 404);
            }

            $query->whereIn('kdgroupdeb', $codes);
        }

        // Get detail data
        $details = $query
            ->select('nokontrak', 'nama', 'osmdlc', 'mdlawal', 'colbaru', 'kdgroupdeb', 'nmao', 'dpd')
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
                    'dpd' => $item->dpd ?? 0
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
            'category' => $category,
            'type' => $type,
            'kol' => $kol,
            'kol_label' => $this->getCollectibilityLabel($kol),
            'summary' => $summary,
            'details' => $details
        ]);
    }

    public function getKecamatanDetail($kecamatan)
    {
        $startDay = request('start_day');
        $endDay = request('end_day');
        $filterMonth = request('month', now()->format('m'));
        $filterYear = request('year', now()->year);

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

        // Filter by kecamatan
        $query->where('kecamatan', $kecamatan);

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

        // Filter by AO name
        $query->where('nmao', $nmao);

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
                    'nmao' => $item->nmao ?? '-',
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

        // Filter by AO name and NPF (colbaru >= 3)
        $query->where('nmao', $nmao)
            ->where('colbaru', '>=', 3);

        $details = $query
            ->select('nokontrak', 'nama', 'osmdlc', 'mdlawal', 'colbaru', 'kdgroupdeb', 'nmao', 'kecamatan', 'dpd')
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
                    'nmao' => $item->nmao ?? '-',
                    'kecamatan' => $item->kecamatan,
                    'dpd' => $item->dpd ?? 0
                ];
            });

        // Get total outstanding for this AO (for NPF ratio calculation)
        $totalOutstandingAO = Pembiayaan::query()
            ->where('period_month', $filterMonth)
            ->where('period_year', $filterYear)
            ->where('nmao', $nmao)
            ->sum('osmdlc');

        $summary = [
            'total_nasabah' => $details->count(),
            'total_outstanding' => $details->sum('osmdlc'),
            'avg_outstanding' => $details->count() > 0 ? $details->avg('osmdlc') : 0,
            'total_disburse' => $details->sum('mdlawal'),
            'npf_ratio' => $totalOutstandingAO > 0 ? ($details->sum('osmdlc') / $totalOutstandingAO) * 100 : 0
        ];

        return response()->json([
            'nmao' => $nmao,
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

    private function getSegmentCodes()
    {
        return [
            'FIX INCOME' => [
                'PPPK' => ['PPPK', 'P3KDINKES', 'P3KDISDIK'],
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
                'MIKRO' => ['MIKRO', '096', 'NULL'],
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

    private function getSegmentasiData($startDay = null, $endDay = null, $filterMonth = null, $filterYear = null)
    {
        // Build base query with combined filters
        $baseQuery = function () use ($startDay, $endDay, $filterMonth, $filterYear) {
            $query = Pembiayaan::query();

            // Step 1: Filter by period_month dan period_year - WAJIB
            $query->where('period_month', $filterMonth);
            $query->where('period_year', $filterYear);

            // Step 2: Filter by tanggal range (tgleff) - OPSIONAL
            if ($startDay && $endDay) {
                $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
                $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '>=', $startDate)
                    ->whereDate('tgleff', '<=', $endDate);
            } elseif ($startDay) {
                $startDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '>=', $startDate);
            } elseif ($endDay) {
                $endDate = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($endDay, 2, '0', STR_PAD_LEFT);
                $query->whereDate('tgleff', '<=', $endDate);
            }

            return $query;
        };

        // Definisi struktur segmentasi yang akan ditampilkan
        $segmentStructure = [
            'FIX INCOME' => [
                ['label' => 'PPPK', 'codes' => ['PPPK', 'P3KDINKES', 'P3KDISDIK']],
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
                ['label' => 'MIKRO', 'codes' => ['MIKRO', '096', 'NULL']],
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
        foreach ($segmentStructure as $segments) {
            foreach ($segments as $segment) {
                $mappedCodes = array_merge($mappedCodes, $segment['codes']);
            }
        }

        $data = [];
        $grandTotalOutstanding = 0;
        $grandTotalDisburse = 0;
        $grandNoa = 0;
        $grandCif = 0;

        // Process segmentasi berdasarkan struktur yang baru
        foreach ($segmentStructure as $category => $segments) {
            $rowCount = 0;
            $categoryData = [];

            foreach ($segments as $segment) {
                // Aggregate data untuk semua kode dalam segment ini
                $result = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])
                    ->selectRaw("SUM(osmdlc) as outstanding, SUM(mdlawal) as disburse, COUNT(*) as noa, COUNT(DISTINCT nocif) as cif")
                    ->first();

                // Count and sum by collectibility
                $col1Count = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '1')->count();
                $col2Count = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '2')->count();
                $col3Count = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '3')->count();
                $col4Count = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '4')->count();
                $col5Count = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '5')->count();

                $col1Sum = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '1')->sum('osmdlc');
                $col2Sum = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '2')->sum('osmdlc');
                $col3Sum = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '3')->sum('osmdlc');
                $col4Sum = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '4')->sum('osmdlc');
                $col5Sum = $baseQuery()->whereIn('kdgroupdeb', $segment['codes'])->where('colbaru', '5')->sum('osmdlc');

                $outstanding = $result->outstanding ?? 0;
                $disburse = $result->disburse ?? 0;
                $noa = $result->noa ?? 0;
                $cif = $result->cif ?? 0;

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

        // Process LAIN-LAIN (data yang tidak masuk kategori)
        $lainResult = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)
            ->whereNotNull('kdgroupdeb')
            ->where('kdgroupdeb', '!=', '')
            ->selectRaw("SUM(osmdlc) as outstanding, SUM(mdlawal) as disburse, COUNT(*) as noa, COUNT(DISTINCT nocif) as cif")
            ->first();

        $lainCol1Count = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '1')->count();
        $lainCol2Count = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '2')->count();
        $lainCol3Count = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '3')->count();
        $lainCol4Count = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '4')->count();
        $lainCol5Count = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '5')->count();

        $lainCol1Sum = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '1')->sum('osmdlc');
        $lainCol2Sum = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '2')->sum('osmdlc');
        $lainCol3Sum = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '3')->sum('osmdlc');
        $lainCol4Sum = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '4')->sum('osmdlc');
        $lainCol5Sum = $baseQuery()->whereNotIn('kdgroupdeb', $mappedCodes)->where('colbaru', '5')->sum('osmdlc');

        $lainOutstanding = $lainResult->outstanding ?? 0;
        $lainDisburse = $lainResult->disburse ?? 0;
        $lainNoa = $lainResult->noa ?? 0;
        $lainCif = $lainResult->cif ?? 0;

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

        // Calculate total collectibility
        $totalCol1Count = $baseQuery()->where('colbaru', '1')->count();
        $totalCol2Count = $baseQuery()->where('colbaru', '2')->count();
        $totalCol3Count = $baseQuery()->where('colbaru', '3')->count();
        $totalCol4Count = $baseQuery()->where('colbaru', '4')->count();
        $totalCol5Count = $baseQuery()->where('colbaru', '5')->count();

        $totalCol1Sum = $baseQuery()->where('colbaru', '1')->sum('osmdlc');
        $totalCol2Sum = $baseQuery()->where('colbaru', '2')->sum('osmdlc');
        $totalCol3Sum = $baseQuery()->where('colbaru', '3')->sum('osmdlc');
        $totalCol4Sum = $baseQuery()->where('colbaru', '4')->sum('osmdlc');
        $totalCol5Sum = $baseQuery()->where('colbaru', '5')->sum('osmdlc');

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

        // Calculate previous month
        $prevMonth = $filterMonth - 1;
        $prevYear = $filterYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $filterYear - 1;
        }

        // Base query with filters
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
                $kontrakBulanLalu = Pembiayaan::where('period_month', $prevMonth)
                    ->where('period_year', $prevYear)
                    ->pluck('nokontrak')
                    ->toArray();

                $kontrakBulanIni = Pembiayaan::where('period_month', $filterMonth)
                    ->where('period_year', $filterYear)
                    ->pluck('nokontrak')
                    ->toArray();

                $kontrakHilang = array_diff($kontrakBulanLalu, $kontrakBulanIni);

                // Ambil data dari bulan lalu
                $query = Pembiayaan::query()
                    ->where('period_month', $prevMonth)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $kontrakHilang)
                    ->whereRaw('angs_ke < jw')
                    ->where('jw', '>', 0)
                    ->where('angs_ke', '>=', 1) // Minimal 1x bayar
                    ->where('osmdlc', '<=', 2000000) // Outstanding max 2 juta
                    ->whereRaw("strftime('%Y-%m', tgleff) != ?", [$filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT)]); // Exclude nasabah baru

                $title = 'Pelunasan Cepat (Lunas Sebelum Tenor Selesai)';
                break;

            case 'nasabah_lunas':
                // Kontrak yang ada di bulan lalu tapi hilang di bulan ini, dan tenor sudah habis
                $kontrakBulanLalu = Pembiayaan::where('period_month', $prevMonth)
                    ->where('period_year', $prevYear)
                    ->pluck('nokontrak')
                    ->toArray();

                $kontrakBulanIni = Pembiayaan::where('period_month', $filterMonth)
                    ->where('period_year', $filterYear)
                    ->pluck('nokontrak')
                    ->toArray();

                $kontrakHilang = array_diff($kontrakBulanLalu, $kontrakBulanIni);

                // Ambil data dari bulan lalu
                $query = Pembiayaan::query()
                    ->where('period_month', $prevMonth)
                    ->where('period_year', $prevYear)
                    ->whereIn('nokontrak', $kontrakHilang)
                    ->whereRaw('angs_ke >= jw')
                    ->where('jw', '>', 0)
                    ->where('osmdlc', '<=', 2000000) // Outstanding max 2 juta
                    ->whereRaw("strftime('%Y-%m', tgleff) != ?", [$filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT)]); // Exclude nasabah baru

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
            ->get();

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
            // Get customers who had deposito pencairan (deposits that disappeared)
            // This is more complex - we need to find deposits that existed in previous month but not current
            $customers = DB::table('depositos as prev')
                ->leftJoin('depositos as curr', function ($join) {
                    $join->on('prev.nobilyet', '=', 'curr.nobilyet')
                        ->whereRaw('curr.period_year * 12 + curr.period_month = prev.period_year * 12 + prev.period_month + 1');
                })
                ->whereNull('curr.nobilyet')
                ->select(
                    'prev.nama',
                    'prev.nobilyet',
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
        $currentYear = date('Y');

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

            // Get deposito data for this month - depositos that exist in this period AND were opened in this month
            $depositos = DB::table('depositos')
                ->where('kodeaoh', $kodeaoh)
                ->where('period_month', $monthStr)
                ->where('period_year', $currentYear)
                ->whereRaw("strftime('%m', tglbuka) = ?", [$monthStr])
                ->whereRaw("strftime('%Y', tglbuka) = ?", [$currentYear])
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
            ->whereRaw("strftime('%Y', tglbuka) = ?", [$currentYear])
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
        $currentYear = date('Y');

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
            // Filter by opening date (tglbuka) for monthly details
            $query->whereRaw("strftime('%m', tglbuka) = ?", [$monthStr])
                ->whereRaw("strftime('%Y', tglbuka) = ?", [$currentYear]);
        } else {
            // For "all" months, show all depositos for the year
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
                // Depositos that were acquired in this month but have been withdrawn
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
                // Get depositos acquired in this month that no longer exist in current period
                $cairkanBilyets = DB::table('depositos as acquired')
                    ->leftJoin('depositos as current', function ($join) use ($currentYear) {
                        $join->on('acquired.nobilyet', '=', 'current.nobilyet')
                            ->where('current.period_year', $currentYear)
                            ->where('current.stsrec', 'A');
                    })
                    ->where('acquired.kodeaoh', $ao)
                    ->whereRaw("strftime('%m', acquired.tglbuka) = ?", [$monthStr])
                    ->whereRaw("strftime('%Y', acquired.tglbuka) = ?", [$currentYear])
                    ->where('acquired.stsrec', 'A')
                    ->whereNull('current.nobilyet') // No longer exists in current period
                    ->pluck('acquired.nobilyet');

                $query->whereIn('nobilyet', $cairkanBilyets);
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
}
