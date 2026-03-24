<?php

namespace Tests\Unit;

use App\PaymentMethods\FakeStripe;
use App\Services\Psp\ChargeData;
use PHPUnit\Framework\TestCase;

class FakeStripeTest extends TestCase
{
    private FakeStripe $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new FakeStripe();
    }

    public function test_even_last_digit_produces_success(): void
    {
        $data = new ChargeData(1000, 'EUR', '4242424242424242', '123', 12, 2030);
        $result = $this->provider->charge($data);

        $this->assertTrue($result->success);
        $this->assertSame('Charge succeeded.', $result->message);
    }

    public function test_odd_last_digit_produces_failure(): void
    {
        $data = new ChargeData(1000, 'EUR', '4242424242424241', '123', 12, 2030);
        $result = $this->provider->charge($data);

        $this->assertFalse($result->success);
        $this->assertSame('Your card was declined.', $result->message);
    }

    public function test_zero_last_digit_is_even_and_succeeds(): void
    {
        $data = new ChargeData(500, 'EUR', '4111111111111110', '321', 6, 2027);
        $result = $this->provider->charge($data);

        $this->assertTrue($result->success);
    }

    public function test_psp_reference_starts_with_fakestripe_prefix(): void
    {
        $data = new ChargeData(1000, 'EUR', '4242424242424242', '123', 12, 2030);
        $result = $this->provider->charge($data);

        $this->assertStringStartsWith('fakestripe_', $result->reference);
    }

    public function test_two_charges_produce_distinct_references(): void
    {
        $data = new ChargeData(1000, 'EUR', '4242424242424242', '123', 12, 2030);
        $result1 = $this->provider->charge($data);
        $result2 = $this->provider->charge($data);

        $this->assertNotSame($result1->reference, $result2->reference);
    }

    public function test_raw_response_contains_provider_key(): void
    {
        $data = new ChargeData(1000, 'EUR', '4242424242424242', '123', 12, 2030);
        $result = $this->provider->charge($data);

        $this->assertArrayHasKey('provider', $result->raw);
        $this->assertSame('fake_stripe', $result->raw['provider']);
    }
}
