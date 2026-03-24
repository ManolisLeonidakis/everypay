<?php

namespace App\Repositories;

use App\Contracts\TransactionRepositoryInterface;
use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function findByMerchant(
        int $merchantId,
        ?string $from = null,
        ?string $to = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return Transaction::query()
            ->where('merchant_id', $merchantId)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate($perPage);
    }
}
