<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FinancialHighlight;

class FinancialHighlightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'period_year' => 2025,
                'period_month' => 11,
                'car' => 15.50,
                'roa' => 2.30,
                'roe' => 18.75,
                // aset, pembiayaan, dpk, npf, fdr will be calculated automatically
                'laba_rugi' => 2500000000, // 2.5 Miliar
                'bopo' => 78.50,
            ],
            [
                'period_year' => 2025,
                'period_month' => 10,
                'car' => 15.25,
                'roa' => 2.15,
                'roe' => 18.20,
                // aset, pembiayaan, dpk, npf, fdr will be calculated automatically
                'laba_rugi' => 2400000000, // 2.4 Miliar
                'bopo' => 79.20,
            ],
            [
                'period_year' => 2024,
                'period_month' => 11,
                'car' => 14.80,
                'roa' => 1.95,
                'roe' => 17.50,
                // aset, pembiayaan, dpk, npf, fdr will be calculated automatically
                'laba_rugi' => 2200000000, // 2.2 Miliar
                'bopo' => 80.50,
            ],
        ];

        foreach ($data as $item) {
            FinancialHighlight::updateOrCreate(
                [
                    'period_year' => $item['period_year'],
                    'period_month' => $item['period_month']
                ],
                $item
            );
        }
    }
}
