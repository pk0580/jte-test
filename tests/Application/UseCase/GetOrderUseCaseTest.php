<?php

namespace App\Tests\Application\UseCase;

use App\Application\UseCase\GetOrderUseCase;
use App\Domain\Entity\Order;
use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\ManagerInfo;
use App\Domain\ValueObject\OrderMetadata;
use App\Domain\ValueObject\OrderDates;
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

        $order = new Order(
            payType: $payType,
            name: 'Test Order',
            customerInfo: new CustomerInfo('John', 'Doe', 'john@example.com'),
            deliveryAddress: new DeliveryAddress(),
            deliveryTerms: new DeliveryTerms(),
            managerInfo: new ManagerInfo(),
            financialTerms: new FinancialTerms(currency: 'USD'),
            deliveryConfig: new DeliveryConfig(),
            locale: 'en',
            measure: 'unit'
        );

        $reflectionDates = new \ReflectionClass(Order::class);
        $propDates = $reflectionDates->getProperty('dates');
        $propDates->setAccessible(true);
        $propDates->setValue($order, new OrderDates(createAt: new \DateTimeImmutable('2023-01-01 12:00:00')));

        $reflectionMetadata = new \ReflectionClass(Order::class);
        $propMetadata = $reflectionMetadata->getProperty('metadata');
        $propMetadata->setAccessible(true);
        $propMetadata->setValue($order, new OrderMetadata(
            hash: 'test_hash',
            token: 'test_token',
            locale: 'en',
            measure: 'unit'
        ));

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
