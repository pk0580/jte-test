<?php

namespace App\Tests\Application\UseCase;

use App\Application\UseCase\GetOrderStatsUseCase;
use App\Domain\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

class GetOrderStatsUseCaseTest extends TestCase
{
    public function testExecute(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getStats')
            ->with('day', 1, 10)
            ->willReturn([
                'items' => [
                    ['period' => '2023-01-01', 'orderCount' => 5, 'totalAmount' => 500.0]
                ],
                'total' => 1
            ]);

        $useCase = new GetOrderStatsUseCase($repository);
        $result = $useCase->execute('day', 1, 10);

        $this->assertEquals(1, $result->totalItems);
        $this->assertCount(1, $result->items);
        $this->assertEquals('2023-01-01', $result->items[0]->period);
        $this->assertEquals(5, $result->items[0]->orderCount);
        $this->assertEquals(500.0, $result->items[0]->totalAmount);
    }
}
