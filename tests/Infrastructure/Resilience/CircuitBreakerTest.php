<?php

namespace App\Tests\Infrastructure\Resilience;

use App\Infrastructure\Resilience\CircuitBreaker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CircuitBreakerTest extends TestCase
{
    private ArrayAdapter $cache;
    private CircuitBreaker $cb;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->cb = new CircuitBreaker($this->cache, 'test_cb', 2, 60);
    }

    public function testCallSuccess(): void
    {
        $result = $this->cb->call(fn() => 'ok');
        $this->assertEquals('ok', $result);
    }

    public function testOpensAfterFailures(): void
    {
        try {
            $this->cb->call(fn() => throw new \Exception('fail 1'));
        } catch (\Throwable) {}

        try {
            $this->cb->call(fn() => throw new \Exception('fail 2'));
        } catch (\Throwable) {}

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Circuit breaker 'test_cb' is open");

        $this->cb->call(fn() => 'should not be called');
    }

    public function testResetsAfterSuccess(): void
    {
        try {
            $this->cb->call(fn() => throw new \Exception('fail 1'));
        } catch (\Throwable) {}

        $this->cb->call(fn() => 'ok');

        // Should not be open
        $result = $this->cb->call(fn() => 'still ok');
        $this->assertEquals('still ok', $result);
    }
}
