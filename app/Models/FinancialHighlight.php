<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialHighlight extends Model
{
    use Searchable;
    protected $fillable = [
        'period_year',
        'period_month',
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
        'cash_ratio',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'car' => 'decimal:4',
        'roa' => 'decimal:4',
        'roe' => 'decimal:4',
        'aset' => 'decimal:2',
        'pembiayaan' => 'decimal:2',
        'laba_rugi' => 'decimal:2',
        'biaya' => 'decimal:2',
        'pendapatan' => 'decimal:2',
        'dpk' => 'decimal:2',
        'fdr' => 'decimal:4',
        'npf' => 'decimal:4',
        'bopo' => 'decimal:4',
        'cash_ratio' => 'decimal:4',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            // Dispatch real-time update event
            $cacheService = app(\App\Services\FinancialCacheService::class);
            $cacheService->updateDataWithBroadcast('highlights', [
                'period_year' => $model->period_year,
                'period_month' => $model->period_month,
                'updated_fields' => $model->getChanges()
            ], [
                'year' => $model->period_year,
                'month' => $model->period_month
            ]);
        });

        static::deleted(function ($model) {
            // Dispatch real-time update event
            $cacheService = app(\App\Services\FinancialCacheService::class);
            $cacheService->updateDataWithBroadcast('highlights', [
                'period_year' => $model->period_year,
                'period_month' => $model->period_month,
                'action' => 'deleted'
            ], [
                'year' => $model->period_year,
                'month' => $model->period_month
            ]);
        });
    }

    /**
     * Get financial highlight for specific period
     */
    public static function getForPeriod($year, $month)
    {
        return static::where('period_year', $year)
            ->where('period_month', $month)
            ->first();
    }

    /**
     * Get previous period data for comparison
     */
    public static function getPreviousPeriod($year, $month, $comparisonType = 'MOM')
    {
        if ($comparisonType === 'YOY') {
            // Get the latest period from the previous year
            return static::where('period_year', $year - 1)
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc')
                ->first();
        } else { // MOM
            $prevMonth = $month - 1;
            $prevYear = $year;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear = $year - 1;
            }
            return static::where('period_year', $prevYear)
                ->where('period_month', $prevMonth)
                ->first();
        }
    }

    /**
     * Calculate percentage change
     */
    public function getPercentageChange($field, $comparisonData)
    {
        if (!$comparisonData || !$this->$field || !$comparisonData->$field) {
            return null;
        }

        $current = $this->$field;
        $previous = $comparisonData->$field;

        if ($previous == 0) {
            return null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    /**
     * Calculate DPK (Dana Pihak Ketiga) from database
     * Includes tabungan and deposito excluding ABP (kdprd 41)
     */
    public static function calculateDpk($year, $month)
    {
        try {
            $tabunganTotal = DB::table('tabungans')
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->sum('sahirrp') ?? 0;

            $depositoTotal = DB::table('depositos')
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->where('kdprd', '!=', '41') // Exclude ABP (Arisan Berjangka Pasiva)
                ->sum('nomrp') ?? 0;

            return $tabunganTotal + $depositoTotal;
        } catch (\Exception $e) {
            \Log::warning("Error calculating DPK for {$year}-{$month}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate Pembiayaan from database
     */
    public static function calculatePembiayaan($year, $month)
    {
        try {
            return DB::table('pembiayaans')
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->sum('osmdlc') ?? 0;
        } catch (\Exception $e) {
            \Log::warning("Error calculating Pembiayaan for {$year}-{$month}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate NPF (Non-Performing Financing) ratio from database
     */
    public static function calculateNpf($year, $month)
    {
        try {
            $totalFinancing = DB::table('pembiayaans')
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->sum('osmdlc') ?? 0;

            $npfFinancing = DB::table('pembiayaans')
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->whereIn('colbaru', [3, 4, 5]) // Substandard, Doubtful, Loss
                ->sum('osmdlc') ?? 0;

            return $totalFinancing > 0 ? ($npfFinancing / $totalFinancing) * 100 : 0;
        } catch (\Exception $e) {
            \Log::warning("Error calculating NPF for {$year}-{$month}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate Aset (Assets) from database
     * Formula: Financing + DPK (without buffer)
     */
    public static function calculateAset($year, $month)
    {
        try {
            $pembiayaan = self::calculatePembiayaan($year, $month);
            $dpk = self::calculateDpk($year, $month);

            return $pembiayaan + $dpk;
        } catch (\Exception $e) {
            \Log::warning("Error calculating Aset for {$year}-{$month}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate FDR (Financing to Deposit Ratio) from database
     */
    public static function calculateFdr($year, $month)
    {
        try {
            $pembiayaan = self::calculatePembiayaan($year, $month);
            $dpk = self::calculateDpk($year, $month);

            return $dpk > 0 ? ($pembiayaan / $dpk) * 100 : 0;
        } catch (\Exception $e) {
            \Log::warning("Error calculating FDR for {$year}-{$month}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate all derived values from database and update the model
     * Only calculates: pembiayaan, dpk, npf, fdr (auto-calculated fields)
     * Other fields like CAR, ROA, ROE, aset, etc. remain as manually entered
     */
    public function calculateDerivedValues()
    {
        // Only calculate these auto-calculated fields from database
        $this->dpk = self::calculateDpk($this->period_year, $this->period_month);
        $this->pembiayaan = self::calculatePembiayaan($this->period_year, $this->period_month);
        $this->npf = self::calculateNpf($this->period_year, $this->period_month);
        $this->fdr = self::calculateFdr($this->period_year, $this->period_month);

        // Other fields (CAR, ROA, ROE, aset, laba_rugi, biaya, pendapatan, bopo, cash_ratio)
        // are manually entered and should not be overwritten
    }

    /**
     * Get or calculate field value
     */
    public function getCalculatedField($field)
    {
        // If manually set, return the value
        if ($this->$field !== null) {
            return $this->$field;
        }

        // Calculate from database
        try {
            switch ($field) {
                case 'dpk':
                    return self::calculateDpk($this->period_year, $this->period_month);
                case 'pembiayaan':
                    return self::calculatePembiayaan($this->period_year, $this->period_month);
                case 'npf':
                    return self::calculateNpf($this->period_year, $this->period_month);
                case 'fdr':
                    return self::calculateFdr($this->period_year, $this->period_month);
                default:
                    return 0;
            }
        } catch (\Exception $e) {
            \Log::warning("Error calculating field {$field}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get the fields that should be searched
     */
    protected function getSearchFields(): array
    {
        return [
            'period_year',
            'period_month',
        ];
    }
}
