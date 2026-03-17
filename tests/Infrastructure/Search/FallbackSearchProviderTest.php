<?php

namespace App\Tests\Infrastructure\Search;

use App\Domain\Repository\OrderSearchInterface;
use App\Infrastructure\Monitoring\TraceIdContext;
use App\Infrastructure\Search\FallbackSearchProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FallbackSearchProviderTest extends TestCase
{
    private OrderSearchInterface $primary;
    private OrderSearchInterface $fallback;
    private LoggerInterface $logger;
    private TraceIdContext $traceIdContext;
    private FallbackSearchProvider $provider;

    protected function setUp(): void
    {
        $this->primary = $this->createMock(OrderSearchInterface::class);
        $this->fallback = $this->createMock(OrderSearchInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->traceIdContext = new TraceIdContext();

        $this->provider = new FallbackSearchProvider(
            $this->primary,
            $this->fallback,
            $this->logger,
            $this->traceIdContext
        );
    }

    public function testSearchUsesPrimary(): void
    {
        $result = new \App\Domain\Repository\SearchResult([], 1);

        $this->primary->expects($this->once())
            ->method('search')
            ->willReturn($result);

        $this->fallback->expects($this->never())->method('search');

        $this->provider->search('query');
    }

    public function testSearchFallsBackOnFailure(): void
    {
        $this->primary->expects($this->once())
            ->method('search')
            ->willThrowException(new \Exception('fail'));

        $this->fallback->expects($this->once())
            ->method('search')
            ->willReturn($this->createMock(\App\Domain\Repository\SearchResult::class));

        $this->logger->expects($this->once())->method('warning');

        $this->provider->search('query');
    }
}
