<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\TransactionRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionIndexRequest;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    public function index(TransactionIndexRequest $request): AnonymousResourceCollection
    {
        $merchant = $request->user();
        $data = $request->validated();

        $transactions = $this->transactions->findByMerchant(
            merchantId: $merchant->id,
            from: $data['from'] ?? null,
            to: $data['to'] ?? null,
        );

        return TransactionResource::collection($transactions);
    }
}
