<?php

namespace App\Tests\Application\UseCase;

use App\Application\UseCase\GetOrderUseCase;
use App\Domain\Entity\Order;
use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetOrderUseCaseTest extends TestCase
{
    public function testExecuteSuccess(): void
    {
        $payType = new PayType('Test Pay');
        $reflectionPay = new \ReflectionClass(PayType::class);
        $propPay = $reflectionPay->getProperty('id');
        $propPay->setAccessible(true);
        $propPay->setValue($payType, 1);

        $order = new Order();
        $order->setHash('test_hash');
        $order->setToken('test_token');
        $order->setCustomerInfo(new CustomerInfo('John', 'Doe', 'john@example.com'));
        $order->setPayType($payType);
        $order->setCreateDate(new \DateTime('2023-01-01 12:00:00'));
        $order->setLocale('en');
        $order->setCurrency('USD');
        $order->setMeasure('unit');
        $order->setName('Test Order');

        // Reflection to set ID since it's only set by Doctrine
        $reflection = new \ReflectionClass(Order::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($order, 1);

        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($order);

        $useCase = new GetOrderUseCase($repository);
        $result = $useCase->execute(1);

        $this->assertEquals(1, $result->id);
        $this->assertEquals('John', $result->clientName);
        $this->assertEquals('2023-01-01 12:00:00', $result->createDate);
    }

    public function testExecuteNotFound(): void
    {
        $repository = $this->createStub(OrderRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $useCase = new GetOrderUseCase($repository);

        $this->expectException(NotFoundHttpException::class);
        $useCase->execute(999);
    }
}
