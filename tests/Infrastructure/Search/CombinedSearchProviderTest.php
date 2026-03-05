<?php

namespace App\Tests\Infrastructure\Search;

use App\Domain\Repository\OrderSearchInterface;
use App\Infrastructure\Monitoring\TraceIdContext;
use App\Infrastructure\Search\CombinedSearchProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CombinedSearchProviderTest extends TestCase
{
    private OrderSearchInterface $primary;
    private OrderSearchInterface $fallback;
    private LoggerInterface $logger;
    private ArrayAdapter $cache;
    private TraceIdContext $traceIdContext;
    private CombinedSearchProvider $provider;

    protected function setUp(): void
    {
        $this->primary = $this->createMock(OrderSearchInterface::class);
        $this->fallback = $this->createMock(OrderSearchInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = new ArrayAdapter();
        $this->traceIdContext = new TraceIdContext();

        $this->provider = new CombinedSearchProvider(
            $this->primary,
            $this->fallback,
            $this->logger,
            $this->cache,
            $this->traceIdContext,
            3, // failureThreshold
            60 // recoveryTime
        );
    }

    public function testCircuitBreakerOpensAfterFailures(): void
    {
        $this->primary->expects($this->exactly(3))
            ->method('search')
            ->willThrowException(new \Exception('Service unavailable'));

        $this->fallback->expects($this->exactly(4))
            ->method('search');

        // 1st failure - goes to fallback
        $this->provider->search('query');
        // 2nd failure - goes to fallback
        $this->provider->search('query');
        // 3rd failure - goes to fallback, circuit opens
        $this->provider->search('query');

        // 4th call - circuit is already OPEN, primary not called
        $this->provider->search('query');
    }

    public function testCircuitBreakerResetsAfterSuccess(): void
    {
        $this->primary->expects($this->exactly(2))
            ->method('search')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \Exception('Fail')),
                $this->createMock(\App\Domain\Repository\SearchResult::class)
            );

        // 1st failure
        $this->provider->search('query');

        // 2nd success - resets failures
        $this->provider->search('query');

        // Check cache is empty (reset)
        $this->assertFalse($this->cache->hasItem('circuit_breaker_manticore'));
    }
}
