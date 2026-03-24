<?php

namespace App\PaymentMethods;

use App\Contracts\PaymentProviderInterface;
use App\Services\Psp\ChargeData;
use App\Services\Psp\ChargeResult;
use Illuminate\Support\Str;

class FakeStripe implements PaymentProviderInterface
{
    public function charge(ChargeData $data): ChargeResult
    {
        $reference = 'fakestripe_' . strtoupper(Str::random(16));
        $lastDigit = (int) substr($data->cardNumber, -1);

        if ($lastDigit % 2 === 0) {
            return ChargeResult::success(
                reference: $reference,
                message: 'Charge succeeded.',
                raw: ['provider' => 'fake_stripe', 'simulated' => true],
            );
        }

        return ChargeResult::failure(
            reference: $reference,
            message: 'Your card was declined.',
            raw: ['provider' => 'fake_stripe', 'simulated' => true],
        );
    }
}
