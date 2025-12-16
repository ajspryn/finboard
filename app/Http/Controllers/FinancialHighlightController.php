<?php

namespace App\Http\Controllers;

use App\Models\FinancialHighlight;
use App\Services\FinancialCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialHighlightController extends Controller
{
    protected $cacheService;

    public function __construct(FinancialCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    /**
     * Display financial highlights management page
     */
    public function index()
    {
        $highlights = FinancialHighlight::orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->paginate(12);

        return view('financial-highlights.index', compact('highlights'));
    }

    /**
     * Show the form for creating a new financial highlight
     */
    public function create()
    {
        return view('financial-highlights.create');
    }

    /**
     * Store a newly created financial highlight
     */
    public function store(Request $request)
    {
        $request->validate([
            'period_year' => 'required|integer|min:2020|max:2030',
            'period_month' => 'required|integer|min:1|max:12',
            'car' => 'nullable|numeric|min:0|max:100',
            'roa' => 'nullable|numeric|min:-100|max:100',
            'roe' => 'nullable|numeric|min:-100|max:100',
            'aset' => 'nullable|numeric|min:0',
            'pembiayaan' => 'nullable|numeric|min:0',
            'laba_rugi' => 'nullable|numeric',
            'biaya' => 'nullable|numeric',
            'pendapatan' => 'nullable|numeric',
            'dpk' => 'nullable|numeric|min:0',
            'fdr' => 'nullable|numeric|min:0',
            'npf' => 'nullable|numeric|min:0|max:100',
            'bopo' => 'nullable|numeric|min:0|max:200',
        ]);

        // Check if period already exists
        $existing = FinancialHighlight::where('period_year', $request->period_year)
            ->where('period_month', $request->period_month)
            ->first();

        if ($existing) {
            return back()->withErrors(['period' => 'Data untuk periode ini sudah ada.'])->withInput();
        }

        $data = $request->all();

        // Calculate auto-calculated values (pembiayaan, dpk, npf, fdr)
        $autoCalculatedFields = ['dpk', 'pembiayaan', 'npf', 'fdr'];
        foreach ($autoCalculatedFields as $field) {
            if (empty($data[$field])) {
                $method = 'calculate' . ucfirst($field);
                $data[$field] = FinancialHighlight::$method($data['period_year'], $data['period_month']);
            }
        }

        FinancialHighlight::create($data);

        return redirect()->route('financial-highlights.index')
            ->with('success', 'Data financial highlight berhasil ditambahkan.');
    }

    /**
     * Show the form for editing financial highlight
     */
    public function edit(FinancialHighlight $financialHighlight)
    {
        return view('financial-highlights.edit', compact('financialHighlight'));
    }

    /**
     * Update financial highlight
     */
    public function update(Request $request, FinancialHighlight $financialHighlight)
    {
        \Log::info('FinancialHighlight update called', [
            'id' => $financialHighlight->id,
            'request_data' => $request->all(),
            'method' => $request->method(),
        ]);

        $request->validate([
            'car' => 'nullable|numeric|min:0|max:100',
            'roa' => 'nullable|numeric|min:-100|max:100',
            'roe' => 'nullable|numeric|min:-100|max:100',
            'aset' => 'nullable|numeric|min:0',
            'pembiayaan' => 'nullable|numeric|min:0',
            'laba_rugi' => 'nullable|numeric',
            'biaya' => 'nullable|numeric',
            'pendapatan' => 'nullable|numeric',
            'dpk' => 'nullable|numeric|min:0',
            'fdr' => 'nullable|numeric|min:0',
            'npf' => 'nullable|numeric|min:0|max:100',
            'bopo' => 'nullable|numeric|min:0|max:200',
            'cash_ratio' => 'nullable|numeric|min:0|max:200',
        ]);

        \Log::info('Validation passed');

        $data = $request->all();

        // Always recalculate auto-calculated values based on latest data
        $autoCalculatedFields = ['dpk', 'pembiayaan', 'npf', 'fdr'];
        foreach ($autoCalculatedFields as $field) {
            $method = 'calculate' . ucfirst($field);
            $data[$field] = FinancialHighlight::$method($financialHighlight->period_year, $financialHighlight->period_month);
            \Log::info("Recalculated {$field}: {$data[$field]}");
        }

        \Log::info('About to update with data', $data);

        try {
            $financialHighlight->update($data);
            \Log::info('Update completed successfully');
        } catch (\Exception $e) {
            \Log::error('Update failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'id' => $financialHighlight->id
            ]);
            return back()->withErrors(['update' => 'Gagal memperbarui data: ' . $e->getMessage()]);
        }

        \Log::info('Redirecting to index with success message');
        return redirect()->route('financial-highlights.index')
            ->with('success', 'Data financial highlight berhasil diperbarui.');
    }

    /**
     * Delete financial highlight
     */
    public function destroy(FinancialHighlight $financialHighlight)
    {
        $financialHighlight->delete();

        return redirect()->route('financial-highlights.index')
            ->with('success', 'Data financial highlight berhasil dihapus.');
    }

    /**
     * Get financial highlights data for dashboard
     */
    public function getDashboardData(Request $request)
    {
        try {
            $comparisonType = $request->get('comparison', 'MOM'); // MOM or YOY
            $filterMonth = $request->get('month');
            $filterYear = $request->get('year');

            // Try to get from cache first
            $cachedData = $this->cacheService->getFinancialHighlights($filterYear, $filterMonth, $comparisonType);

            if ($cachedData) {
                return response()->json($cachedData);
            }

            // If not in cache, calculate and cache
            // Get data for the specified period or latest if no filters
            $query = FinancialHighlight::query();

            if ($filterMonth && $filterYear) {
                $query->where('period_month', $filterMonth)
                    ->where('period_year', $filterYear);
            }

            $latest = $query->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc')
                ->first();

            // If filtered period has no data, fallback to latest available data
            if (!$latest && $filterMonth && $filterYear) {
                $latest = FinancialHighlight::orderBy('period_year', 'desc')
                    ->orderBy('period_month', 'desc')
                    ->first();
            }

            if (!$latest) {
                return response()->json([
                    'data' => null,
                    'comparison' => null,
                    'changes' => [],
                    'comparison_type' => $comparisonType,
                    'period' => null
                ]);
            }

            // Get comparison data
            $comparison = FinancialHighlight::getPreviousPeriod(
                $latest->period_year,
                $latest->period_month,
                $comparisonType
            );

            // Calculate percentage changes
            $changes = [];
            $fields = ['car', 'roa', 'roe', 'aset', 'pembiayaan', 'laba_rugi', 'biaya', 'pendapatan', 'dpk', 'fdr', 'npf', 'bopo', 'cash_ratio'];

            foreach ($fields as $field) {
                $changes[$field] = $latest->getPercentageChange($field, $comparison);
            }

            // Prepare data with calculated fields
            $data = $latest->toArray();
            $calculatedFields = ['dpk', 'pembiayaan', 'npf', 'aset', 'fdr'];
            foreach ($calculatedFields as $field) {
                if ($data[$field] === null) {
                    $data[$field] = $latest->getCalculatedField($field);
                }
            }

            // Ensure all required fields have values
            $requiredFields = ['car', 'roa', 'roe', 'aset', 'pembiayaan', 'laba_rugi', 'biaya', 'pendapatan', 'dpk', 'fdr', 'npf', 'bopo', 'cash_ratio'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === null) {
                    $data[$field] = 0;
                }
            }

            // Prepare comparison data with calculated fields
            $comparisonData = null;
            if ($comparison) {
                $comparisonData = $comparison->toArray();
                foreach ($calculatedFields as $field) {
                    if ($comparisonData[$field] === null) {
                        $comparisonData[$field] = $comparison->getCalculatedField($field);
                    }
                }

                // Ensure all required fields have values in comparison data
                foreach ($requiredFields as $field) {
                    if (!isset($comparisonData[$field]) || $comparisonData[$field] === null) {
                        $comparisonData[$field] = 0;
                    }
                }
            }

            return response()->json([
                'data' => $data,
                'comparison' => $comparisonData,
                'changes' => $changes,
                'comparison_type' => $comparisonType,
                'period' => $latest->period_year . '-' . str_pad($latest->period_month, 2, '0', STR_PAD_LEFT)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getDashboardData: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'user_role' => auth()->user()->role,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat memuat data financial highlights',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate auto-calculated financial metrics for a specific period
     * Returns: pembiayaan, dpk, npf, fdr
     */
    public function calculateDerivedValues(Request $request)
    {
        $year = $request->get('year');
        $month = $request->get('month');

        if (!$year || !$month) {
            return response()->json(['error' => 'Year and month are required'], 400);
        }

        return response()->json([
            'pembiayaan' => FinancialHighlight::calculatePembiayaan($year, $month),
            'dpk' => FinancialHighlight::calculateDpk($year, $month),
            'npf' => FinancialHighlight::calculateNpf($year, $month),
            'fdr' => FinancialHighlight::calculateFdr($year, $month),
        ]);
    }
}
