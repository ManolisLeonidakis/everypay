<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Merchant::factory()->create([
            'name' => 'Demo Merchant',
            'email' => 'merchant@example.com',
            'psp_driver' => 'fake_stripe',
        ]);

        Transaction::factory(20)->create();
    }
}
