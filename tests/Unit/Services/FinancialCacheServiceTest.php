<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FinancialCacheService;
use App\Models\FinancialHighlight;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FinancialCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(FinancialCacheService::class);
    }

    public function test_get_financial_highlights_returns_array()
    {
        // Create test data
        FinancialHighlight::create([
            'period_year' => 2025,
            'period_month' => 12,
            'car' => 15.5,
            'roa' => 2.1,
            'roe' => 18.5,
            'aset' => 1000000000,
            'pembiayaan' => 800000000,
        ]);

        $result = $this->cacheService->getFinancialHighlights();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('comparison', $result);
        $this->assertArrayHasKey('changes', $result);
    }

    public function test_constants_are_defined()
    {
        $this->assertEquals(60, FinancialHighlight::CACHE_TTL_MINUTES);
        $this->assertEquals(30, FinancialHighlight::DASHBOARD_CACHE_TTL_MINUTES);
        $this->assertEquals('MOM', FinancialHighlight::DEFAULT_COMPARISON);
        $this->assertContains('MOM', FinancialHighlight::COMPARISON_TYPES);
        $this->assertContains('YOY', FinancialHighlight::COMPARISON_TYPES);
    }
}
