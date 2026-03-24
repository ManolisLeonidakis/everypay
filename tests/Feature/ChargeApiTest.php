<?php

namespace Tests\Feature;

use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChargeApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(string $cardNumber = '4242424242424242'): array
    {
        return [
            'amount' => 1000,
            'card_number' => $cardNumber,
            'cvv' => '123',
            'expiry_month' => 12,
            'expiry_year' => (int) date('Y') + 2,
        ];
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson('/api/v1/charges', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_successful_charge_with_even_last_digit(): void
    {
        $merchant = Merchant::factory()->create(['psp_driver' => 'fake_stripe']);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/charges', $this->validPayload('4242424242424242'));

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'succeeded')
            ->assertJsonPath('data.card_last_four', '4242')
            ->assertJsonPath('data.amount', 1000)
            ->assertJsonStructure(['data' => ['id', 'amount', 'currency', 'status', 'psp_reference', 'created_at']]);
    }

    public function test_failed_charge_with_odd_last_digit(): void
    {
        $merchant = Merchant::factory()->create(['psp_driver' => 'fake_stripe']);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/charges', $this->validPayload('4242424242424241'));

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'failed');
    }

    public function test_transaction_is_persisted_in_database(): void
    {
        $merchant = Merchant::factory()->create(['psp_driver' => 'fake_stripe']);
        $token = $merchant->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/charges', $this->validPayload());

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', [
            'merchant_id' => $merchant->id,
            'amount' => 1000,
            'card_last_four' => '4242',
        ]);
    }

    public function test_missing_required_fields_returns_422(): void
    {
        $merchant = Merchant::factory()->create(['psp_driver' => 'fake_stripe']);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/charges', ['amount' => -100]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'card_number', 'cvv', 'expiry_month', 'expiry_year']);
    }

    public function test_charge_includes_optional_description(): void
    {
        $merchant = Merchant::factory()->create(['psp_driver' => 'fake_stripe']);
        $token = $merchant->createToken('test')->plainTextToken;

        $payload = array_merge($this->validPayload(), ['description' => 'Test purchase']);
        $response = $this->withToken($token)->postJson('/api/v1/charges', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.description', 'Test purchase');
    }
}
