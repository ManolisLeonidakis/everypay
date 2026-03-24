<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'card_last_four' => $this->card_last_four,
            'status' => $this->status,
            'psp_reference' => $this->psp_reference,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
