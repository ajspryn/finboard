<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    /**
     * Test login page loads correctly
     */
    public function test_login_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('FinBoard')
            ->assertSee('form-control')
            ->assertSee('authentication-wrapper');
    }

    /**
     * Test verify PIN page redirects without session
     */
    public function test_verify_pin_redirects_without_session(): void
    {
        $response = $this->get('/auth/verify-pin');

        $response->assertRedirect('/');
    }

    /**
     * Test verify PIN page loads with session
     */
    public function test_verify_pin_loads_with_session(): void
    {
        $response = $this->withSession(['login_email' => 'test@example.com'])
            ->get('/auth/verify-pin');

        $response->assertStatus(200)
            ->assertSee('FinBoard')
            ->assertSee('pin1')
            ->assertSee('form-control')
            ->assertSee('authentication-wrapper');
    }

    /**
     * Test dashboard page loads with sidebar and navbar
     */
    public function test_dashboard_loads_with_sidebar(): void
    {
        // Simulate authenticated user with pin_verified session
        $response = $this->actingAs(\App\Models\User::factory()->create())
            ->withSession(['pin_verified' => true])
            ->get('/dashboard');

        $response->assertStatus(200)
            ->assertSee('FinBoard')
            ->assertSee('layout-menu')
            ->assertSee('navbar')
            ->assertSee('Dashboard Bank');
    }

    /**
     * Test user settings page loads correctly
     */
    public function test_user_settings_page_loads(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['pin_verified' => true])
            ->get('/user-settings');

        $response->assertStatus(200)
            ->assertSee('Pengaturan User')
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('Nama Lengkap');
    }
}
