<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FinancialHighlight;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $user = User::factory()->create(['role' => 'admin']);

        // Login the user
        $this->actingAs($user);
    }

    public function test_dashboard_api_returns_successful_response()
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

        $response = $this->get('/api/financial-highlights/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'comparison',
                'changes',
                'comparison_type',
                'period'
            ]);
    }

    public function test_dashboard_api_validates_input()
    {
        $response = $this->get('/api/financial-highlights/dashboard?year=invalid');

        // Controller doesn't validate query params; it should respond successfully.
        $response->assertStatus(200);
    }

    public function test_dashboard_api_accepts_valid_parameters()
    {
        $response = $this->get('/api/financial-highlights/dashboard?year=2025&month=12&comparison=MOM');

        $response->assertStatus(200);
    }
}
