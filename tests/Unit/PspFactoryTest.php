<?php

namespace Tests\Unit;

use App\Contracts\PaymentProviderInterface;
use App\PaymentMethods\FakeStripe;
use App\Services\Psp\PspFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PspFactoryTest extends TestCase
{
    private PspFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PspFactory();
    }

    public function test_returns_fake_stripe_provider_for_fake_stripe_driver(): void
    {
        $provider = $this->factory->make('fake_stripe');

        $this->assertInstanceOf(FakeStripe::class, $provider);
    }

    public function test_returned_provider_implements_payment_provider_interface(): void
    {
        $provider = $this->factory->make('fake_stripe');

        $this->assertInstanceOf(PaymentProviderInterface::class, $provider);
    }

    public function test_throws_invalid_argument_exception_for_unknown_driver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown PSP driver: unknown_psp');

        $this->factory->make('unknown_psp');
    }

    public function test_throws_for_empty_driver_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory->make('');
    }
}
