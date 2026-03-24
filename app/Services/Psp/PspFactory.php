<?php

namespace App\Services\Psp;

use App\Contracts\PaymentProviderInterface;
use Illuminate\Support\Str;
use ReflectionClass;

class PspFactory
{
    protected array $providers = [];

    public function __construct()
    {
        $this->discoverProviders();
    }

    public function make(string $driver): PaymentProviderInterface
    {
        if (!isset($this->providers[$driver])) {
            throw new \InvalidArgumentException("Unknown PSP driver: {$driver}");
        }

        return app($this->providers[$driver]);
    }

    protected function discoverProviders(): void
    {
        $path = app_path('PaymentMethods');

        foreach (glob($path . '/*.php') as $file) {
            $class = 'App\\PaymentMethods\\' . basename($file, '.php');

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (
                $reflection->isAbstract() ||
                !$reflection->implementsInterface(PaymentProviderInterface::class)
            ) {
                continue;
            }

            $driver = Str::of($reflection->getShortName())
                ->replaceLast('Provider', '')
                ->snake()
                ->toString();

            $this->providers[$driver] = $class;
        }
    }
}
