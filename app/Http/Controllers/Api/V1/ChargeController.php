<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\TransactionRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChargeRequest;
use App\Http\Resources\TransactionResource;
use App\Services\Psp\ChargeData;
use App\Services\Psp\PspFactory;
use Illuminate\Http\JsonResponse;

class ChargeController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
        private readonly PspFactory $pspFactory,
    ) {
    }

    public function store(ChargeRequest $request): JsonResponse
    {
        $merchant = $request->user();
        $data = $request->validated();

        $provider = $this->pspFactory->make($merchant->psp_driver);

        $chargeData = new ChargeData(
            amount: $data['amount'],
            currency: $data['currency'] ?? 'EUR',
            cardNumber: $data['card_number'],
            cvv: $data['cvv'],
            expiryMonth: (int) $data['expiry_month'],
            expiryYear: (int) $data['expiry_year'],
            description: $data['description'] ?? null,
        );

        $result = $provider->charge($chargeData);

        $transaction = $this->transactions->create([
            'merchant_id' => $merchant->id,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'EUR',
            'description' => $data['description'] ?? null,
            'card_last_four' => substr($data['card_number'], -4),
            'status' => $result->success ? 'succeeded' : 'failed',
            'psp_reference' => $result->reference,
            'psp_response' => $result->raw,
        ]);

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }
}
