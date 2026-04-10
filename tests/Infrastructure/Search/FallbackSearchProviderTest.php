<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Search;

use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
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
        $result = new SearchResult([], 1);
        /** @var \PHPUnit\Framework\MockObject\MockObject&OrderSearchInterface $primary */
        $primary = $this->primary;
        /** @var \PHPUnit\Framework\MockObject\MockObject&OrderSearchInterface $fallback */
        $fallback = $this->fallback;
        /** @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface $logger */
        $logger = $this->logger;

        $primary->expects($this->once())
            ->method('search')
            ->willReturn($result);

        $fallback->expects($this->never())->method('search');
        $logger->expects($this->never())->method('warning');

        $this->provider->search('query');
    }

    public function testSearchFallsBackOnFailure(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&OrderSearchInterface $primary */
        $primary = $this->primary;
        /** @var \PHPUnit\Framework\MockObject\MockObject&OrderSearchInterface $fallback */
        $fallback = $this->fallback;
        /** @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface $logger */
        $logger = $this->logger;

        $primary->expects($this->once())
            ->method('search')
            ->willThrowException(new \Exception('fail'));

        $fallback->expects($this->once())
            ->method('search')
            ->willReturn(new SearchResult([], 0));

        $logger->expects($this->once())->method('warning');

        $this->provider->search('query');
    }
}
