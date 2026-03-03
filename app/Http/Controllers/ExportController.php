<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
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

    public function exportDashboard(Request $request)
    {
        // Get current filter parameters
        $filterMonth = $request->get('month', date('m'));
        $filterYear = $request->get('year', date('Y'));
        $startDay = $request->get('start_day');
        $endDay = $request->get('end_day');

        // Get dashboard data - same logic as DashboardController
        $funding = $this->getFundingData($filterMonth, $filterYear);
        $lending = $this->getLendingData($filterMonth, $filterYear);
        $npf = $this->getNPFData($filterMonth, $filterYear);
        $kolektibilitasComparison = $this->getKolektibilitasData($filterMonth, $filterYear);
        $topTabunganProducts = $this->getTopTabunganProducts($filterMonth, $filterYear);
        $topAOData = $this->getTopAOData($filterMonth, $filterYear);
        $kecamatanData = $this->getKecamatanData($filterMonth, $filterYear);
        $segmentasiData = $this->getSegmentasiData($filterMonth, $filterYear);
        $nasabahLending = $this->getNasabahLendingData($filterMonth, $filterYear);

        // Get financial highlights data
        $financialHighlights = $this->getFinancialHighlightsData($filterMonth, $filterYear);

        // Get last updated timestamps
        $lastUpdated = $this->getLastUpdatedData();

        $data = [
            'funding' => $funding,
            'lending' => $lending,
            'npf' => $npf,
            'kolektibilitasComparison' => $kolektibilitasComparison,
            'topTabunganProducts' => $topTabunganProducts,
            'topAOData' => $topAOData,
            'kecamatanData' => $kecamatanData,
            'segmentasiData' => $segmentasiData,
            'nasabahLending' => $nasabahLending,
            'financialHighlights' => $financialHighlights,
            'lastUpdated' => $lastUpdated,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
            'startDay' => $startDay,
            'endDay' => $endDay,
            'exportDate' => now()->format('d M Y H:i'),
            'user' => Auth::user(),
        ];

        // Generate PDF
        $pdf = PDF::loadView('exports.dashboard-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        // Set PDF options for better rendering
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isJavascriptEnabled' => false,
            'dpi' => 96,
            'defaultPaperSize' => 'a4',
        ]);

        $filename = 'dashboard-finboard-' . date('Y-m-d-H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    // Helper methods to get dashboard data - same as DashboardController
    private function getFundingData($month, $year)
    {
        // Get total funding
        $totalFunding = DB::table('tabungans')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->sum('sahirrp');

        // Get funding growth (compare with previous month)
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;

        $prevTotalFunding = DB::table('tabungans')
            ->where('period_month', $prevMonth)
            ->where('period_year', $prevYear)
            ->sum('sahirrp');

        $growth = $prevTotalFunding > 0 ? (($totalFunding - $prevTotalFunding) / $prevTotalFunding) * 100 : 0;

        // Get top products
        $topTabunganProducts = DB::table('tabungans')
            ->select('nama_produk', DB::raw('COUNT(*) as jumlah_rekening'), DB::raw('SUM(sahirrp) as total_nominal'))
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->whereNotNull('nama_produk')
            ->groupBy('nama_produk')
            ->orderBy('total_nominal', 'desc')
            ->limit(5)
            ->get();

        // Get deposit withdrawal data
        $pencairan = DB::table('depositos')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('kdprd', '!=', '41') // Exclude ABP
            ->selectRaw('COUNT(*) as jumlah, SUM(nomrp) as total')
            ->first();

        $prevPencairan = DB::table('depositos')
            ->where('period_month', $prevMonth)
            ->where('period_year', $prevYear)
            ->where('kdprd', '!=', '41')
            ->selectRaw('COUNT(*) as jumlah, SUM(nomrp) as total')
            ->first();

        $pencairanGrowth = $prevPencairan && $prevPencairan->total > 0
            ? (($pencairan->total ?? 0) - $prevPencairan->total) / $prevPencairan->total * 100
            : 0;

        return [
            'total' => $totalFunding,
            'growth' => round($growth, 1),
            'pencairan' => [
                'jumlah' => $pencairan->jumlah ?? 0,
                'total' => $pencairan->total ?? 0,
                'growth' => round($pencairanGrowth, 1),
            ],
            'top_products' => $topTabunganProducts,
        ];
    }

    private function getLendingData($month, $year)
    {
        $lendingData = DB::table('pembiayaans')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->selectRaw('
                COUNT(*) as nasabah,
                SUM(plafon) as plafon_awal,
                SUM(osmdlc) as total,
                AVG(mgnawal) as rate_flat,
                AVG(mgnawal + (mgnawal * 0.1)) as rate_eff
            ')
            ->first();

        return [
            'total' => $lendingData->total ?? 0,
            'plafon_awal' => $lendingData->plafon_awal ?? 0,
            'nasabah' => $lendingData->nasabah ?? 0,
            'rate_flat' => round($lendingData->rate_flat ?? 0, 2),
            'rate_eff' => round($lendingData->rate_eff ?? 0, 2),
        ];
    }

    private function getNPFData($month, $year)
    {
        $npfData = DB::table('pembiayaans')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->selectRaw('
                SUM(CASE WHEN colbaru IN (2,3,4,5) THEN osmdlc ELSE 0 END) as total_npf,
                SUM(CASE WHEN colbaru IN (2,3,4,5) THEN (osmdlc - osmgnc) ELSE 0 END) as tunggakan_pokok,
                SUM(osmdlc) as total_outstanding
            ')
            ->first();

        $npfRatio = $npfData->total_outstanding > 0
            ? ($npfData->total_npf / $npfData->total_outstanding) * 100
            : 0;

        return [
            'ratio' => round($npfRatio, 2),
            'total' => $npfData->total_npf ?? 0,
            'tunggakan_pokok' => $npfData->tunggakan_pokok ?? 0,
        ];
    }

    private function getKolektibilitasData($month, $year)
    {
        return DB::table('pembiayaans')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->selectRaw('
                colbaru as kategori,
                CASE
                    WHEN colbaru = 1 THEN "Lancar"
                    WHEN colbaru = 2 THEN "Dalam Perhatian Khusus"
                    WHEN colbaru = 3 THEN "Kurang Lancar"
                    WHEN colbaru = 4 THEN "Diragukan"
                    WHEN colbaru = 5 THEN "Macet"
                    ELSE "Unknown"
                END as nama_kategori,
                COUNT(*) as jumlah_nasabah,
                SUM(osmdlc) as total_outstanding
            ')
            ->groupBy('colbaru')
            ->orderBy('colbaru')
            ->get()
            ->map(function ($item) {
                return [
                    'kategori' => $item->kategori,
                    'nama_kategori' => $item->nama_kategori,
                    'jumlah_nasabah' => $item->jumlah_nasabah,
                    'total_outstanding' => $item->total_outstanding,
                ];
            });
    }

    private function getTopTabunganProducts($month, $year)
    {
        return DB::table('tabungans')
            ->select('kodeprd', DB::raw('SUM(sahirrp) as total_nominal'), DB::raw('COUNT(*) as jumlah_rekening'))
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->whereNotNull('kodeprd')
            ->where('kodeprd', '!=', '')
            ->groupBy('kodeprd')
            ->orderBy('total_nominal', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                // Mapping kode produk ke nama produk (same as DashboardController)
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
    }

    private function getTopAOData($month, $year)
    {
        return DB::table('pembiayaans')
            ->select(
                'kdaoh',
                DB::raw('COUNT(*) as total_nasabah'),
                DB::raw('SUM(osmdlc) as total_outstanding'),
                DB::raw('SUM(mdlawal) as total_plafon'),
                DB::raw('SUM(CASE WHEN colbaru >= 3 THEN osmdlc ELSE 0 END) as total_npf'),
                DB::raw('COUNT(CASE WHEN colbaru >= 3 THEN 1 END) as jumlah_npf')
            )
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->whereNotNull('kdaoh')
            ->where('kdaoh', '!=', '')
            ->groupBy('kdaoh')
            ->orderBy('total_outstanding', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $npfRatio = $item->total_outstanding > 0
                    ? ($item->total_npf / $item->total_outstanding) * 100
                    : 0;

                return [
                    'ao_key' => $item->kdaoh,
                    'nmao' => $this->getAoDisplayName($item->kdaoh),
                    'total_nasabah' => $item->total_nasabah,
                    'total_outstanding' => $item->total_outstanding,
                    'total_plafon' => $item->total_plafon,
                    'total_npf' => $item->total_npf,
                    'jumlah_npf' => $item->jumlah_npf,
                    'npf_ratio' => round($npfRatio, 2),
                    'outstanding_miliar' => round($item->total_outstanding / 1000000000, 2)
                ];
            });
    }

    private function getKecamatanData($month, $year)
    {
        return DB::table('pembiayaans')
            ->join('tabungans', function ($join) use ($month, $year) {
                $join->on('pembiayaans.nocif', '=', 'tabungans.nocif')
                    ->where('tabungans.period_month', $month)
                    ->where('tabungans.period_year', $year);
            })
            ->where('pembiayaans.period_month', $month)
            ->where('pembiayaans.period_year', $year)
            ->selectRaw('
                pembiayaans.kecamatan,
                COUNT(DISTINCT pembiayaans.nocif) as jumlah_nasabah,
                SUM(pembiayaans.osmdlc) as total_outstanding,
                AVG(tabungans.sahirrp) as avg_tabungan
            ')
            ->whereNotNull('pembiayaans.kecamatan')
            ->groupBy('pembiayaans.kecamatan')
            ->orderBy('total_outstanding', 'desc')
            ->limit(10)
            ->get();
    }

    private function getSegmentasiData($month, $year)
    {
        return DB::table('pembiayaans')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->selectRaw('
                kdgroupdeb as segmentasi,
                COUNT(*) as jumlah_nasabah,
                SUM(osmdlc) as outstanding,
                SUM(plafon) as disbursement
            ')
            ->groupBy('kdgroupdeb')
            ->orderBy('outstanding', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'segmentasi' => $item->segmentasi,
                    'jumlah_nasabah' => $item->jumlah_nasabah,
                    'outstanding' => $item->outstanding,
                    'disbursement' => $item->disbursement,
                    'is_total' => false,
                ];
            });
    }

    private function getNasabahLendingData($month, $year)
    {
        return DB::table('pembiayaans')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->select('nocif', 'nama', 'osmdlc', 'plafon', 'nmao')
            ->orderBy('osmdlc', 'desc')
            ->limit(50)
            ->get();
    }

    private function getFinancialHighlightsData($month, $year)
    {
        return DB::table('financial_highlights')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->first();
    }

    private function getLastUpdatedData()
    {
        return [
            'tabungan' => DB::table('tabungans')->max('updated_at'),
            'pembiayaan' => DB::table('pembiayaans')->max('updated_at'),
            'deposito' => DB::table('depositos')->max('updated_at'),
            'financial_highlight' => DB::table('financial_highlights')->max('updated_at'),
        ];
    }
}
