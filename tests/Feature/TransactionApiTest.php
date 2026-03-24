<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/transactions');

        $response->assertStatus(401);
    }

    public function test_merchant_can_list_their_transactions(): void
    {
        $merchant = Merchant::factory()->create();
        Transaction::factory(3)->create(['merchant_id' => $merchant->id]);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'amount', 'currency', 'status', 'psp_reference', 'created_at']], 'meta']);
    }

    public function test_merchant_cannot_see_other_merchants_transactions(): void
    {
        $merchant = Merchant::factory()->create();
        $otherMerchant = Merchant::factory()->create();
        Transaction::factory(5)->create(['merchant_id' => $otherMerchant->id]);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_transactions_filtered_by_from_date(): void
    {
        $merchant = Merchant::factory()->create();
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'created_at' => '2026-01-15']);
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'created_at' => '2026-03-10']);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/transactions?from=2026-03-01');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_transactions_filtered_by_to_date(): void
    {
        $merchant = Merchant::factory()->create();
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'created_at' => '2026-01-15']);
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'created_at' => '2026-03-10']);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/transactions?to=2026-02-28');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_transactions_filtered_by_date_range(): void
    {
        $merchant = Merchant::factory()->create();
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'created_at' => '2026-01-01']);
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'created_at' => '2026-02-15']);
        Transaction::factory()->create(['merchant_id' => $merchant->id, 'created_at' => '2026-03-19']);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/transactions?from=2026-02-01&to=2026-02-28');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_invalid_date_format_returns_422(): void
    {
        $merchant = Merchant::factory()->create();
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/transactions?from=not-a-date');

        $response->assertStatus(422);
    }
}
