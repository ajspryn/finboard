<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialHighlight extends Model
{
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
        'dpk',
        'fdr',
        'npf',
        'bopo',
        'cash_ratio',
        'kpmm',
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
        'dpk' => 'decimal:2',
        'fdr' => 'decimal:4',
        'npf' => 'decimal:4',
        'bopo' => 'decimal:4',
        'cash_ratio' => 'decimal:4',
        'kpmm' => 'decimal:2',
    ];

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
            return static::where('period_year', $year - 1)
                ->where('period_month', $month)
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
        $tabunganTotal = DB::table('tabungans')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->sum('sahirrp');

        $depositoTotal = DB::table('depositos')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('kdprd', '!=', '41') // Exclude ABP (Arisan Berjangka Pasiva)
            ->sum('nomrp');

        return $tabunganTotal + $depositoTotal;
    }

    /**
     * Calculate Pembiayaan from database
     */
    public static function calculatePembiayaan($year, $month)
    {
        return DB::table('pembiayaans')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->sum('osmdlc');
    }

    /**
     * Calculate NPF (Non-Performing Financing) ratio from database
     */
    public static function calculateNpf($year, $month)
    {
        $totalFinancing = DB::table('pembiayaans')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->sum('osmdlc');

        $npfFinancing = DB::table('pembiayaans')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->whereIn('colbaru', [3, 4, 5]) // Substandard, Doubtful, Loss
            ->sum('osmdlc');

        return $totalFinancing > 0 ? ($npfFinancing / $totalFinancing) * 100 : 0;
    }

    /**
     * Calculate Aset (Assets) from database
     * Formula: Financing + DPK (without buffer)
     */
    public static function calculateAset($year, $month)
    {
        $pembiayaan = self::calculatePembiayaan($year, $month);
        $dpk = self::calculateDpk($year, $month);

        return $pembiayaan + $dpk;
    }

    /**
     * Calculate FDR (Financing to Deposit Ratio) from database
     */
    public static function calculateFdr($year, $month)
    {
        $pembiayaan = self::calculatePembiayaan($year, $month);
        $dpk = self::calculateDpk($year, $month);

        return $dpk > 0 ? ($pembiayaan / $dpk) * 100 : 0;
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
        switch ($field) {
            case 'dpk':
                return self::calculateDpk($this->period_year, $this->period_month);
            case 'pembiayaan':
                return self::calculatePembiayaan($this->period_year, $this->period_month);
            case 'npf':
                return self::calculateNpf($this->period_year, $this->period_month);
            case 'aset':
                return self::calculateAset($this->period_year, $this->period_month);
            case 'fdr':
                return self::calculateFdr($this->period_year, $this->period_month);
            default:
                return null;
        }
    }
}
