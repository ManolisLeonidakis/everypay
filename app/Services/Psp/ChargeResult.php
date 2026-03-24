<?php

namespace App\Services\Psp;

final class ChargeResult
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $reference,
        public readonly string $message,
        public readonly array $raw = [],
    ) {
    }

    /** @param array<string, mixed> $raw */
    public static function success(string $reference, string $message, array $raw = []): self
    {
        return new self(true, $reference, $message, $raw);
    }

    /** @param array<string, mixed> $raw */
    public static function failure(string $reference, string $message, array $raw = []): self
    {
        return new self(false, $reference, $message, $raw);
    }
}
