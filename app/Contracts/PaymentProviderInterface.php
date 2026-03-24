<?php

namespace App\Contracts;

use App\Services\Psp\ChargeData;
use App\Services\Psp\ChargeResult;

interface PaymentProviderInterface
{
    public function charge(ChargeData $data): ChargeResult;
}
