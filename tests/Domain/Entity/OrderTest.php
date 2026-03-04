<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\Exception\InvalidOrderStateException;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private function createOrder(): Order
    {
        return new Order(
            payType: new PayType('Test Pay'),
            name: 'Test Order'
        );
    }

    public function testRecalculateTotals(): void
    {
        $order = $this->createOrder();

        $article1 = new OrderArticle();
        $article1->setPrice('100.50');
        $article1->setAmount('2');
        $article1->setWeight('1.5');

        $article2 = new OrderArticle();
        $article2->setPrice('50.00');
        $article2->setAmount('1');
        $article2->setWeight('2.0');

        $order->addArticle($article1);
        $order->addArticle($article2);

        // (100.50 * 2) + (50.00 * 1) = 201 + 50 = 251.00
        $this->assertEquals('251.00', $order->getTotalAmount());

        // (1.5 * 2) + (2.0 * 1) = 3 + 2 = 5.000
        $this->assertEquals('5.000', $order->getTotalWeight());

        // Update amount should trigger recalculate
        $article1->setAmount('3');
        // (100.50 * 3) + (50.00 * 1) = 301.5 + 50 = 351.50
        $this->assertEquals('351.50', $order->getTotalAmount());
    }

    public function testIncapsulation(): void
    {
        $customerInfo = new CustomerInfo(name: 'Ivan', surname: 'Ivanov');
        $order = new Order(
            payType: new PayType('Test Pay'),
            name: 'Test Order',
            customerInfo: $customerInfo
        );

        $this->assertEquals('Ivan', $order->getClientName());
        $this->assertEquals('Ivanov', $order->getClientSurname());

        $order->assignNumber('ORD-123');
        $this->assertEquals('ORD-123', $order->getNumber());

        $this->assertNotEmpty($order->getHash());
        $this->assertNotEmpty($order->getToken());
    }

    public function testStatusChangeFlow(): void
    {
        $order = $this->createOrder();
        $this->assertEquals(Order::STATUS_NEW, $order->getStatus());

        $order->changeStatus(Order::STATUS_PROCESSING);
        $this->assertEquals(Order::STATUS_PROCESSING, $order->getStatus());

        $order->changeStatus(Order::STATUS_SHIPPED);
        $this->assertEquals(Order::STATUS_SHIPPED, $order->getStatus());

        $order->changeStatus(Order::STATUS_DELIVERED);
        $this->assertEquals(Order::STATUS_DELIVERED, $order->getStatus());
    }

    public function testInvalidStatusTransition(): void
    {
        $order = $this->createOrder();

        $this->expectException(InvalidOrderStateException::class);
        $order->changeStatus(Order::STATUS_DELIVERED);
    }

    public function testCancelation(): void
    {
        $order = $this->createOrder();
        $order->changeStatus(Order::STATUS_CANCELLED);

        $this->assertEquals(Order::STATUS_CANCELLED, $order->getStatus());
        $this->assertNotNull($order->getCancelDate());
    }
}
