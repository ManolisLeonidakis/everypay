<?php

namespace App\Http\Controllers;

use App\Contracts\TransactionRepositoryInterface;
use App\Http\Requests\TransactionIndexRequest;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    public function index(TransactionIndexRequest $request): Response
    {
        $merchant = $request->user();
        $data = $request->validated();

        $transactions = $this->transactions->findByMerchant(
            merchantId: $merchant->id,
            from: $data['from'] ?? null,
            to: $data['to'] ?? null,
        );

        return Inertia::render('Transactions', [
            'name' => $merchant->name,
            'transactions' => $transactions,
            'filters' => [
                'from' => $data['from'] ?? null,
                'to' => $data['to'] ?? null,
            ],
        ]);
    }
}
