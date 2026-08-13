<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\FinancialHighlight;

class FinancialCacheService
{
    /**
     * Cache TTL in minutes
     */
    const CACHE_TTL = 60; // 1 hour
    const DASHBOARD_CACHE_TTL = 30; // 30 minutes for dashboard data

    /**
     * Cache keys
     */
    const CACHE_PREFIX = 'financial:';
    const DASHBOARD_RENDER_VERSION_KEY = 'dashboard:render:version';

    /**
     * Get cached financial highlights data
     */
    public function getFinancialHighlights(?int $year = null, ?int $month = null, string $comparison = 'MOM'): ?array
    {
        $cacheKey = $this->buildCacheKey('highlights', [
            'year' => $year,
            'month' => $month,
            'comparison' => $comparison
        ]);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month, $comparison) {
            try {
                // Get data directly from database instead of calling controller to avoid circular dependency
                $query = \App\Models\FinancialHighlight::query();

                if ($month && $year) {
                    $query->where('period_month', $month)
                        ->where('period_year', $year);
                }

                $latest = $query->orderBy('period_year', 'desc')
                    ->orderBy('period_month', 'desc')
                    ->first();

                // If filtered period has no data, fallback to latest available data
                if (!$latest && $month && $year) {
                    $latest = \App\Models\FinancialHighlight::orderBy('period_year', 'desc')
                        ->orderBy('period_month', 'desc')
                        ->first();
                }

                if (!$latest) {
                    return [
                        'data' => null,
                        'comparison' => null,
                        'changes' => [],
                        'comparison_type' => $comparison,
                        'period' => null
                    ];
                }

                // Get comparison data
                $comparisonData = \App\Models\FinancialHighlight::getPreviousPeriod(
                    $latest->period_year,
                    $latest->period_month,
                    $comparison
                );

                // Calculate percentage changes
                $changes = [];
                $fields = ['car', 'roa', 'roe', 'aset', 'pembiayaan', 'laba_rugi', 'biaya', 'pendapatan', 'dpk', 'fdr', 'npf', 'bopo', 'cash_ratio'];

                foreach ($fields as $field) {
                    $changes[$field] = $latest->getPercentageChange($field, $comparisonData);
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
                $comparisonResult = null;
                if ($comparisonData) {
                    $comparisonResult = $comparisonData->toArray();
                    foreach ($calculatedFields as $field) {
                        if ($comparisonResult[$field] === null) {
                            $comparisonResult[$field] = $comparisonData->getCalculatedField($field);
                        }
                    }

                    // Ensure all required fields have values in comparison data
                    foreach ($requiredFields as $field) {
                        if (!isset($comparisonResult[$field]) || $comparisonResult[$field] === null) {
                            $comparisonResult[$field] = 0;
                        }
                    }
                }

                return [
                    'data' => $data,
                    'comparison' => $comparisonResult,
                    'changes' => $changes,
                    'comparison_type' => $comparison,
                    'period' => $latest->period_year . '-' . str_pad($latest->period_month, 2, '0', STR_PAD_LEFT)
                ];
            } catch (\Exception $e) {
                Log::error('Failed to cache financial highlights', [
                    'error' => $e->getMessage(),
                    'year' => $year,
                    'month' => $month
                ]);
                return null;
            }
        });
    }

    /**
     * Get cached dashboard KPI data
     */
    public function getDashboardKPIs(int $year, int $month): ?array
    {
        $cacheKey = $this->buildCacheKey('dashboard_kpis', [
            'year' => $year,
            'month' => $month
        ]);

        return Cache::remember($cacheKey, self::DASHBOARD_CACHE_TTL, function () use ($year, $month) {
            try {
                // Calculate KPIs from database
                $funding = $this->calculateFundingData($year, $month);
                $lending = $this->calculateLendingData($year, $month);
                $npf = $this->calculateNPFData($year, $month);

                return [
                    'funding' => $funding,
                    'lending' => $lending,
                    'npf' => $npf,
                    'filterMonth' => $month,
                    'filterYear' => $year,
                    'cached_at' => now()->toISOString()
                ];
            } catch (\Exception $e) {
                Log::error('Failed to cache dashboard KPIs', [
                    'error' => $e->getMessage(),
                    'year' => $year,
                    'month' => $month
                ]);
                return null;
            }
        });
    }

    /**
     * Cache calculated financial metrics
     */
    public function cacheCalculatedMetrics(int $year, int $month): void
    {
        $cacheKey = $this->buildCacheKey('calculated_metrics', [
            'year' => $year,
            'month' => $month
        ]);

        Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month) {
            return [
                'dpk' => FinancialHighlight::calculateDpk($year, $month),
                'pembiayaan' => FinancialHighlight::calculatePembiayaan($year, $month),
                'npf' => FinancialHighlight::calculateNpf($year, $month),
                'aset' => FinancialHighlight::calculateAset($year, $month),
                'fdr' => FinancialHighlight::calculateFdr($year, $month),
                'calculated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Get cached calculated metrics
     */
    public function getCalculatedMetrics(int $year, int $month): ?array
    {
        $cacheKey = $this->buildCacheKey('calculated_metrics', [
            'year' => $year,
            'month' => $month
        ]);

        return Cache::get($cacheKey);
    }

    /**
     * Clear all financial caches
     */
    public function clearAllCaches(): void
    {
        try {
            // Try to use cache tags if supported
            if (method_exists(Cache::store(), 'tags')) {
                Cache::tags(['financial'])->flush();
            } else {
                // Fallback: flush entire cache if tags not supported
                Cache::flush();
            }
            Log::info('All financial caches cleared');
        } catch (\Exception $e) {
            // If cache clearing fails, log the error but don't break the process
            Log::warning('Failed to clear financial caches: ' . $e->getMessage());
        }
    }

    /**
     * Get the current dashboard render cache version.
     */
    public function getDashboardRenderVersion(): int
    {
        return (int) Cache::get(self::DASHBOARD_RENDER_VERSION_KEY, 1);
    }

    /**
     * Invalidate cached dashboard renders without flushing the whole cache store.
     */
    public function invalidateDashboardRenderCache(): int
    {
        $version = $this->getDashboardRenderVersion() + 1;
        Cache::forever(self::DASHBOARD_RENDER_VERSION_KEY, $version);
        Cache::forget('dashboard:latest-period');

        return $version;
    }

    /**
     * Clear cache for specific period
     */
    public function clearPeriodCache(int $year, int $month): void
    {
        $pattern = self::CACHE_PREFIX . "*{$year}*{$month}*";
        // Note: This is a simplified approach. In production, you might want to use Redis SCAN
        Cache::flush();
        Log::info("Cache cleared for period {$year}-{$month}");
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This would require Redis-specific commands in production
        return [
            'cache_driver' => config('cache.default'),
            'cache_ttl' => self::CACHE_TTL,
            'dashboard_cache_ttl' => self::DASHBOARD_CACHE_TTL,
            'cache_prefix' => self::CACHE_PREFIX,
        ];
    }

    /**
     * Build cache key with prefix
     */
    private function buildCacheKey(string $type, array $params = []): string
    {
        $key = self::CACHE_PREFIX . $type;

        foreach ($params as $param => $value) {
            if ($value !== null) {
                $key .= ":{$param}_{$value}";
            }
        }

        return $key;
    }

    /**
     * Calculate funding data (DPK)
     */
    private function calculateFundingData(int $year, int $month): array
    {
        $tabunganTotal = \DB::table('tabungans')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->sum('sahirrp');

        $depositoTotal = \DB::table('depositos')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('kdprd', '!=', '41') // Exclude ABP
            ->sum('nomrp');

        $total = $tabunganTotal + $depositoTotal;

        // Calculate previous period for growth
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $prevTabungan = \DB::table('tabungans')
            ->where('period_year', $prevYear)
            ->where('period_month', $prevMonth)
            ->sum('sahirrp');

        $prevDeposito = \DB::table('depositos')
            ->where('period_year', $prevYear)
            ->where('period_month', $prevMonth)
            ->where('kdprd', '!=', '41')
            ->sum('nomrp');

        $prevTotal = $prevTabungan + $prevDeposito;
        $growth = $prevTotal > 0 ? (($total - $prevTotal) / $prevTotal) * 100 : 0;

        return [
            'total' => $total,
            'growth' => round($growth, 2),
            'tabungan' => $tabunganTotal,
            'deposito' => $depositoTotal
        ];
    }

    /**
     * Calculate lending data (Pembiayaan)
     */
    private function calculateLendingData(int $year, int $month): array
    {
        $total = \DB::table('pembiayaans')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->sum('osmdlc');

        // Calculate previous period for growth
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $prevTotal = \DB::table('pembiayaans')
            ->where('period_year', $prevYear)
            ->where('period_month', $prevMonth)
            ->sum('osmdlc');

        $growth = $prevTotal > 0 ? (($total - $prevTotal) / $prevTotal) * 100 : 0;

        return [
            'total' => $total,
            'growth' => round($growth, 2)
        ];
    }

    /**
     * Calculate NPF data
     */
    private function calculateNPFData(int $year, int $month): array
    {
        $totalFinancing = \DB::table('pembiayaans')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->sum('osmdlc');

        $npfFinancing = \DB::table('pembiayaans')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->whereIn('colbaru', [3, 4, 5]) // Substandard, Doubtful, Loss
            ->sum('osmdlc');

        $ratio = $totalFinancing > 0 ? ($npfFinancing / $totalFinancing) * 100 : 0;

        // Calculate previous period for growth
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $prevTotal = \DB::table('pembiayaans')
            ->where('period_year', $prevYear)
            ->where('period_month', $prevMonth)
            ->sum('osmdlc');

        $prevNpf = \DB::table('pembiayaans')
            ->where('period_year', $prevYear)
            ->where('period_month', $prevMonth)
            ->whereIn('colbaru', [3, 4, 5])
            ->sum('osmdlc');

        $prevRatio = $prevTotal > 0 ? ($prevNpf / $prevTotal) * 100 : 0;
        $growth = $prevRatio > 0 ? (($ratio - $prevRatio) / $prevRatio) * 100 : 0;

        return [
            'ratio' => round($ratio, 2),
            'amount' => $npfFinancing,
            'total_financing' => $totalFinancing,
            'growth' => round($growth, 2)
        ];
    }

    /**
     * Invalidate cache for specific data types
     */
    public function invalidateCache(string $type = 'all', array $params = []): void
    {
        $patterns = [];

        switch ($type) {
            case 'highlights':
                $patterns[] = $this->buildCacheKey('highlights', $params);
                break;
            case 'dashboard_kpis':
                $patterns[] = $this->buildCacheKey('dashboard_kpis', $params);
                break;
            case 'calculated_metrics':
                $patterns[] = $this->buildCacheKey('calculated_metrics', $params);
                break;
            case 'all':
            default:
                $patterns[] = self::CACHE_PREFIX . '*';
                break;
        }

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        if ($type === 'all') {
            $this->invalidateDashboardRenderCache();
        }

        Log::info('Cache invalidated', ['type' => $type, 'params' => $params]);
    }

    /**
     * Dispatch real-time update event
     */
    public function dispatchUpdateEvent(array $data, string $type = 'general'): void
    {
        try {
            \App\Events\FinancialDataUpdated::dispatch($data, $type);
            Log::info('Financial data update event dispatched', ['type' => $type]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch financial data update event', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
        }
    }

    /**
     * Update data and invalidate cache with real-time broadcast
     */
    public function updateDataWithBroadcast(string $type, array $data, array $cacheParams = []): void
    {
        // Invalidate relevant cache
        $this->invalidateCache($type, $cacheParams);

        // Dispatch real-time update event
        $this->dispatchUpdateEvent($data, $type);
    }
}
