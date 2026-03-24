<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => 1,
            'amount' => fake()->numberBetween(100, 100000),
            'currency' => 'EUR',
            'description' => fake()->optional()->sentence(),
            'card_last_four' => (string) fake()->numberBetween(1000, 9999),
            'status' => TransactionStatus::Succeeded,
            'psp_reference' => 'fakestripe_' . strtoupper(Str::random(16)),
            'psp_response' => [],
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::Failed,
        ]);
    }
}
