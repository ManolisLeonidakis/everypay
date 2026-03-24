<?php

namespace App\Services\Psp;

final class ChargeData
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $cardNumber,
        public readonly string $cvv,
        public readonly int $expiryMonth,
        public readonly int $expiryYear,
        public readonly ?string $description = null,
    ) {
    }
}
