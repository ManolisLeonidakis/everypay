<?php

namespace Tests\Feature;

use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_merchant_can_login_with_valid_credentials(): void
    {
        $merchant = Merchant::factory()->create([
            'email' => 'merchant@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'merchant@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($merchant);
    }

    public function test_merchant_cannot_login_with_wrong_password(): void
    {
        Merchant::factory()->create([
            'email' => 'merchant@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'merchant@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_merchant_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_merchant_can_logout(): void
    {
        $merchant = Merchant::factory()->create();

        $response = $this->actingAs($merchant)->post('/logout');

        $response->assertRedirect('/');  // back to login (named route 'login' = '/')
        $this->assertGuest();
    }

    public function test_api_token_endpoint_returns_token_for_valid_credentials(): void
    {
        Merchant::factory()->create([
            'email' => 'api@example.com',
            'password' => Hash::make('secret'),
        ]);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'api@example.com',
            'password' => 'secret',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token']);
    }

    public function test_api_token_endpoint_rejects_wrong_credentials(): void
    {
        Merchant::factory()->create(['email' => 'api@example.com']);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'api@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/');
    }
}
