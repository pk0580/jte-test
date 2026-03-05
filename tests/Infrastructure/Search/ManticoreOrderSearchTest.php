<?php

namespace App\Tests\Infrastructure\Search;

use App\Domain\Entity\Order;
use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\ManagerInfo;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\SearchResult;
use App\Infrastructure\Search\ManticoreOrderSearch;
use App\Infrastructure\Search\OrderSearchQueryBuilder;
use App\Infrastructure\Monitoring\TraceIdContext;
use Manticoresearch\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ManticoreOrderSearchTest extends TestCase
{
    public function testSearchThrowsExceptionOnFailure(): void
    {
        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $queryBuilder = new OrderSearchQueryBuilder();
        $traceIdContext = new TraceIdContext();

        // Pointing to a wrong port to ensure failure
        $search = new ManticoreOrderSearch('localhost', 9307, $orderRepository, $queryBuilder, $logger, $traceIdContext);

        $query = 'test query';
        $page = 1;
        $limit = 10;

        $this->expectException(\Manticoresearch\Exceptions\NoMoreNodesException::class);

        $search->search($query, $page, $limit);
    }

    public function testSwapIndexValidation(): void
    {
        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $queryBuilder = new OrderSearchQueryBuilder();
        $traceIdContext = new TraceIdContext();
        $search = new ManticoreOrderSearch('localhost', 9308, $orderRepository, $queryBuilder, $logger, $traceIdContext);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid tmp index name');
        $search->swapIndex('invalid-name!', 'valid_name');
    }

    public function testSwapIndexValidationMain(): void
    {
        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $queryBuilder = new OrderSearchQueryBuilder();
        $traceIdContext = new TraceIdContext();
        $search = new ManticoreOrderSearch('localhost', 9308, $orderRepository, $queryBuilder, $logger, $traceIdContext);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid main index name');
        $search->swapIndex('valid_tmp', 'invalid;name');
    }

    public function testBulkIndexChunking(): void
    {
        $orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $client = $this->createMock(Client::class);
        $queryBuilder = new OrderSearchQueryBuilder();
        $traceIdContext = new TraceIdContext();

        // We need to inject the client or use reflection because it's private
        $search = new ManticoreOrderSearch('localhost', 9308, $orderRepository, $queryBuilder, $logger, $traceIdContext);
        $reflection = new \ReflectionClass($search);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($search, $client);

        $rows = [];
        for ($i = 1; $i <= 2500; $i++) {
            $rows[] = ['id' => $i, 'number' => 'ORD-' . $i];
        }

        // Expected 3 calls: 1000, 1000, 500
        $client->expects($this->exactly(3))
            ->method('bulk')
            ->willReturnCallback(function($params) use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    $this->assertCount(1000, $params['body']);
                } elseif ($callCount === 2) {
                    $this->assertCount(1000, $params['body']);
                } elseif ($callCount === 3) {
                    $this->assertCount(500, $params['body']);
                }
                return [];
            });

        $callCount = 0;
        $search->bulkIndexRawToIndex('orders', $rows);
        $this->assertEquals(3, $callCount);
    }
}
