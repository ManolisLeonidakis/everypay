<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('EUR');
            $table->string('description')->nullable();
            $table->char('card_last_four', 4);
            $table->string('status', 10)->default('succeeded');
            $table->string('psp_reference')->unique();
            $table->json('psp_response')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
