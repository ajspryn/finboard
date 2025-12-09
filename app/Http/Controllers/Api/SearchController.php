<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembiayaan;
use App\Models\Tabungan;
use App\Models\Deposito;
use App\Models\FinancialHighlight;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * Search pembiayaan (financing) data
     */
    public function searchPembiayaan(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'period_year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'period_month' => 'nullable|integer|min:1|max:12',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->get('q');
        $limit = $request->get('limit', 20);
        $filters = [];

        // Add period filters if provided
        if ($request->has('period_year')) {
            $filters[] = ['term' => ['period_year' => $request->get('period_year')]];
        }
        if ($request->has('period_month')) {
            $filters[] = ['term' => ['period_month' => $request->get('period_month')]];
        }

        $options = [
            'size' => $limit,
            'filters' => $filters,
        ];

        $results = Pembiayaan::search($query, $options);

        return response()->json([
            'success' => true,
            'query' => $query,
            'total' => $results['total'],
            'results' => collect($results['hits'])->map(function ($pembiayaan) {
                return [
                    'id' => $pembiayaan->id,
                    'nokontrak' => $pembiayaan->nokontrak,
                    'nama' => $pembiayaan->nama,
                    'nmao' => $pembiayaan->nmao,
                    'alamat' => $pembiayaan->alamat,
                    'sahirrp' => $pembiayaan->sahirrp,
                    'colbaru' => $pembiayaan->colbaru,
                    'period_year' => $pembiayaan->period_year,
                    'period_month' => $pembiayaan->period_month,
                    'score' => $pembiayaan->_score ?? null,
                ];
            }),
        ]);
    }

    /**
     * Search tabungan (savings) data
     */
    public function searchTabungan(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'period_year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'period_month' => 'nullable|integer|min:1|max:12',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->get('q');
        $limit = $request->get('limit', 20);
        $filters = [];

        // Add period filters if provided
        if ($request->has('period_year')) {
            $filters[] = ['term' => ['period_year' => $request->get('period_year')]];
        }
        if ($request->has('period_month')) {
            $filters[] = ['term' => ['period_month' => $request->get('period_month')]];
        }

        $options = [
            'size' => $limit,
            'filters' => $filters,
        ];

        $results = Tabungan::search($query, $options);

        return response()->json([
            'success' => true,
            'query' => $query,
            'total' => $results['total'],
            'results' => collect($results['hits'])->map(function ($tabungan) {
                return [
                    'id' => $tabungan->id,
                    'notab' => $tabungan->notab,
                    'nocif' => $tabungan->nocif,
                    'fnama' => $tabungan->fnama,
                    'namaqq' => $tabungan->namaqq,
                    'sahirrp' => $tabungan->sahirrp,
                    'stsrec' => $tabungan->stsrec,
                    'period_year' => $tabungan->period_year,
                    'period_month' => $tabungan->period_month,
                    'score' => $tabungan->_score ?? null,
                ];
            }),
        ]);
    }

    /**
     * Search deposito (deposit) data
     */
    public function searchDeposito(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'period_year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'period_month' => 'nullable|integer|min:1|max:12',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->get('q');
        $limit = $request->get('limit', 20);
        $filters = [];

        // Add period filters if provided
        if ($request->has('period_year')) {
            $filters[] = ['term' => ['period_year' => $request->get('period_year')]];
        }
        if ($request->has('period_month')) {
            $filters[] = ['term' => ['period_month' => $request->get('period_month')]];
        }

        $options = [
            'size' => $limit,
            'filters' => $filters,
        ];

        $results = Deposito::search($query, $options);

        return response()->json([
            'success' => true,
            'query' => $query,
            'total' => $results['total'],
            'results' => collect($results['hits'])->map(function ($deposito) {
                return [
                    'id' => $deposito->id,
                    'nodep' => $deposito->nodep,
                    'nocif' => $deposito->nocif,
                    'nama' => $deposito->nama,
                    'nomrp' => $deposito->nomrp,
                    'stsrec' => $deposito->stsrec,
                    'kodeaoh' => $deposito->kodeaoh,
                    'period_year' => $deposito->period_year,
                    'period_month' => $deposito->period_month,
                    'score' => $deposito->_score ?? null,
                ];
            }),
        ]);
    }

    /**
     * Search financial highlights data
     */
    public function searchFinancialHighlights(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|min:1|max:100',
            'period_year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'period_month' => 'nullable|integer|min:1|max:12',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->get('q', '');
        $limit = $request->get('limit', 20);
        $filters = [];

        // Add period filters if provided
        if ($request->has('period_year')) {
            $filters[] = ['term' => ['period_year' => $request->get('period_year')]];
        }
        if ($request->has('period_month')) {
            $filters[] = ['term' => ['period_month' => $request->get('period_month')]];
        }

        $options = [
            'size' => $limit,
            'filters' => $filters,
        ];

        $results = FinancialHighlight::search($query ?: '*', $options);

        return response()->json([
            'success' => true,
            'query' => $query,
            'total' => $results['total'],
            'results' => collect($results['hits'])->map(function ($highlight) {
                return [
                    'id' => $highlight->id,
                    'period_year' => $highlight->period_year,
                    'period_month' => $highlight->period_month,
                    'car' => $highlight->car,
                    'roa' => $highlight->roa,
                    'roe' => $highlight->roe,
                    'aset' => $highlight->aset,
                    'pembiayaan' => $highlight->pembiayaan,
                    'laba_rugi' => $highlight->laba_rugi,
                    'biaya' => $highlight->biaya,
                    'pendapatan' => $highlight->pendapatan,
                    'dpk' => $highlight->dpk,
                    'fdr' => $highlight->fdr,
                    'npf' => $highlight->npf,
                    'bopo' => $highlight->bopo,
                    'cash_ratio' => $highlight->cash_ratio,
                    'score' => $highlight->_score ?? null,
                ];
            }),
        ]);
    }

    /**
     * Universal search across all indices
     */
    public function searchAll(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'type' => 'nullable|in:pembiayaan,tabungan,deposito,financial_highlight',
            'period_year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'period_month' => 'nullable|integer|min:1|max:12',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->get('q');
        $type = $request->get('type');
        $limit = $request->get('limit', 10);

        $results = [];

        // Search specific type or all types
        $searchTypes = $type ? [$type] : ['pembiayaan', 'tabungan', 'deposito', 'financial_highlight'];

        foreach ($searchTypes as $searchType) {
            $filters = [];

            // Add period filters if provided
            if ($request->has('period_year')) {
                $filters[] = ['term' => ['period_year' => $request->get('period_year')]];
            }
            if ($request->has('period_month')) {
                $filters[] = ['term' => ['period_month' => $request->get('period_month')]];
            }

            $options = [
                'size' => $limit,
                'filters' => $filters,
            ];

            switch ($searchType) {
                case 'pembiayaan':
                    $searchResults = Pembiayaan::search($query, $options);
                    $results['pembiayaan'] = [
                        'total' => $searchResults['total'],
                        'results' => collect($searchResults['hits'])->take(5)->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'nokontrak' => $item->nokontrak,
                                'nama' => $item->nama,
                                'type' => 'pembiayaan',
                                'score' => $item->_score ?? null,
                            ];
                        }),
                    ];
                    break;

                case 'tabungan':
                    $searchResults = Tabungan::search($query, $options);
                    $results['tabungan'] = [
                        'total' => $searchResults['total'],
                        'results' => collect($searchResults['hits'])->take(5)->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'notab' => $item->notab,
                                'fnama' => $item->fnama,
                                'type' => 'tabungan',
                                'score' => $item->_score ?? null,
                            ];
                        }),
                    ];
                    break;

                case 'deposito':
                    $searchResults = Deposito::search($query, $options);
                    $results['deposito'] = [
                        'total' => $searchResults['total'],
                        'results' => collect($searchResults['hits'])->take(5)->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'nodep' => $item->nodep,
                                'nama' => $item->nama,
                                'type' => 'deposito',
                                'score' => $item->_score ?? null,
                            ];
                        }),
                    ];
                    break;

                case 'financial_highlight':
                    $searchResults = FinancialHighlight::search($query ?: '*', $options);
                    $results['financial_highlight'] = [
                        'total' => $searchResults['total'],
                        'results' => collect($searchResults['hits'])->take(5)->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'period' => $item->period_year . '-' . str_pad($item->period_month, 2, '0', STR_PAD_LEFT),
                                'type' => 'financial_highlight',
                                'score' => $item->_score ?? null,
                            ];
                        }),
                    ];
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Get pembiayaan detail by ID
     */
    public function getPembiayaanDetail($id): JsonResponse
    {
        $pembiayaan = Pembiayaan::find($id);

        if (!$pembiayaan) {
            return response()->json([
                'success' => false,
                'message' => 'Data pembiayaan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pembiayaan
        ]);
    }

    /**
     * Get tabungan detail by ID
     */
    public function getTabunganDetail($id): JsonResponse
    {
        $tabungan = Tabungan::find($id);

        if (!$tabungan) {
            return response()->json([
                'success' => false,
                'message' => 'Data tabungan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tabungan
        ]);
    }

    /**
     * Get deposito detail by ID
     */
    public function getDepositoDetail($id): JsonResponse
    {
        $deposito = Deposito::find($id);

        if (!$deposito) {
            return response()->json([
                'success' => false,
                'message' => 'Data deposito tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $deposito
        ]);
    }

    /**
     * Get financial highlight detail by ID
     */
    public function getFinancialHighlightDetail($id): JsonResponse
    {
        $highlight = FinancialHighlight::find($id);

        if (!$highlight) {
            return response()->json([
                'success' => false,
                'message' => 'Data financial highlight tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $highlight
        ]);
    }
}
