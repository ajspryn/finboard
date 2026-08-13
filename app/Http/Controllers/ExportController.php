<?php

namespace App\Http\Controllers;

use App\Models\Deposito;
use App\Models\Linkage;
use App\Models\Pembiayaan;
use App\Models\Tabungan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function showDataExportForm()
    {
        $now = now();

        return view('exports.data-export', [
            'defaultStartPeriod' => $now->copy()->subMonthNoOverflow()->format('Y-m'),
            'defaultEndPeriod' => $now->format('Y-m'),
            'currentPeriod' => $now->format('Y-m'),
            'lastPeriod' => $now->copy()->subMonthNoOverflow()->format('Y-m'),
        ]);
    }

    public function exportSelectedData(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'data_type' => 'required|in:tabungan,funding,lending,all',
            'period_type' => 'required|in:this_month,last_month,custom_range',
            'start_period' => 'nullable|required_if:period_type,custom_range|date_format:Y-m',
            'end_period' => 'nullable|required_if:period_type,custom_range|date_format:Y-m|after_or_equal:start_period',
            'filter_field' => 'nullable|in:cif,nik,nama,rekening,hp,produk,ao,kecamatan,keyword',
            'filter_value' => 'nullable|string|max:100',
        ]);

        [$startKey, $endKey, $periodLabel] = $this->resolvePeriodRange(
            $validated['period_type'],
            $validated['start_period'] ?? null,
            $validated['end_period'] ?? null,
        );

        $dataType = $validated['data_type'];
        $filterField = $validated['filter_field'] ?? null;
        $filterValue = $validated['filter_value'] ?? null;

        $selectedDatasets = match ($dataType) {
            'tabungan' => ['tabungan'],
            'funding' => ['deposito', 'linkage'],
            'lending' => ['pembiayaan'],
            default => ['tabungan', 'deposito', 'linkage', 'pembiayaan'],
        };

        $headers = [
            'Sumber Data',
            'Periode Tahun',
            'Periode Bulan',
            'CIF',
            'NIK/No ID',
            'Nama Nasabah',
            'Nomor Referensi',
            'Produk',
            'HP',
            'AO',
            'Kecamatan',
            'Nominal',
            'Tanggal Referensi',
        ];

        $filename = sprintf(
            'export-data-%s-%s-%s.csv',
            $dataType,
            str_replace(' ', '-', strtolower($periodLabel)),
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($headers, $selectedDatasets, $startKey, $endKey, $filterField, $filterValue) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            $seenCifs = [];

            foreach ($selectedDatasets as $dataset) {
                $query = $this->buildDatasetQuery($dataset, $startKey, $endKey);
                $this->applyDatasetFilters($query, $dataset, $filterField, $filterValue);

                $query
                    ->orderByDesc('period_year')
                    ->orderByDesc('period_month')
                    ->orderByDesc('id')
                    ->chunk(1000, function ($rows) use ($output, &$seenCifs) {
                        foreach ($rows as $row) {
                            $cif = trim((string) ($row->nocif ?? ''));
                            if ($cif === '' || isset($seenCifs[$cif])) {
                                continue;
                            }

                            $seenCifs[$cif] = true;

                            fputcsv($output, [
                                $row->sumber_data,
                                $row->period_year,
                                str_pad((string) $row->period_month, 2, '0', STR_PAD_LEFT),
                                $cif,
                                $row->nik,
                                $row->nama_nasabah,
                                $row->nomor_referensi,
                                $row->produk,
                                $row->hp,
                                $row->ao,
                                $row->kecamatan,
                                $row->nominal,
                                $row->tanggal_referensi,
                            ]);
                        }
                    });
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolvePeriodRange(string $periodType, ?string $startPeriod, ?string $endPeriod): array
    {
        if ($periodType === 'this_month') {
            $current = now()->format('Y-m');
            return [$this->monthKey($current), $this->monthKey($current), 'bulan-ini'];
        }

        if ($periodType === 'last_month') {
            $last = now()->subMonthNoOverflow()->format('Y-m');
            return [$this->monthKey($last), $this->monthKey($last), 'bulan-lalu'];
        }

        $start = Carbon::createFromFormat('Y-m', (string) $startPeriod)->startOfMonth()->format('Y-m');
        $end = Carbon::createFromFormat('Y-m', (string) $endPeriod)->startOfMonth()->format('Y-m');

        return [$this->monthKey($start), $this->monthKey($end), $start . '-sd-' . $end];
    }

    private function monthKey(string $period): int
    {
        [$year, $month] = explode('-', $period);
        return ((int) $year * 100) + (int) $month;
    }

    private function applyPeriodFilter($query, int $startKey, int $endKey)
    {
        return $query->whereRaw(
            '((period_year * 100) + period_month) BETWEEN ? AND ?',
            [$startKey, $endKey]
        );
    }

    private function buildDatasetQuery(string $dataset, int $startKey, int $endKey)
    {
        if ($dataset === 'tabungan') {
            $query = Tabungan::query()->selectRaw("id, 'TABUNGAN' as sumber_data, period_year, period_month, nocif, noid as nik, fnama as nama_nasabah, notab as nomor_referensi, kodeprd as produk, hp, NULL as ao, NULL as kecamatan, sahirrp as nominal, tgltrnakh as tanggal_referensi");
            return $this->applyPeriodFilter($query, $startKey, $endKey);
        }

        if ($dataset === 'deposito') {
            $query = Deposito::query()->selectRaw("id, 'FUNDING-DEPOSITO' as sumber_data, period_year, period_month, nocif, noid as nik, nama as nama_nasabah, nodep as nomor_referensi, kdprd as produk, hp, kodeaoh as ao, kecamatan, nomrp as nominal, tgleff as tanggal_referensi");
            return $this->applyPeriodFilter($query, $startKey, $endKey);
        }

        if ($dataset === 'linkage') {
            $query = Linkage::query()->selectRaw("id, 'FUNDING-LINKAGE' as sumber_data, period_year, period_month, nocif, NULL as nik, nama as nama_nasabah, nokontrak as nomor_referensi, jnsakad as produk, NULL as hp, NULL as ao, NULL as kecamatan, os as nominal, tgleff as tanggal_referensi");
            return $this->applyPeriodFilter($query, $startKey, $endKey);
        }

        $query = Pembiayaan::query()->selectRaw("id, 'LENDING' as sumber_data, period_year, period_month, nocif, NULL as nik, nama as nama_nasabah, nokontrak as nomor_referensi, kdprd as produk, hp, nmao as ao, kecamatan, osmdlc as nominal, tgleff as tanggal_referensi");
        return $this->applyPeriodFilter($query, $startKey, $endKey);
    }

    private function applyDatasetFilters($query, string $dataset, ?string $filterField, ?string $filterValue): void
    {
        $value = trim((string) $filterValue);
        if (!$filterField || $value === '') {
            return;
        }

        if ($filterField === 'keyword') {
            $query->where(function ($sub) use ($dataset, $value) {
                $sub->where('nocif', 'like', "%{$value}%");

                if ($dataset === 'tabungan') {
                    $sub->orWhere('noid', 'like', "%{$value}%")
                        ->orWhere('fnama', 'like', "%{$value}%")
                        ->orWhere('notab', 'like', "%{$value}%")
                        ->orWhere('kodeprd', 'like', "%{$value}%");
                    return;
                }

                if ($dataset === 'deposito') {
                    $sub->orWhere('noid', 'like', "%{$value}%")
                        ->orWhere('nama', 'like', "%{$value}%")
                        ->orWhere('nodep', 'like', "%{$value}%")
                        ->orWhere('kdprd', 'like', "%{$value}%")
                        ->orWhere('kodeaoh', 'like', "%{$value}%")
                        ->orWhere('kecamatan', 'like', "%{$value}%");
                    return;
                }

                if ($dataset === 'linkage') {
                    $sub->orWhere('nama', 'like', "%{$value}%")
                        ->orWhere('nokontrak', 'like', "%{$value}%")
                        ->orWhere('jnsakad', 'like', "%{$value}%");
                    return;
                }

                $sub->orWhere('nama', 'like', "%{$value}%")
                    ->orWhere('nokontrak', 'like', "%{$value}%")
                    ->orWhere('kdprd', 'like', "%{$value}%")
                    ->orWhere('nmao', 'like', "%{$value}%")
                    ->orWhere('kecamatan', 'like', "%{$value}%");
            });

            return;
        }

        if ($filterField === 'cif') {
            $query->where('nocif', 'like', "%{$value}%");
            return;
        }

        if ($filterField === 'nik') {
            if ($dataset === 'tabungan' || $dataset === 'deposito') {
                $query->where('noid', 'like', "%{$value}%");
            } else {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        if ($filterField === 'nama') {
            $query->where($dataset === 'tabungan' ? 'fnama' : 'nama', 'like', "%{$value}%");
            return;
        }

        if ($filterField === 'rekening') {
            if ($dataset === 'tabungan') {
                $query->where('notab', 'like', "%{$value}%");
            } elseif ($dataset === 'deposito') {
                $query->where('nodep', 'like', "%{$value}%");
            } else {
                $query->where('nokontrak', 'like', "%{$value}%");
            }
            return;
        }

        if ($filterField === 'hp') {
            if ($dataset === 'tabungan' || $dataset === 'deposito' || $dataset === 'pembiayaan') {
                $query->where('hp', 'like', "%{$value}%");
            } else {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        if ($filterField === 'produk') {
            if ($dataset === 'tabungan') {
                $query->where('kodeprd', 'like', "%{$value}%");
            } elseif ($dataset === 'deposito' || $dataset === 'pembiayaan') {
                $query->where('kdprd', 'like', "%{$value}%");
            } else {
                $query->where('jnsakad', 'like', "%{$value}%");
            }
            return;
        }

        if ($filterField === 'ao') {
            if ($dataset === 'deposito') {
                $query->where('kodeaoh', 'like', "%{$value}%");
            } elseif ($dataset === 'pembiayaan') {
                $query->where('nmao', 'like', "%{$value}%");
            } else {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        if ($filterField === 'kecamatan') {
            if ($dataset === 'deposito' || $dataset === 'pembiayaan') {
                $query->where('kecamatan', 'like', "%{$value}%");
            } else {
                $query->whereRaw('1 = 0');
            }
        }
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
