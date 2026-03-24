<?php

namespace App\Contracts;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Transaction;

    public function findByMerchant(
        int $merchantId,
        ?string $from = null,
        ?string $to = null,
        int $perPage = 15,
    ): LengthAwarePaginator;
}
